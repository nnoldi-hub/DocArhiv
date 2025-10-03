# 🚀 Ghid Deployment Producție

Acest ghid vă ajută să lansați aplicația **Arhiva Documente** în producție pe un server real.

## 📋 Checklist Pre-Deployment

### ✅ Cerințe Server

- [ ] Server Linux (Ubuntu 22.04 LTS / CentOS 8+ recomandat)
- [ ] PHP 8.1 sau superior instalat
- [ ] MySQL 8.0+ sau MariaDB 10.5+ instalat
- [ ] Apache 2.4+ sau Nginx 1.18+
- [ ] SSL Certificate (Let's Encrypt recomandat)
- [ ] Minimum 2GB RAM
- [ ] Minimum 50GB SSD storage

### ✅ Securitate

- [ ] Firewall configurat (UFW/iptables)
- [ ] SSH pe port non-standard (nu 22)
- [ ] Key-based SSH authentication (nu parole)
- [ ] Fail2ban instalat și configurat
- [ ] SELinux/AppArmor activ

## 🔧 Instalare Dependințe

### Ubuntu/Debian

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Instalare Apache, PHP, MySQL
sudo apt install apache2 php8.1 php8.1-mysql php8.1-cli php8.1-mbstring \
php8.1-gd php8.1-zip php8.1-curl php8.1-xml mysql-server -y

# Activare module Apache
sudo a2enmod rewrite ssl headers expires

# Restart Apache
sudo systemctl restart apache2
```

### CentOS/RHEL

```bash
# Update sistem
sudo dnf update -y

# Instalare EPEL și Remi repository
sudo dnf install epel-release -y
sudo dnf install https://rpms.remirepo.net/enterprise/remi-release-8.rpm -y

# Activare PHP 8.1
sudo dnf module reset php
sudo dnf module enable php:remi-8.1 -y

# Instalare pachete
sudo dnf install httpd php php-mysqlnd php-gd php-zip php-mbstring \
php-xml mariadb-server -y

# Start services
sudo systemctl enable --now httpd mariadb
```

## 🗄️ Configurare Bază de Date

```bash
# Securizare MySQL
sudo mysql_secure_installation

# Conectare MySQL
sudo mysql -u root -p

# Creează baza de date și utilizator
CREATE DATABASE arhiva_documente CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'arhiva_prod'@'localhost' IDENTIFIED BY 'PAROLA_FOARTE_COMPLEXA_AICI';
GRANT ALL PRIVILEGES ON arhiva_documente.* TO 'arhiva_prod'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u arhiva_prod -p arhiva_documente < /path/to/database/schema.sql
```

## 📁 Deploy Aplicație

### 1. Upload Fișiere

```bash
# Creează director aplicație
sudo mkdir -p /var/www/arhiva

# Upload via SCP sau SFTP
scp -r arhiva-documente/* user@server:/var/www/arhiva/

# Sau folosind Git
cd /var/www
sudo git clone https://github.com/yourusername/arhiva-documente.git arhiva
```

### 2. Setare Permisiuni

```bash
# Setează owner-ul corect
sudo chown -R www-data:www-data /var/www/arhiva

# Permisiuni directoare
sudo find /var/www/arhiva -type d -exec chmod 755 {} \;

# Permisiuni fișiere
sudo find /var/www/arhiva -type f -exec chmod 644 {} \;

# Permisiuni speciale pentru storage
sudo chmod -R 775 /var/www/arhiva/storage
sudo chmod -R 775 /var/www/arhiva/storage/logs
```

### 3. Configurare Environment

```bash
# Editează config.php
sudo nano /var/www/arhiva/config/config.php
```

**Setări CRITICE pentru producție:**

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'arhiva_documente');
define('DB_USER', 'arhiva_prod');
define('DB_PASS', 'PAROLA_COMPLEXA');

// URL
define('APP_URL', 'https://arhiva.yourdomain.com');

// Paths (căi ABSOLUTE)
define('STORAGE_PATH', '/var/www/arhiva/storage');

// IMPORTANT: Dezactivare DEBUG în producție!
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);

// Securitate
define('SESSION_LIFETIME', 7200);
```

## 🌐 Configurare Apache Virtual Host

```bash
# Creează fișier virtual host
sudo nano /etc/apache2/sites-available/arhiva.conf
```

```apache
<VirtualHost *:80>
    ServerName arhiva.yourdomain.com
    ServerAlias www.arhiva.yourdomain.com
    DocumentRoot /var/www/arhiva/public
    
    <Directory /var/www/arhiva/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Protejează directoarele sensibile
    <Directory /var/www/arhiva/config>
        Require all denied
    </Directory>
    
    <Directory /var/www/arhiva/storage>
        Require all denied
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/arhiva_error.log
    CustomLog ${APACHE_LOG_DIR}/arhiva_access.log combined
    
    # Redirect la HTTPS
    RewriteEngine on
    RewriteCond %{SERVER_NAME} =arhiva.yourdomain.com [OR]
    RewriteCond %{SERVER_NAME} =www.arhiva.yourdomain.com
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>
```

```bash
# Activează site
sudo a2ensite arhiva.conf

# Dezactivează default site
sudo a2dissite 000-default.conf

# Test configurație
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

## 🔒 Configurare SSL cu Let's Encrypt

```bash
# Instalare Certbot
sudo apt install certbot python3-certbot-apache -y

# Obținere certificat SSL
sudo certbot --apache -d arhiva.yourdomain.com -d www.arhiva.yourdomain.com

# Auto-renewal (testeză)
sudo certbot renew --dry-run
```

După obținerea certificatului, Apache va crea automat configurația HTTPS.

## 🔐 Securizare Avansată

### 1. Configurare Firewall

```bash
# UFW (Ubuntu/Debian)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp  # sau portul SSH personalizat
sudo ufw enable

# Firewalld (CentOS/RHEL)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 2. Fail2Ban pentru protecție brute-force

```bash
# Instalare
sudo apt install fail2ban -y

# Configurare pentru Apache
sudo nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/error.log
maxretry = 5
bantime = 3600

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/access.log
maxretry = 2
bantime = 86400
```

```bash
# Restart Fail2Ban
sudo systemctl restart fail2ban
```

### 3. Hardening PHP

```bash
sudo nano /etc/php/8.1/apache2/php.ini
```

```ini
; Dezactivează funcții periculoase
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Ascunde versiunea PHP
expose_php = Off

; Limită upload
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 300
memory_limit = 256M

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_only_cookies = 1
```

```bash
sudo systemctl restart apache2
```

## 💾 Configurare Backup Automat

### 1. Script Backup

```bash
sudo nano /usr/local/bin/arhiva-backup.sh
```

```bash
#!/bin/bash

# Configurare
BACKUP_DIR="/var/backups/arhiva"
DB_NAME="arhiva_documente"
DB_USER="arhiva_prod"
DB_PASS="PAROLA"
APP_DIR="/var/www/arhiva"
RETENTION_DAYS=30

# Creare director backup
mkdir -p $BACKUP_DIR

# Nume fișier cu timestamp
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/arhiva_backup_$DATE"

# Backup bază de date
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > "${BACKUP_FILE}_db.sql.gz"

# Backup fișiere (doar storage)
tar -czf "${BACKUP_FILE}_files.tar.gz" -C $APP_DIR storage/documents

# Șterge backup-uri mai vechi de RETENTION_DAYS
find $BACKUP_DIR -name "arhiva_backup_*" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $BACKUP_FILE"
```

```bash
# Permisiuni
sudo chmod +x /usr/local/bin/arhiva-backup.sh

# Test
sudo /usr/local/bin/arhiva-backup.sh
```

### 2. Cron Job pentru Backup Zilnic

```bash
sudo crontab -e
```

```cron
# Backup zilnic la 02:00 AM
0 2 * * * /usr/local/bin/arhiva-backup.sh >> /var/log/arhiva-backup.log 2>&1

# Curățare fișiere temporare
0 3 * * * find /var/www/arhiva/storage/temp -type f -mtime +1 -delete

# Optimizare bază de date (săptămânal)
0 4 * * 0 mysqlcheck -u arhiva_prod -pPAROLA --optimize arhiva_documente
```

## 📊 Monitorizare și Logging

### 1. Monitorizare Server

```bash
# Instalare htop pentru monitorizare
sudo apt install htop -y

# Monitorizare spațiu disc
df -h

# Monitorizare MySQL
sudo mysq