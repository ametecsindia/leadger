# LeadGER - Standalone Windows Runner Guide

This directory contains one-click scripts to run **LeadGER** on your Windows PC **without Antigravity or any IDE open**.

---

## 🛠 Stack Overview

| Component | Setting / Path | Notes |
|---|---|---|
| **Language** | PHP 8.3.21 | `C:\Program Files\Php\php-8.3.21\php.exe` |
| **Framework** | Laravel 13 | Core path: `C:\Lt_Leadger_source - Copy 1\core` |
| **Database** | MySQL 8.0 | Windows Service name: `MYSQL80` (Auto-starts with Windows) |
| **Queue** | Database Queue | Worker: `php artisan queue:work --sleep=3 --tries=3` |
| **Scheduler** | Laravel Scheduler | Worker: `php artisan schedule:work` |
| **Local URL** | `http://localhost:8000/admin` | Accessible via any browser |
| **Network URL** | `http://192.168.0.128:8000/admin` | Accessible across LAN |

---

## 📁 One-Click Scripts Overview

| File | Purpose |
|---|---|
| **`start-leadger.bat`** | **ONE-CLICK START**: Checks MySQL, starts Web Server, Queue Worker & Scheduler, and opens your browser. |
| **`stop-leadger.bat`** | **ONE-CLICK STOP**: Cleanly stops the web server, queue worker, scheduler, and console windows. |
| **`install-autostart.bat`** | Configures LeadGER to start automatically in the background whenever Windows boots or logs in. |
| **`uninstall-autostart.bat`** | Removes the auto-start task/registry entry. |
| **`leadger-autostart.vbs`** | Background launcher that starts services silently with no flashing command windows. |

---

## 🚀 How to Run

### Method 1: Manual 1-Click Start
1. Double-click **`start-leadger.bat`**.
2. A console window will verify PHP, MySQL, and port 8000, then start the web server, queue worker, and scheduler.
3. Your default web browser will automatically open to `http://localhost:8000/admin`.
4. You can close the startup console window; the server and background workers will continue running.

### Method 2: Automatic Background Start on Windows Logon
1. Double-click **`install-autostart.bat`**.
2. Once installed, whenever your computer boots up and you log into Windows, LeadGER will start silently in the background (zero popup windows).
3. Wait about 30 seconds after logon, then open your browser and navigate to:
   `http://localhost:8000/admin`
4. To disable auto-start at any time, double-click **`uninstall-autostart.bat`**.

---

## 🛑 How to Stop
1. Double-click **`stop-leadger.bat`**.
2. It terminates all PHP background workers and frees port 8000.

---

## 📋 Log Files (`logs\` folder)
All process outputs are routed to the `logs\` directory in the project root:
- `logs\server.log` - Web server request and error logs.
- `logs\queue.log` - Background queue worker execution logs.
- `logs\scheduler.log` - Periodic cron/scheduler tasks output.
- `core\storage\logs\laravel.log` - Laravel framework error log.

---

## ❓ Troubleshooting

### 1. Port 8000 is already in use
Run `stop-leadger.bat` to release port 8000. Alternatively, open Command Prompt and run:
```cmd
netstat -ano | findstr :8000
taskkill /PID <PID_NUMBER> /F
```

### 2. MySQL is not running
MySQL runs as the Windows service `MYSQL80`. If it is stopped:
- Press `Win + R`, type `services.msc`, locate `MYSQL80`, and click **Start**.
- Or open Command Prompt as Administrator and run:
  ```cmd
  net start MYSQL80
  ```
