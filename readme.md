# 📁 Sistem Electronic de Arhivare Documente

Sistem profesional de arhivare electronică multi-tenant pentru companii, dezvoltat în PHP, MySQL, JavaScript și Bootstrap 5.

## 🎯 Caracteristici Principale

### ✨ Funcționalități Complete

- **Multi-Tenant Architecture** - Fiecare firmă are propriul spațiu izolat
- **Gestionare Departamente** - Organizare pe structură organizațională
- **Dosare Ierarhice** - Contracte, documente angajați, facturi, etc.
- **Sistem de Taguri** - Etichetare și organizare flexibilă
- **Căutare Avansată** - Full-text search cu filtre multiple
- **Upload Drag & Drop** - Interfață modernă de încărcare
- **Versiuni Documente** - Tracking versiuni și istoric
- **Permisiuni Granulare** - Control detaliat acces
- **Audit Trail Complet** - Logare toate acțiunile
- **Download/Print** - Descărcare și tipărire cu tracking
- **Deduplicare** - Detectare documente duplicate (SHA-256)
- **Dashboard Analytics** - Statistici și rapoarte

### 👥 Roluri și Permisiuni

1. **SuperAdmin** - Gestionare platformă și companii
2. **Admin** - Administrator companie, control total
3. **Manager** - Gestionare departament specific
4. **User** - Utilizator standard cu permisiuni limitate

## 🛠️ Cerințe Tehnice

### Server Requirements

- **PHP**: 8.1 sau superior
- **MySQL/MariaDB**: 8.0+ / 10.5+
- **Apache/Nginx**: cu mod_rewrite activat
- **Extensii PHP necesare**:
  - PDO
  - pdo_mysql
  - mbstring
  - fileinfo
  - gd (pentru manipulare imagini)
  - zip
  - openssl

### Recomandări

- Minimum 2GB RAM
- 50GB spațiu disc (pentru stocare documente)
- SSL Certificate (HTTPS)
- Backup automat configurat

## 📦 Instalare

### Pasul 1: Download și Extragere

```bash
# Clone repository sau descarcă ZIP
git clone https://github.com/yourusername/arhiva-documente.git
cd arhiva-documente

# Sau extrage arhiva
unzip arhiva-documente.zip
cd arhiva-documente
```

### Pasul 2: Configurare Directoare

```bash
# Creează directoarele necesare
mkdir -p storage/documents
mkdir -p storage/temp
mkdir -p storage/backups
mkdir -p storage/logs

# Setează permisiuni (Linux/Mac)
chmod -R 755 storage
chown -R www-data:www-data storage  # sau utilizatorul web server-ului
```

### Pasul 3: Creare Bază de Date

```bash
# Conectează-te la MySQL
mysql -u root -p

# Creează baza de date
CREATE DATABASE arhiva_documente CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Creează utilizator (opțional)
CREATE USER 'arhiva_user'@'localhost' IDENTIFIED BY 'parola_securizata';
GRANT ALL PRIVILEGES ON arhiva_documente.* TO 'arhiva_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Pasul 4: Import Schema

```bash
# Importă schema din database/schema.sql
mysql -u root -p arhiva_documente < database/schema.sql
```

### Pasul 5: Configurare Aplicație

Editează fișierul `config/config.php`:

```php
// Setări bază de date
define('DB_HOST', 'localhost');
define('DB_NAME', 'arhiva_documente');
define('DB_USER', 'arhiva_user');
define('DB_PASS', 'parola_securizata');

// URL aplicație
define('APP_URL', 'http://localhost/arhiva');  // sau domeniul tău

// Căi
define('STORAGE_PATH', '/var/www/arhiva/storage');  // cale absolută

// Setări upload
define('MAX_FILE_SIZE', 52428800); // 50MB

// Debug (în producție setează FALSE)
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);
```

### Pasul 6: Configurare Virtual Host

#### Apache (.htaccess deja inclus)

```apache
<VirtualHost *:80>
    ServerName arhiva.example.com
    DocumentRoot /var/www/arhiva/public
    
    <Directory /var/www/arhiva/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/arhiva_error.log
    CustomLog ${APACHE_LOG_DIR}/arhiva_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name arhiva.example.com;
    root /var/www/arhiva/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Pasul 7: Prima Autentificare

1. Accesează aplicația în browser: `http://localhost/arhiva`
2. Click pe **Login**
3. Autentifică-te ca SuperAdmin:
   - **Username**: `superadmin`
   - **Email**: `superadmin@arhiva.ro`
   - **Password**: `password123` (⚠️ SCHIMBĂ IMEDIAT!)

## 🚀 Utilizare

### Crearea Primei Companii (ca SuperAdmin)

1. Login ca SuperAdmin
2. Navighează la **Companii** → **Adaugă Companie**
3. Completează detaliile companiei
4. Setează limita de stocare și utilizatori
5. Creează cont Admin pentru companie

### Configurare Companie (ca Admin)

1. Login cu contul de Admin
2. **Departamente**: Creează structura organizațională
3. **Dosare**: Definește tipurile de dosare (Contracte, HR, etc.)
4. **Taguri**: Adaugă taguri frecvent folosite
5. **Utilizatori**: Invită membrii echipei

### Upload Documente

1. Navighează la **Documente** → **Încarcă Document**
2. Drag & Drop sau selectează fișierul
3. Completează:
   - Titlu (obligatoriu)
   - Descriere
   - Departament
   - Dosar
   - Taguri
   - Dată document
4. Click **Încarcă**

### Căutare Documente

Filtrare după:
- Text (titlu, descriere, număr document)
- Departament
- Dosar
- Interval de date
- Taguri

## 📁 Structura Proiectului

```
arhiva-documente/
├── config/
│   └── config.php              # Configurație principală
├── includes/
│   ├── classes/
│   │   ├── Database.php        # Wrapper PDO
│   │   └── DocumentManager.php # Gestionare documente
│   └── functions/
│       ├── helpers.php         # Funcții helper
│       ├── security.php        # Funcții securitate
│       └── validation.php      # Validări
├── modules/
│   ├── superadmin/
│   │   ├── dashboard.php
│   │   └── companies.php
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── departments.php
│   │   ├── documents.php
│   │   ├── folders.php
│   │   ├── tags.php
│   │   └── users.php
│   └── user/
│       └── my_documents.php
├── public/
│   ├── index.php               # Landing page
│   ├── login.php               # Autentificare
│   ├── register.php            # Înregistrare
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── .htaccess
├── storage/
│   ├── documents/              # Documente încărcate
│   │   └── {company_id}/
│   │       └── {year}/
│   │           └── {month}/
│   ├── temp/                   # Fișiere temporare
│   ├── backups/                # Backup-uri
│   └── logs/                   # Log-uri aplicație
├── database/
│   ├── schema.sql              # Schema completă
│   └── migrations/             # Migrări viitoare
└── README.md
```

## 🔒 Securitate

### Măsuri Implementate

- ✅ **Prepared Statements** - Protecție SQL Injection
- ✅ **CSRF Tokens** - Protecție Cross-Site Request Forgery
- ✅ **Password Hashing** - Bcrypt cu cost 12
- ✅ **File Validation** - Verificare extensii și MIME types
- ✅ **Input Sanitization** - Curățare toate input-urile
- ✅ **Session Security** - Timeout și regenerare ID
- ✅ **Multi-Tenant Isolation** - Separare completă date
- ✅ **Audit Logging** - Tracking toate acțiunile
- ✅ **File Deduplication** - SHA-256 hash checking

### Recomandări Producție

1. **Schimbă parola SuperAdmin** imediat
2. **Activează HTTPS** (SSL/TLS)
3. **Configurează Firewall**
4. **Backup automat** zilnic
5. **Monitorizare** log-uri erori
6. **Rate Limiting** pentru API
7. **Actualizări** regulate securitate

## 🔧 Configurări Avansate

### Upload Limită

Editează `config/config.php`:
```php
define('MAX_FILE_SIZE', 104857600); // 100MB
```

Și configurează PHP:
```ini
upload_max_filesize = 100M
post_max_size = 105M
max_execution_time = 300
```

### Email Notifications

Configurează SMTP în `config/config.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### Backup Automat

Creează cron job:
```bash
# Backup zilnic la 02:00
0 2 * * * /usr/bin/php /var/www/arhiva/scripts/backup.php
```

## 📊 Monitorizare și Mentenanță

### Log Files

```bash
# Verifică log-uri erori
tail -f storage/logs/error_$(date +%Y-%m-%d).log

# Verifică log-uri activitate
tail -f storage/logs/activity_$(date +%Y-%m-%d).log
```

### Curățare Fișiere Temporare

```bash
# Adaugă în cron (zilnic)
0 3 * * * find /var/www/arhiva/storage/temp -type f -mtime +1 -delete
```

### Optimizare Bază de Date

```sql
-- Rulează periodic
OPTIMIZE TABLE documents;
OPTIMIZE TABLE activity_logs;
ANALYZE TABLE documents;
```

## 🐛 Troubleshooting

### Probleme Comune

**1. Eroare "Permission Denied" la upload**
```bash
chmod -R 755 storage
chown -R www-data:www-data storage
```

**2. Documente nu apar**
- Verifică conexiunea la bază de date
- Verifică `company_id` în sesiune
- Verifică log-uri erori

**3. Căutare nu funcționează**
```sql
-- Verifică index full-text
SHOW INDEX FROM documents WHERE Key_name = 'idx_fulltext_search';
```

**4. Upload eșuează**
- Verifică `MAX_FILE_SIZE` în PHP
- Verifică spațiu disc disponibil
- Verifică permisiuni directoare

## 📝 TODO / Îmbunătățiri Viitoare

- [ ] OCR pentru documente scanate
- [ ] Preview PDF în browser
- [ ] Integrare email (notificări)
- [ ] API REST pentru integrări
- [ ] Export Excel rapoarte
- [ ] Workflows approval documente
- [ ] Semnătură electronică
- [ ] Mobile app
- [ ] Two-Factor Authentication (2FA)
- [ ] Integrare cloud storage (AWS S3, Google Drive)

## 📄 Licență

Acest proiect este open-source și disponibil sub licența MIT.

## 🤝 Contribuții

Contribuțiile sunt binevenite! Pentru modificări majore:
1. Fork repository
2. Creează branch pentru feature (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Deschide Pull Request

## 📞 Support

Pentru întrebări sau probleme:
- 📧 Email: support@arhiva.ro
- 📱 Telefon: +40 721 234 567
- 🌐 Website: https://arhiva.ro

---

**Dezvoltat cu ❤️ pentru companiile românești**

Versiunea 1.0.0 - Octombrie 2025

---

## 🚀 Quickstart GitHub (Windows PowerShell)

Pași rapizi pentru a urca proiectul pe GitHub:

```powershell
cd C:\wamp64\www\document-archive
git init
git config user.name "Numele Tău"
git config user.email "email@domeniu.ro"
$REMOTE = "https://github.com/username/document-archive.git"  # înlocuiește
git remote add origin $REMOTE
git add .
git commit -m "chore: initial import"
git branch -M main
git push -u origin main
```

Vezi și `docs/GIT_PUSH_WINDOWS.md` pentru detalii.

## ✅ Conformitate & Unelte
- Ghostscript (PDF→PDF/A) – opțional, configurabil în SuperAdmin → Sistem
- veraPDF (validare PDF/A) – opțional; raport inclus la export dacă e activat

## 🌐 Deploy Hostico (rezumat)
- Urcă proiectul în `public_html/document-archive/` și setează document root spre `public/`
- Editează `config/config.php` (APP_URL, DB_*, STORAGE_PATH)
- Creează folderele din `storage/*` și acordă permisiuni de scriere
- Importă `database/schema.sql` în MySQL

Detalii complete: `docs/DEPLOY_HOSTICO.md`.