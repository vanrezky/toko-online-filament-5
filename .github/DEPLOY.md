# Deployment Guide

## GitHub Actions Auto-Deploy

### Prerequisites

1. Server dengan SSH access + sudo privileges
2. GitHub repository
3. rsync terinstall di server (`sudo apt install rsync`)

### Step 1: Generate SSH Key

```bash
# Di lokal, generate SSH key (tanpa passphrase untuk CI/CD)
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions

# Copy public key ke server
ssh-copy-id -i ~/.ssh/github_actions.pub deploy@SERVER_IP

# Tampilkan private key (untuk GitHub Secrets)
cat ~/.ssh/github_actions
```

### Step 2: Add GitHub Secrets

Di GitHub repo -> Settings -> Secrets and variables -> Actions -> New repository secret:

| Secret Name | Value |
|-------------|-------|
| `SERVER_HOST` | IP server atau domain |
| `SERVER_USER` | Username SSH (contoh: deploy) |
| `SERVER_SSH_PORT` | Port SSH (default: 22) |
| `SSH_PRIVATE_KEY` | Private key SSH untuk CI/CD |
| `APP_KEY` | Laravel APP_KEY (base64:Ci...) |
| `APP_ENV` | production |
| `APP_DEBUG` | false |
| `APP_URL` | https://yourdomain.com |
| `DB_HOST` | Database host |
| `DB_PORT` | 3306 |
| `DB_DATABASE` | Nama database |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |
| `MAIL_HOST` | SMTP host |
| `MAIL_PORT` | 587 |
| `MAIL_USERNAME` | SMTP user |
| `MAIL_PASSWORD` | SMTP password |
| `MAIL_FROM_ADDRESS` | noreply@yourdomain.com |

### Step 3: Setup Server

```bash
# Install rsync (jika belum ada)
sudo apt install -y rsync

# Buat directory dan set permissions
sudo mkdir -p /var/www/html
sudo chown -R deploy:www-data /var/www/html
sudo chmod -R 775 /var/www/html

# Buat symbolic link storage
ln -s /var/www/html/storage/app/public /var/www/html/public/storage 2>/dev/null || true
```

### Step 4: Configure Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location /build {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### How It Works

1. **Push ke branch `main`** -> Trigger workflow
2. **Build** -> Install PHP/Node deps, build Vite assets
3. **Sync via rsync** -> Upload incremental files ke server
4. **Deploy via SSH** -> .env, migrate, optimize, permission fix
5. **Health check** -> Verify HTTP response dari app
6. **Notify** -> Log deployment status

### Troubleshooting

**SSH Connection Failed:**
- Verify IP, username, dan private key
- Check firewall/port SSH
- Run `ssh-keyscan -H -p 22 <SERVER_IP>` locally for debugging

**Permission Denied:**
```bash
sudo chown -R deploy:www-data /var/www/html
sudo chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build
```

**Build Failed:**
- Check Node version (harus 22)
- Check PHP extensions (mbstring, xml, gd, mysql wajib)

**rsync not found:**
```bash
sudo apt install -y rsync
```