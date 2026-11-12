"""
LeadGER call recording consumer (RabbitMQ).

The call provider puts one message per finished call on a RabbitMQ queue named after the company's CRM ID (from its
licence). This program listens on the queue of every company and saves the recording link and call details in that
company's database:
  - Auto Dialer call (reference has "_d_")  -> leadger_dialer_logs
  - incoming call (incoming = 1)              -> leadger_incoming_vmn_logs + Laravel live alert
  - Click2Call / WebRTC call                  -> leadger_call_logs

Companies: for every .env path in config.ini [path]: the main database's own company AND every company of its
companies list (leadger_tenants). A CRM ID used by two companies is listened to once (and a warning is logged).

Settings (config.ini on the server, not in git): [rabbitmq] host / port / user / password, [licence] key (required),
[laravel] php (PHP binary, e.g. php8.3; default php).
"""
import json
import logging
import os
import subprocess
import time
from configparser import ConfigParser
from datetime import datetime
from threading import Thread, Event

import mysql.connector
import pika

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
logging.basicConfig(
    filename=os.path.join(BASE_DIR, datetime.now().strftime('rabbitmq_consumer_%Y-%m-%d.log')),
    level=logging.INFO,
    format='%(asctime)s %(levelname)s %(message)s',
)

config = ConfigParser()
config.read(os.path.join(BASE_DIR, 'config.ini'))

# Connection settings and the licence key come only from config.ini on the server (never from this file)
RABBIT_HOST = config.get('rabbitmq', 'host', fallback='')
RABBIT_PORT = config.getint('rabbitmq', 'port', fallback=5672)
RABBIT_USER = config.get('rabbitmq', 'user', fallback='')
RABBIT_PASSWORD = config.get('rabbitmq', 'password', fallback='')
LICENCE_KEY = config.get('licence', 'key', fallback='')
PHP_BINARY = config.get('laravel', 'php', fallback='php')
_missing = [n for n, v in (('[rabbitmq] host', RABBIT_HOST), ('[rabbitmq] user', RABBIT_USER),
                           ('[rabbitmq] password', RABBIT_PASSWORD), ('[licence] key', LICENCE_KEY)) if not v]
if _missing and __name__ == '__main__':
    raise SystemExit('config.ini is missing: ' + ', '.join(_missing))

# Database errors worth trying again (server gone, connection lost): the message goes back on the queue
RETRY_ERRORS = (mysql.connector.errors.InterfaceError, mysql.connector.errors.OperationalError)


def load_laravel_env(env_path):
    """KEY=value lines of a Laravel .env (quotes removed; comments and lines without '=' skipped)."""
    env_vars = {}
    with open(env_path) as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, value = line.split('=', 1)
            value = value.strip()
            if len(value) >= 2 and value[0] == value[-1] and value[0] in ('"', "'"):
                value = value[1:-1]
            env_vars[key.strip()] = value
    return env_vars


def read_crm_id(db_connection, prefix):
    """CRM ID from the company's licence (settings.full_response), or None."""
    with mysql.connector.connect(**db_connection) as connection:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(
            f"SELECT FROM_BASE64(AES_DECRYPT(full_response, %s)) AS full_response FROM {prefix}settings ORDER BY id LIMIT 1",
            (LICENCE_KEY,))
        row = cursor.fetchone()
    if not row or not row['full_response']:
        return None
    raw = row['full_response']
    if isinstance(raw, (bytes, bytearray)):
        raw = raw.decode('utf-8', 'ignore')
    try:
        return str(json.loads(raw)['data']['crm_id'])
    except (ValueError, KeyError, TypeError):
        return None


def get_companies(env_path, env_vars):
    """[(crm_id, db_connection, env_path)] for the main database and every company of its companies list."""
    prefix = env_vars.get('DB_TABLE_PREFIX', 'leadger_')
    main = {
        'host': env_vars['DB_HOST'],
        'port': int(env_vars.get('DB_PORT', 3306) or 3306),
        'user': env_vars['DB_USERNAME'],
        'password': env_vars.get('DB_PASSWORD', ''),
        'database': env_vars['DB_DATABASE'],
    }
    connections = [('main ' + main['database'], main)]
    try:
        with mysql.connector.connect(**main) as connection:
            cursor = connection.cursor(dictionary=True)
            cursor.execute(f"SHOW TABLES LIKE '{prefix}tenants'")
            if cursor.fetchone():
                cursor.execute(f"SELECT subdomain, db_host, db_name, db_user, db_password FROM {prefix}tenants ORDER BY id")
                for t in cursor.fetchall():
                    connections.append((t['subdomain'], {
                        'host': t['db_host'], 'port': main['port'], 'user': t['db_user'],
                        'password': t['db_password'] or '', 'database': t['db_name'],
                    }))
    except mysql.connector.Error as e:
        logging.error(f"Companies list could not be read ({main['database']}): {e}")

    companies = []
    for name, db in connections:
        try:
            crm_id = read_crm_id(db, prefix)
        except mysql.connector.Error as e:
            logging.error(f"{name}: database could not be read ({db['database']}): {e}")
            continue
        if not crm_id:
            logging.warning(f"{name}: no licence / CRM ID in {db['database']}, skipped")
            continue
        companies.append((crm_id, db, env_path, name))
    return companies


def process_call_recording(call_msg, db_connection, env_path):
    """True when the message is done (saved, or cannot ever be saved); False to put it back on the queue."""
    try:
        lead_ref = str(call_msg.get('lead_id') or '')
        parts = lead_ref.split('_')
        if len(parts) > 1 and parts[1] == 'd':
            process_dialer_call(call_msg, db_connection)
        elif call_msg.get('incoming') == 1:
            process_incoming_call(call_msg, db_connection, env_path)
        else:
            process_other_call(call_msg, db_connection)
        return True
    except RETRY_ERRORS as e:
        logging.error(f"Database not reachable, message put back on the queue: {e}")
        return False
    except Exception as e:
        logging.error(f"Message could not be saved (dropped): {e} | message: {call_msg}")
        return True


def mm_ss(seconds):
    seconds = int(seconds or 0)
    return '{:02d}:{:02d}'.format(seconds // 60, seconds % 60)


def process_dialer_call(call_msg, db_connection):
    with mysql.connector.connect(**db_connection) as connection:
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT id, reference_id FROM leadger_dialer_logs WHERE reference_id = %s", (call_msg['lead_id'],))
        log = cursor.fetchone()
        if not log:
            logging.error(f"Dialer log not found: {call_msg['lead_id']} ({db_connection['database']})")
            return
        cursor.execute(
            """UPDATE leadger_dialer_logs
               SET click_response = %s, click_recording_url = %s, file_path = %s, duration = %s
               WHERE reference_id = %s""",
            (call_msg.get('response'), call_msg.get('recording_url'), call_msg.get('recording_url'),
             mm_ss(call_msg.get('duration')), log['reference_id']))
        connection.commit()
        logging.info(f"Recording saved for dialer log {log['id']} ({db_connection['database']})")


def process_other_call(call_msg, db_connection):
    reference_id = call_msg['lead_id']
    with mysql.connector.connect(**db_connection) as connection:
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT id FROM leadger_call_logs WHERE reference_id = %s", (reference_id,))
        log = cursor.fetchone()
        if not log:
            logging.error(f"Call log not found: {reference_id} ({db_connection['database']})")
            return
        cursor.execute(
            """UPDATE leadger_call_logs
               SET updated_at = %s, click_response = %s, click_recording_url = %s
               WHERE reference_id = %s""",
            (datetime.now(), call_msg.get('response'), call_msg.get('recording_url'), reference_id))
        connection.commit()
        logging.info(f"Recording saved for call log {log['id']} ({db_connection['database']})")


def process_incoming_call(call_msg, db_connection, env_path):
    recording_url = '' if call_msg.get('recording_url') == 'No Voice' else (call_msg.get('recording_url') or '')
    with mysql.connector.connect(**db_connection) as connection:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(
            """INSERT INTO leadger_incoming_vmn_logs
               (call_id, dni, a_party_no, a_start_time, a_dial_status, cli, recording_url, duration, response, file_path)
               VALUES (%s, %s, %s, %s, '', %s, %s, %s, %s, '')""",
            (call_msg.get('call_id'), call_msg.get('dni'), call_msg.get('a_party_no'),
             iso_to_mysql(call_msg.get('a_start_time')), call_msg.get('cli'), recording_url,
             call_msg.get('duration'), call_msg.get('response')))
        connection.commit()
        log_id = cursor.lastrowid
    logging.info(f"Incoming call saved: log {log_id} ({db_connection['database']})")
    trigger_laravel_event(db_connection, log_id, env_path)


def trigger_laravel_event(db_conn, log_id, env_path):
    """Live incoming-call alert in LeadGER (php artisan event:fire-incoming-log)."""
    laravel_root = os.path.dirname(env_path)
    try:
        result = subprocess.run(
            [PHP_BINARY, os.path.join(laravel_root, 'artisan'), 'event:fire-incoming-log',
             db_conn['database'], db_conn['user'], db_conn['password'], db_conn['host'], str(log_id)],
            cwd=laravel_root, capture_output=True, text=True, timeout=60)
        if result.returncode == 0:
            logging.info(f"Live alert sent for incoming log {log_id} ({db_conn['database']})")
        else:
            logging.error(f"Live alert failed for incoming log {log_id} ({db_conn['database']}): {result.stderr.strip()[:500]}")
    except Exception as e:
        logging.error(f"Live alert could not run for incoming log {log_id} ({db_conn['database']}): {e}")


def iso_to_mysql(iso_str):
    if not iso_str:
        return None
    return datetime.fromisoformat(str(iso_str).replace('Z', '+00:00')).strftime('%Y-%m-%d %H:%M:%S')


def do_work(crm_id, db_connection, env_path, name, stop_event):
    """Listen on the queue <crm_id>; reconnect after a lost connection."""
    while not stop_event.is_set():
        try:
            connection = pika.BlockingConnection(pika.ConnectionParameters(
                host=RABBIT_HOST, port=RABBIT_PORT, heartbeat=60,
                credentials=pika.PlainCredentials(RABBIT_USER, RABBIT_PASSWORD)))
            channel = connection.channel()
            channel.queue_declare(queue=crm_id, durable=True)
            channel.basic_qos(prefetch_count=1)

            def callback(ch, method, properties, body):
                try:
                    call_msg = json.loads(body.decode('utf-8'))
                except ValueError:
                    logging.error(f"[{crm_id}] Not a JSON message, dropped: {body[:200]!r}")
                    ch.basic_ack(delivery_tag=method.delivery_tag)
                    return
                logging.info(f"[{crm_id}] Received: {call_msg}")
                if process_call_recording(call_msg, db_connection, env_path):
                    ch.basic_ack(delivery_tag=method.delivery_tag)
                else:
                    time.sleep(5)
                    ch.basic_nack(delivery_tag=method.delivery_tag, requeue=True)

            channel.basic_consume(queue=crm_id, on_message_callback=callback, auto_ack=False)
            logging.info(f"[{crm_id}] Listening for {name} ({db_connection['database']})")
            print(f"[{crm_id}] listening for {name} ({db_connection['database']})", flush=True)
            while not stop_event.is_set():
                connection.process_data_events(time_limit=1)
            connection.close()
        except Exception as e:
            logging.error(f"[{crm_id}] RabbitMQ connection lost: {e}; reconnecting in 10 s")
            stop_event.wait(10)


if __name__ == '__main__':
    if not config.has_section('path'):
        raise SystemExit('config.ini has no [path] section')

    companies, seen = [], {}
    for key, env_path in config['path'].items():
        env_vars = load_laravel_env(env_path)
        for crm_id, db, path, name in get_companies(env_path, env_vars):
            if crm_id in seen:
                logging.warning(f"CRM ID {crm_id} of {name} is also used by {seen[crm_id]}: recordings of that CRM ID go to {seen[crm_id]} only")
                print(f"WARNING: CRM ID {crm_id} of {name} is also used by {seen[crm_id]}", flush=True)
                continue
            seen[crm_id] = name
            companies.append((crm_id, db, path, name))

    print('Companies found (CRM ID -> company, database):', flush=True)
    for crm_id, db, path, name in companies:
        print(f'  {crm_id} -> {name}, {db["database"]}', flush=True)   # never the password
    if not companies:
        raise SystemExit('No company with a licence / CRM ID found: nothing to listen to')

    stop_event = Event()
    threads = [Thread(target=do_work, args=(crm_id, db, path, name, stop_event), daemon=True)
               for crm_id, db, path, name in companies]
    for thread in threads:
        thread.start()
    try:
        while any(t.is_alive() for t in threads):
            time.sleep(1)
    except KeyboardInterrupt:
        stop_event.set()
        for thread in threads:
            thread.join(timeout=5)
    print('Stopped')
