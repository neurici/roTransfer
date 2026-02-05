<?php
date_default_timezone_set('Europe/Bucharest');

define('MAX_TRANSFER_BYTES', 2 * 1024 * 1024 * 1024); // 2GB
define('MAX_FILES_PER_TRANSFER', 50);
define('CHUNK_BYTES', 1 * 1024 * 1024); // 1MB
define('CRON_HTTP_KEY', 'PUNE-CHEIA-AICI'); //CHEIE SECRETA ACCESARE cron_cleanup.php
define('NUKE_HTTP_KEY', 'PUNE-CHEIA-AICI'); //CHEIE SECRETA ACCESARE clear.php
define('ADMIN_USER', 'admin'); //USERNAME ACCESARE PAGINĂ ADMINISTARRE
define('ADMIN_PASS', 'XXXXXXXXXXXXX'); // PAROLĂ ACCESARE PAGINĂ ADMINISTRARE
define('BASE_PATH', __DIR__);
define('STORAGE_PATH', BASE_PATH . '/storage');
define('TRANSFERS_PATH', STORAGE_PATH . '/transfers');
define('TMP_PATH', STORAGE_PATH . '/tmp');
define('LOG_PATH', STORAGE_PATH . '/logs');
define('DB_PATH', STORAGE_PATH . '/db.sqlite');

define('APP_BASE_URL', 'https://DOMENIUL-TAU.XXX/rotransfer');


define('SMTP_HOST', 'mail-eu.smtp2go.com'); //ADRESA/IP HOST SMTP
define('SMTP_PORT', 465); // 587 STARTTLS, 465 implicit TLS
define('SMTP_USER', 'neurici'); //USERNAME AUTENTIFICARE SMTP
define('SMTP_PASS', 'sergiu1981'); //PAROLĂ AUTENTIFICARE SMTP
define('SMTP_FROM_EMAIL', 'noreply@neuro.sytes.net'); //DE LA ACEASTĂ ADRESĂ INIȚIATORII ȘI DESTINATARII TRANSFERURILOR VOR PRIMI NOTIFICĂRI
define('SMTP_FROM_NAME', '• roTransfer •');
define('SMTP_ENCRYPTION', 'tls'); // 'none' | 'starttls' | 'tls'



define('APP_NAME', 'roTransfer');

// cat timp ramane sesiunea valida dupa parola corecta
define('TRANSFER_AUTH_TTL', 24 * 3600);
