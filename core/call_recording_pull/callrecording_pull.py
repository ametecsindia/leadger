import os
import pika
import json
import mysql.connector
from datetime import datetime
import logging
import requests
import subprocess
from threading import Thread, Event
from configparser import ConfigParser
import sys

logging.basicConfig(filename=datetime.now().strftime('rabbitmq_consumer_%Y-%m-%d.log'), level=logging.INFO)

def load_laravel_env(env_path):
    env_vars = {}
    with open(env_path) as f:
        for line in f:
            if line.strip() and not line.startswith('#'):
                key, value = line.strip().split('=', 1)
                env_vars[key] = value
    return env_vars

def get_crm_ids_and_db_connections(env_vars):
    crm_ids = []
    db_connections = []
    connection = None
    try:
        db_password = env_vars.get('DB_PASSWORD')
        connection = mysql.connector.connect(
            host=env_vars['DB_HOST'],
            user=env_vars['DB_USERNAME'],
            password=db_password,
            database=env_vars['DB_DATABASE']
        )
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT * FROM leadger_tenants")
        clients = cursor.fetchall()

        if not clients:
            logging.info("No tenants found. Falling back to current DB.")
            cursor.execute("SELECT id, site_title_en, FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response FROM leadger_settings")
            rows = cursor.fetchall()
            for row in rows:
                if row['full_response']:
                    full_response = json.loads(row['full_response'])
                    crm_ids.append(full_response['data']['crm_id'])
                    db_connections.append({
                        'host': env_vars['DB_HOST'],
                        'user': env_vars['DB_USERNAME'],
                        'password': db_password,
                        'database': env_vars['DB_DATABASE']
                    })
        else:
            for client in clients:
                try:
                    client_connection = mysql.connector.connect(
                        host=client['db_host'],
                        user=client['db_user'],
                        password=client['db_password'],
                        database=client['db_name']
                    )
                    client_cursor = client_connection.cursor(dictionary=True)
                    client_cursor.execute("SELECT id, site_title_en, FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response FROM leadger_settings")
                    rows = client_cursor.fetchall()
                    for row in rows:
                        if row['full_response']:
                            full_response = json.loads(row['full_response'])
                            crm_ids.append(full_response['data']['crm_id'])
                            db_connections.append({
                                'host': client['db_host'],
                                'user': client['db_user'],
                                'password': client['db_password'],
                                'database': client['db_name']
                            })
                except Exception as e:
                    logging.error(f"Client DB error: {e}")
                finally:
                    if client_connection.is_connected():
                        client_cursor.close()
                        client_connection.close()
    except Exception as e:
        logging.error(f"Main DB error: {e}")
    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()
    return crm_ids, db_connections

def process_call_recording(call_msg, db_connection, env_vars):
    try:
        logging.info(f"Processing call message: {call_msg}")
        if ('lead_id' in call_msg) and call_msg['lead_id'].split('_')[1] == "d":
            process_dialer_call(call_msg, db_connection, env_vars)
        elif ('incoming' in call_msg) and call_msg['incoming'] == 1:
            process_incoming_call(call_msg, db_connection, env_vars)
        else:
            process_other_call(call_msg, db_connection, env_vars)
    except Exception as e:
        logging.error(f"process_call_recording error: {e}")

def process_dialer_call(call_msg, db_connection, env_vars):
    try:
        logging.info(f"Processing dialer call: {call_msg['lead_id']}")
        with mysql.connector.connect(**db_connection) as connection:
            cursor = connection.cursor(dictionary=True)
            cursor.execute("SELECT * FROM leadger_dialer_logs WHERE reference_id = %(uuid)s", {'uuid': call_msg['lead_id']})
            log = cursor.fetchone()
            if not log:
                logging.error(f"Dialer log not found: {call_msg['lead_id']}")
                return
            seconds = call_msg['duration']
            minutes = seconds // 60
            seconds = seconds % 60
            duration = '{:02d}:{:02d}'.format(minutes, seconds)
            destination_path = call_msg['recording_url']
            cursor.execute(
                """
                UPDATE leadger_dialer_logs 
                SET click_response = %(response)s, 
                    click_recording_url = %(recording_url)s, 
                    file_path = %(file_path)s, 
                    duration = %(duration)s 
                WHERE reference_id = %(id)s
                """,
                {
                    'response': call_msg['response'],
                    'recording_url': call_msg['recording_url'],
                    'file_path': destination_path,
                    'duration': duration,
                    'id': log['reference_id']
                }
            )
            if cursor.rowcount == 0:
                logging.warning(f"No rows updated for dialer log ID: {log['id']}")
            else:
                logging.info(f"Recording updated for dialer log ID: {log['id']}")
            connection.commit()
    except Exception as ex:
        logging.error(f"process_dialer_call error: {ex}")

def process_other_call(call_msg, db_connection, env_vars):
    try:
        call_log_id = call_msg['lead_id']
        logging.info(f"Processing other call: reference_id = {call_log_id}")

        with mysql.connector.connect(**db_connection) as connection:
            cursor = connection.cursor(dictionary=True)

            # 1. Fetch log
            cursor.execute("SELECT * FROM leadger_call_logs WHERE reference_id = %(id)s", {'id': call_log_id})
            log = cursor.fetchone()
            if not log:
                logging.error(f"Call log not found: {call_log_id}")
                return

            # 2. Extract lead_id prefix (lead_id = 125000005_M8_90022_2_1 ? 125000005)
            lead_id_prefix = call_log_id.split('_')[0]

            # 3. Lookup campaign_id via lead_id prefix from leadger_leads
            cursor.execute("SELECT campaign_id FROM leadger_leads WHERE id = %s", (lead_id_prefix,))
            lead_row = cursor.fetchone()
            campaign_id = lead_row['campaign_id'] if lead_row else None

            # Optional: log missing lead/campaign lookup
            if not campaign_id:
                logging.warning(f"Campaign ID not found for lead ID prefix: {lead_id_prefix}")

            # 4. Fetch campaign (optional, if you need campaign data elsewhere)
            campaign = None
            if campaign_id:
                cursor.execute("SELECT * FROM leadger_campaigns WHERE id = %s", (campaign_id,))
                campaign = cursor.fetchone()

            # 5. Format duration
            seconds = call_msg['duration']
            minutes = seconds // 60
            seconds = seconds % 60
            duration = '{:02d}:{:02d}'.format(minutes, seconds)

            # 6. Construct filename
            filename = f"{call_msg['created_at'].replace(':', '-')}_0_{call_msg['b_party_no']}.wav"
            destination_path = call_msg['recording_url']

            # 7. Update log
            query = """
                UPDATE leadger_call_logs 
                SET updated_at=%(uploaded_at)s, 
                    
                 
                    created_by=%(uploaded_by)s, 
                   
                    
                    click_response=%(response)s, 
                    click_recording_url=%(recording_url)s 
                WHERE reference_id = %(id)s
            """
            params = {
                'uploaded_at': datetime.now(),
                             'uploaded_by': log['created_by'],
                
                 'response': call_msg['response'],
                'recording_url': call_msg['recording_url'],
                'id': call_log_id
            }

            cursor.execute(query, params)
            if cursor.rowcount == 0:
                logging.warning(f"No rows updated for log ID: {call_log_id}")
            else:
                logging.info(f"Recording updated for log ID: {call_log_id}")

            connection.commit()

    except Exception as ex:
        logging.error(f"process_other_call error: {ex}")

def process_incoming_call(call_msg, db_connection, env_vars):
    try:
        logging.info(f"Processing incoming call: ref_id = {call_msg.get('ref_id')}")
        with mysql.connector.connect(**db_connection) as connection:
            cursor = connection.cursor(dictionary=True)
            seconds = call_msg['duration']
            duration = seconds
            lead_id = call_msg['ref_id'].split('_')[0]
            a_start_time = convert_iso_to_custom_format(call_msg['a_start_time'])
            outgoing_call = convert_iso_to_custom_format(call_msg['called_time'])
            recording_url = '' if call_msg['recording_url'] == 'No Voice' else call_msg['recording_url']
            insert_query = """
                INSERT INTO leadger_incoming_vmn_logs 
                (call_id, dni, a_party_no, a_start_time, a_dial_status, cli, recording_url, duration, response, file_path)
                VALUES (%s, %s, %s, %s, '', %s, %s, %s, %s, '')
            """
            cursor.execute(insert_query, (
                call_msg['call_id'], call_msg['dni'], call_msg['a_party_no'],
                a_start_time, call_msg['cli'], recording_url, duration,
                call_msg['response']
            ))
            connection.commit()
            log_id = cursor.lastrowid

            # Call Laravel to fire the event
            trigger_laravel_event(db_connection, log_id, env_path)
    except Exception as ex:
        logging.error(f"process_incoming_call error: {ex}")

def get_laravel_paths_from_env_path(env_path):
    # Remove the trailing "/.env" to get the Laravel root
    laravel_root = os.path.dirname(env_path)
    artisan_path = os.path.join(laravel_root, "artisan")
    return artisan_path, laravel_root

def trigger_laravel_event(db_conn, log_id, env_path):
    try:
        # Get artisan path and Laravel root directory from .env path
        artisan_path, laravel_root = get_laravel_paths_from_env_path(env_path)

        # Run the Laravel artisan command with database info and log_id
        result = subprocess.run([
            "php",
            artisan_path,
            "event:fire-incoming-log",
            db_conn['database'],
            db_conn['user'],
            db_conn['password'],
            db_conn['host'],
            str(log_id)
        ],
        cwd=laravel_root,
        capture_output=True,
        text=True)

        # Print command output or error
        if result.returncode == 0:
            print(f"[{db_conn['database']}] Event fired successfully:\n{result.stdout}")
        else:
            print(f"[{db_conn['database']}] Event firing failed:\n{result.stderr}")

    except Exception as e:
        print(f"Error while calling Laravel event for {db_conn['database']}: {e}")

def convert_iso_to_custom_format(iso_str, format="%Y-%m-%d %H:%M:%S"):
    dt = datetime.fromisoformat(iso_str.replace("Z", "+00:00"))
    return dt.strftime(format)

def do_work(crm_id, db_connection, env_vars, stop_event):
    connection = pika.BlockingConnection(pika.ConnectionParameters(
        host='34.93.0.136',
        port=5672,
        credentials=pika.PlainCredentials('guest', 'guest')
    ))
    channel = connection.channel()
    channel.queue_declare(queue=crm_id, durable=True)

    def callback(ch, method, properties, body):
        if stop_event.is_set():
            return
        call_msg = json.loads(body.decode())
        print(f"[{crm_id}] Received message: {call_msg}")
        logging.info(f"[{crm_id}] Received message: {call_msg}")
        process_call_recording(call_msg, db_connection, env_vars)

    channel.basic_consume(queue=crm_id, on_message_callback=callback, auto_ack=True)

    while not stop_event.is_set():
        try:
            channel.start_consuming()
        except Exception as e:
            logging.error(f"do_work error: {e}")
            channel.stop_consuming()

    connection.close()

if __name__ == "__main__":
    config = ConfigParser()
    config.read('config.ini')
    print("Parsed ENV paths from config.ini:")
    print(list(config['path'].items()))

    crm_ids_all = []
    db_connections_all = []

    for key, env_path in config['path'].items():
        env_vars = load_laravel_env(env_path)
        crm_ids, db_connections = get_crm_ids_and_db_connections(env_vars)
        crm_ids_all.extend(crm_ids)
        db_connections_all.extend(db_connections)
        print("CRM IDs found:", crm_ids)
        print("DB Connections found:", db_connections)

    stop_event = Event()
    threads = []

    for crm_id, db_connection in zip(crm_ids_all, db_connections_all):
        thread = Thread(target=do_work, args=(crm_id, db_connection, env_vars, stop_event))
        thread.start()
        threads.append(thread)

    try:
        for thread in threads:
            thread.join()
    except KeyboardInterrupt:
        stop_event.set()
        for thread in threads:
            thread.join()
    print("Main thread exiting")