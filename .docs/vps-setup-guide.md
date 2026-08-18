# VPS Setup Guide - Toko Online Laravel

## Spesifikasi Target

| Komponen | Spec                                    |
| -------- | --------------------------------------- |
| RAM      | 2 GB                                    |
| CPU      | 2 vCPU                                  |
| Disk     | 20 GB SSD                               |
| OS       | Ubuntu 22.04 LTS                        |
| Location | Singapore (recommended untuk Indonesia) |

## Budget

- VPS: ~Rp 155.900/bulan (Hostinger KVM 2)
- Domain: ~Rp 150.000/tahun
- SSL: Gratis (Let's Encrypt)

---

## Daftar Isi

1. [Initial Server Setup](#1-initial-server-setup)
2. [SSH Key Setup](#2-ssh-key-setup)
3. [Firewall Configuration](#3-firewall-configuration)
4. [Nginx Installation](#4-nginx-installation)
5. [PHP 8.2 Installation](#5-php-82-installation)
6. [MySQL 8.0 Installation](#6-mysql-80-installation)
7. [Composer Installation](#7-composer-installation)
8. [Laravel Deployment](#8-laravel-deployment)
9. [Nginx Configuration](#9-nginx-configuration)
10. [Queue Setup](#10-queue-setup)
11. [Scheduler Setup](#11-scheduler-setup)
12. [SSL Setup (Let's Encrypt)](#12-ssl-setup-lets-encrypt)
13. [Optimization for 2GB RAM](#13-optimization-for-2gb-ram)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Initial Server Setup

### 1.1 Login sebagai Root

```bash
ssh root@IP_VPS
```

### 1.2 Update System

```bash
apt update && apt upgrade -y
```

### 1.3 Buat User Deploy

```bash
adduser deploy
```

Isi password (min 8 karakter). Semua field lain tekan Enter untuk skip.

### 1.4 Beri Sudo Access

```bash
usermod -aG sudo deploy
```

### 1.5 Verify Sudo Access

```bash
getent group sudo
# Output: sudo:x:27:deploy

su - deploy
sudo whoami
# Output: root (berarti berhasil)
```

---

## 2. SSH Key Setup

### 2.1 Generate SSH Key di Mac (Lokal)

Buka Terminal baru di Mac kamu (bukan di VPS):

```bash
ssh-keygen -t ed25519
```

Tekan Enter untuk semua pertanyaan (default path, no passphrase).

### 2.2 Copy Public Key ke VPS

```bash
ssh-copy-id -i ~/.ssh/id_ed25519.pub deploy@IP_VPS
```

Masukkan password user `deploy` saat diminta.

### 2.3 Test Login

```bash
ssh deploy@IP_VPS
```

Harus login langsung tanpa minta password.

### 2.4 (Optional) Disable Password Login

```bash
sudo nano /etc/ssh/sshd_config
```

Ubah baris berikut:

```
PasswordAuthentication no
```

Restart SSH:

```bash
sudo systemctl restart sshd
```

---

## 3. Firewall Configuration

### 3.1 Enable UFW

```bash
sudo ufw enable
```

### 3.2 Allow Port SSH

```bash
# Jika pakai port default 22
sudo ufw allow 22/tcp

# Jika pakai port custom (misal 2222)
sudo ufw allow 2222/tcp
```

### 3.3 Allow HTTP/HTTPS

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### 3.4 Check Status

```bash
sudo ufw status verbose
```

---

## 4. Nginx Installation

### 4.1 Install Nginx

```bash
sudo apt update
sudo apt install nginx -y
```

### 4.2 Test Nginx

Buka browser, akses `http://IP_VPS`. Harus muncul halaman default Nginx.

---

## 5. PHP 8.2 Installation

### pre req

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
```

### 5.1 Install PHP 8.2 + Extensions

```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl unzip php8.2-gd
```

### 5.2 Konfigurasi PHP Memory Limit

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Cari dan ubah:

```
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 60
```

Simpan (Ctrl+X, Y, Enter).

### 5.3 Konfigurasi PHP-FPM Pool

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Cari section `[www]` dan ubah:

```
pm.max_children = 4
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

### 5.4 Restart PHP-FPM

```bash
sudo systemctl restart php8.2-fpm
```

---

## 6. MySQL 8.0 Installation

### 6.1 Install MySQL

```bash
sudo apt install mysql-server -y
```

### 6.2 Secure Installation

```bash
sudo mysql_secure_installation
```

Jawaban:

```
Validate password component? N
Remove anonymous users? Y
Disallow root login remotely? Y
Remove test database? Y
Reload privilege tables? Y
```

### 6.3 Konfigurasi MySQL untuk 2GB RAM

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Dalam section `[mysqld]`, tambahkan/ubah:

```ini
[mysqld]
innodb_buffer_pool_size = 512M
max_connections = 75
query_cache_size = 32M
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

### 6.4 Buat Database dan User

```bash
sudo mysql
```

```sql
CREATE DATABASE tokoonline CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'deploy'@'localhost' IDENTIFIED BY 'db_password';
GRANT ALL PRIVILEGES ON tokoonline.* TO 'deploy'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 7. Composer Installation

### 7.1 Install Composer

```bash
curl -sS https://getcomposer.org/installer | sudo php
sudo mv composer.phar /usr/local/bin/composer
```

### 7.2 Verify

```bash
composer --version
```

---

## 8. Laravel Deployment

### Pre Req Jalankan di server:

#### 1. Buat directory dan set permission

```bash
sudo mkdir -p /var/www/html
sudo chown -R $(whoami):www-data /var/www/html
sudo chmod -R 775 /var/www/html
```

#### 2. Buat subdirectory yang diperlukan

```bash
sudo mkdir -p /var/www/html/{storage,bootstrap/cache,public,log}
sudo chmod -R 775 /var/www/html/{storage,bootstrap/cache,public}
atau jika pakai user berbeda untuk SSH (bukan deploy):
```

#### Cek user yang dipake SSH

```bash
whoami
```

##### Set ownership sesuai

```bash
sudo chown -R deploy:www-data /var/www/html
sudo chmod -R 775 /var/www/html
```

### 8.1 Buat Directory dan Clone Repo

```bash
cd /var/www/html
```

### 8.2 Clone Project (Jika ada Git repo)

```bash
git clone https://github.com/USERNAME/REPO.git /tmp/tokoonline
rsync -av --delete /tmp/tokoonline/ /var/www/html/
```

### 8.3 Atau Upload Manual via SCP

Dari Mac kamu:

```bash
scp -r /path/to/local/project/* deploy@IP_VPS:/var/www/html/
```

### 8.4 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 8.5 Setup Environment

```bash
cp .env.example .env
```

### 8.6 Edit .env

```bash
nano .env
```

Sesuaikan:

```
APP_NAME="Toko Online"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tokoonline
DB_USERNAME=deploy
DB_PASSWORD=ISI_PASSWORD_DB

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

### 8.7 Generate Key

```bash
php artisan key:generate
```

### 8.8 Run Migration

```bash
php artisan migrate --force
```

### 8.9 Set Permission

```bash
sudo chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build
sudo chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build
```

---

## 9. Nginx Configuration

### 9.1 Buat Config

```bash
sudo nano /etc/nginx/sites-available/tokoonline
```

```nginx
server {
    listen 80;
    server_name domain.com www.domain.com;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 9.2 Enable Config

```bash
sudo ln -s /etc/nginx/sites-available/tokoonline /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
```

### 9.3 Test Config

```bash
sudo nginx -t
```

### 9.4 Reload Nginx

```bash
sudo systemctl reload nginx
```

---

## 10. Queue Setup

### 10.1 Pastikan Queue Connection Database

Edit `.env`:

```bash
nano /var/www/html/.env
```

```env
QUEUE_CONNECTION=database
```

### 10.2 Buat Tabel Queue (Jika belum ada)

```bash
php artisan queue:table
php artisan migrate
```

### 10.3 Rekomendasi: Jalankan Queue Worker dengan Supervisor atau systemd

Queue worker tidak ideal dijalankan via cron per menit. Untuk production, gunakan process manager agar worker selalu hidup.

Contoh paling sederhana dengan Supervisor:

```bash
sudo apt install supervisor -y
sudo nano /etc/supervisor/conf.d/tokoonline-worker.conf
```

```ini
[program:tokoonline-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/html/artisan queue:work --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

Aktifkan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### 10.4 Fallback: Cron untuk Queue Worker

Jika belum memakai Supervisor, cron fallback masih bisa dipakai, tetapi hanya sementara:

```bash
crontab -e
```

```cron
* * * * * cd /var/www/html && php artisan queue:work --stop-when-empty --tries=3 --timeout=60 >> /dev/null 2>&1
```

### 10.5 Restart Queue Worker (Manual untuk test)

```bash
php artisan queue:work
```

---

## 11. Scheduler Setup

### 11.1 Setup Cron

```bash
crontab -e
```

Tambah baris:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### 11.2 Verify Scheduler Running

```bash
php artisan schedule:list
```

Harus muncul list scheduled tasks (misal `orders:check-expiry` every 15 minutes).

---

## 12. SSL Setup (Let's Encrypt)

### 12.1 Install Certbot

```bash
sudo apt install certbot python3-certbot-nginx -y
```

### 12.2 Generate SSL

```bash
sudo certbot --nginx -d domain.com -d www.domain.com
```

Ikuti instruksi:

- Masukkan email
- Agree terms
- Choose redirect (2) untuk redirect HTTP ke HTTPS

### 12.3 Auto-Renew SSL

```bash
sudo systemctl enable certbot.timer
```

### 12.4 Test Renewal

```bash
sudo certbot renew --dry-run
```

---

## 13. Optimization for 2GB RAM

### 13.1 PHP-FPM Optimization

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

```
pm.max_children = 3
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 2
```

### 13.2 MySQL Optimization

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 384M
max_connections = 50
key_buffer_size = 32M
query_cache_limit = 1M
query_cache_size = 16M
```

### 13.3 Disable Unnecessary Services

```bash
# Stop services yang tidak perlu
sudo systemctl stop snapd
sudo systemctl disable snapd

# Optimalkan SWAP (jika RAM hampir penuh)
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

Tambahkan ke `/etc/fstab`:

```
/swapfile none swap sw 0 0
```

### 13.4 Laravel Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 14. Troubleshooting

### 14.1 Cek Log Error

```bash
# Nginx error log
sudo tail -f /var/log/nginx/error.log

# Laravel log
sudo tail -f /var/www/html/storage/logs/laravel.log

# Queue log
sudo tail -f /var/www/html/storage/logs/worker.log
```

### 14.2 Cek Status Semua Service Utama

Setelah VPS reboot atau website error, login melalui SSH lalu jalankan:

```bash
# Ringkasan service yang gagal dijalankan systemd
sudo systemctl --failed

# Status setiap service yang dipakai aplikasi
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status supervisor

# Status worker Laravel yang dikelola Supervisor
sudo supervisorctl status

# Pastikan cron service aktif (scheduler Laravel dijalankan oleh cron)
sudo systemctl status cron
```

Service berstatus `active (running)` berarti berjalan normal. Tekan `q` untuk keluar dari tampilan `systemctl status`.

Untuk pemeriksaan cepat tanpa log detail:

```bash
sudo systemctl is-active nginx php8.2-fpm mysql supervisor cron
sudo systemctl is-enabled nginx php8.2-fpm mysql supervisor cron
```

`enabled` berarti service akan otomatis berjalan kembali setelah VPS reboot. Jika service utama belum enabled, aktifkan:

```bash
sudo systemctl enable nginx php8.2-fpm mysql supervisor cron
```

### 14.3 Jalankan atau Restart Service

```bash
# Jalankan service yang sedang mati
sudo systemctl start nginx
sudo systemctl start php8.2-fpm
sudo systemctl start mysql
sudo systemctl start supervisor
sudo systemctl start cron

# Restart Nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart MySQL
sudo systemctl restart mysql

# Terapkan konfigurasi Nginx tanpa memutus koneksi aktif
sudo nginx -t && sudo systemctl reload nginx

# Muat ulang dan restart worker Laravel dari Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart tokoonline-worker:*

# Clear Laravel cache
cd /var/www/html
php artisan optimize:clear
```

Jangan menjalankan `php artisan queue:work` langsung di terminal sebagai perbaikan permanen; worker tersebut berhenti saat sesi SSH ditutup. Gunakan Supervisor seperti di atas.

### 14.4 Lihat Penyebab Service Gagal

```bash
# Log systemd sejak boot terakhir; ganti nama service bila perlu
sudo journalctl -u nginx -b --no-pager -n 100
sudo journalctl -u php8.2-fpm -b --no-pager -n 100
sudo journalctl -u mysql -b --no-pager -n 100
sudo journalctl -u supervisor -b --no-pager -n 100

# Pantau log service secara real-time
sudo journalctl -fu nginx
```

### 14.5 Cek PHP-FPM Status

```bash
sudo systemctl status php8.2-fpm
```

### 14.6 Cek Port yang Digunakan

```bash
sudo netstat -tlnp
```

### 14.7 Cek RAM Usage

```bash
free -h
```

### 14.8 Cek Disk Usage

```bash
df -h
```

---

## Checklist Final

| Task                 | Status |
| -------------------- | ------ |
| SSH Key Setup        | ⬜     |
| Firewall Enabled     | ⬜     |
| Nginx Running        | ⬜     |
| PHP 8.2 + Extensions | ⬜     |
| MySQL 8.0 + Database | ⬜     |
| Composer Installed   | ⬜     |
| Laravel Deployed     | ⬜     |
| Nginx Configured     | ⬜     |
| SSL Enabled          | ⬜     |
| Queue Working        | ⬜     |
| Scheduler Running    | ⬜     |
| Permission Correct   | ⬜     |

---

## Deploy Routine (Update Code)

```bash
# Pull latest code
cd /var/www/html
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear and rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrate database if needed
php artisan migrate --force

# Restart queue worker
sudo -u www-data php artisan queue:restart
```

---

## Emergency Commands

```bash
# Jika VPS tidak respons
# → Login via console/VNC dari provider panel

# Jika Nginx crash
sudo systemctl restart nginx

# Jika PHP error
sudo systemctl restart php8.2-fpm

# Jika MySQL error
sudo systemctl restart mysql

# Jika server overload
top
htop
free -h
```

---

## Support & Dokumentasi

- Laravel Docs: https://laravel.com/docs
- Nginx Docs: https://nginx.org/en/docs/
- DigitalOcean Tutorials: https://www.digitalocean.com/community/tutorials
