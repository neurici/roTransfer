# Instalare roTransfer

Acest ghid descrie instalarea aplicației roTransfer pe un hosting gratuit (ex: InfinityFree) sau local.

---

## 📦 Cerințe

- PHP 7.4 sau mai nou
- Apache Web Server
- Acces FTP / File Manager
- Hosting cu suport pentru PHP

---

## 🗂️ Pași de instalare

### 1️⃣ Descărcare
Clonează repository-ul sau descarcă arhiva ZIP.

### 2️⃣ Urcare fișiere
Urcă conținutul în:
- `public_html/rotransfer`
- sau `htdocs/rotransfer` (de obicei apar epe hosting-urile gratuite)

### 3️⃣ Structură necesară
Asigură-te că există:

```
storage/
├── transfers/
├── tmp/
├── logs/
└── db.sqlite
```

> Notă: creează folderele din File Manager dacă nu există.

---

### 4️⃣ Permisiuni
Setează permisiuni:
- `storage/` → 755 / 777
- `storage/transfers/` → 777
- `storage/tmp/` → 777
- `storage/logs/` → 777
- `storage/db.sqlite` → 666 / 777

---

### 5️⃣ Configurare (`config.php`)

```php
define('BASE_PATH', __DIR__);
define('STORAGE_PATH', BASE_PATH . '/storage');
define('TRANSFERS_PATH', STORAGE_PATH . '/transfers');
define('LOG_PATH', STORAGE_PATH . '/logs');
define('DB_PATH', STORAGE_PATH . '/db.sqlite');

date_default_timezone_set('Europe/Bucharest');
```

Configurează și datele SMTP conform providerului tău.

---

## ✅ Testare

- Accesează aplicația în browser
- Creează un transfer de test
- Verifică `storage/logs/mail.log`
- Accesează `log.php` (doar ca admin)

---

## 🧹 Recomandări

- Protejează `admin.php` cu parolă
- Nu expune `log.php` public
- Fă backup periodic la `storage/`
---

## 🚨 Configurare de securitate OBLIGATORIE (.htaccess)

Pentru securitatea aplicației, **ACEASTĂ CONFIGURARE ESTE OBLIGATORIE**.  
Fără ea, fișiere sensibile pot fi accesate public.

Adaugă următoarele reguli în fișierul `.htaccess` din directorul aplicației:

```apache
Options -Indexes

<FilesMatch "\.(sqlite|log|ini)$">
  Require all denied
</FilesMatch>

<Files "config.php">
  Require all denied
</Files>
```
❗ Această configurare:

    dezactivează listarea directoarelor

    blochează accesul la baza de date SQLite

    blochează fișierele de log

    protejează fișierul config.php

⚠️ NU sări peste acest pas. Este esențial pentru securitatea aplicației.

markdown
