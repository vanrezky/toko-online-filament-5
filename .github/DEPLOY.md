# Deployment Guide

## GitHub Actions Auto-Deploy

### Prerequisites

1. Server dengan SSH access
2. GitHub repository

### Step 1: Generate SSH Key (Jika belum punya)

```bash
# Di server, generate SSH key (tanpa passphrase untuk CI/CD)
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions

# Tambahkan public key ke authorized_keys
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys

# Tampilkan private key (untuk GitHub Secrets)
cat ~/.ssh/github_actions
```

### Step 2: Add GitHub Secrets

Di GitHub repo → Settings → Secrets and variables → Actions → New repository secret:

| Secret Name | Value |
|-------------|-------|
| `SERVER_HOST` | IP server atau domain (contoh: 192.168.1.100) |
| `SERVER_USER` | Username SSH (contoh: root) |
| `SERVER_DEPLOY_PATH` | Path deployment (contoh: /var/www/html) |
| `SSH_PRIVATE_KEY` | Isi dari file `~/.ssh/github_actions` (private key) |
| `SERVER_SSH_PORT` | Port SSH (default: 22) |

### Step 3: Add Known Hosts (Optional)

Jika server belum dikenal oleh GitHub Actions, perlu ditambahkan:

```bash
# Di server, get fingerprint SSH
ssh-keyscan -H -p 22 <SERVER_IP> >> ~/.ssh/known_hosts
```

### Step 4: Setup Server Directory

```bash
# Buat directory dan set permissions
sudo mkdir -p /var/www/html
sudo chown -R $USER:$USER /var/www/html
sudo chmod -R 755 /var/www/html

# Buat symbolic link storage (jika belum)
ln -s /var/www/html/storage/app/public /var/www/html/public/storage 2>/dev/null || true
```

### Step 5: Configure Nginx

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPTFILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Vite build assets
    location /build {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### How It Works

1. **Push ke branch `main`** → Trigger workflow
2. **Build job** → Install PHP, Node, composer, npm, build assets
3. **Deploy via Rsync** → Upload files ke server (exclude vendor, node_modules, dll)
4. **Post-deploy** → Run `config:cache`, `route:cache`, dll

### Troubleshooting

**SSH Connection Failed:**
- Verify IP, username, dan private key
- Check firewall/port SSH
- Ensure known_hosts tidak ada konflik

**Permission Denied:**
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

**Build Failed:**
- Check Node version (harus 22)
- Check PHP extensions (mbstring, xml, gd, mysql обязательны)