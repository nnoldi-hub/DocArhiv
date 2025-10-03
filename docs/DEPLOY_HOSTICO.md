# Deploy pe Hostico (Shared Hosting)

Acest ghid prezintă pașii de publicare pe Hostico sau alt shared hosting similar.

## 1) Cerințe
- PHP 8.1+
- MySQL 8.0+/MariaDB 10.5+
- Acces cPanel (sau echivalent)

## 2) Structură webroot
Pe shared hosting, public webroot este de regulă `public_html/`. Recomandare:
- Urcă toate fișierele proiectului într-un folder, ex: `public_html/document-archive/`
- Setează document root spre subfolderul `public/` dacă platforma permite (Application Manager, Domain/Subdomain root)
  - ex: `public_html/document-archive/public`
- Dacă nu poți seta document root, folosește un subdomeniu care pointează direct la `public/`.

## 3) Upload fișiere
- Comprimă local proiectul (exclus storage mare) sau folosește Git deploy
- Urcă arhiva și extrage în `public_html/document-archive/`

## 4) Baza de date
- Creează DB și user din cPanel → MySQL® Databases
- Importă `database/schema.sql` din phpMyAdmin
- Notează credențialele DB (host, dbname, user, pass)

## 5) Configurare aplicație
Editează `config/config.php`:
- APP_URL: `https://domeniu.tld/document-archive` (sau subdomeniu)
- STORAGE_PATH: cale absolută pe hosting (de ex: `/home/USER/document-archive/storage`)
- DB_*
- Dezactivează DEBUG în producție

Creează directoarele (dacă lipsesc):
- `storage/documents`, `storage/temp`, `storage/backups`, `storage/logs`, `storage/exports`

Permisiuni:
- În cPanel, setează 755 pentru foldere și 644 pentru fișiere
- Asigură-te că PHP are drept de scriere în `storage/*`

## 6) Ghostscript și veraPDF (opțional)
Pe shared hosting s-ar putea să nu ai acces la instalare sistem:
- Ghostscript: dacă nu e disponibil, conversia PDF/A nu va rula; aplicația va continua fără conversie
- veraPDF: la fel, validarea e opțională
- Poți lăsa conversia la upload dezactivată în `SuperAdmin → Sistem` și folosi conversia locală la export (dacă ai un worker extern)

## 7) Verificări după deploy
- Accesează `APP_URL`
- Autentificare SuperAdmin
- Încarcă un fișier mic, verifică loguri
- Generează un export și descarcă arhiva

## 8) Git deploy (opțional)
- Creează repo privat pe GitHub
- Pe Hostico, folosește Git Version Control (dacă e disponibil) sau pull cu SSH în folderul proiectului
- Update ulterior: `git pull` în server

## 9) Cron & backup
- Configurează cron jobs din cPanel pentru cleanup/backup dacă este necesar

## 10) Suport
- Hostico support pentru setări PHP/ini, document root, permisiuni
- Verifică erorile în `storage/logs/`
