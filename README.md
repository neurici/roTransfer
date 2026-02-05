# roTransfer

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Version](https://img.shields.io/badge/Version-1.2.0-orange)

roTransfer este o aplicație web PHP pentru transfer securizat de fișiere, cu notificări prin email și un sistem vizual de log pentru emailurile trimise.

Aplicația este optimizată pentru hosting-uri gratuite (ex: InfinityFree) și folosește stocare locală + SQLite.

---

## 🚀 Funcționalități

- Transfer securizat de fișiere
- Protecție opțională cu parolă
- Expirare automată a transferurilor
- Notificări prin email către destinatari
- **Vizualizare log email în browser**
- Bază de date SQLite (fără MySQL)
- Compatibilă cu hosting-uri free

---

## 🧾 Sistem log email

roTransfer loghează **doar emailurile trimise cu succes către destinatari**.

Câmpuri logate:
- Data / Ora (format `d-m-Y H:i:s`)
- Inițiator (email expeditor)
- Destinatar
- ID transfer
- Calea reală către transfer (filesystem)

Acces:
```
/rotransfer/log.php
```

---

## 🛠 Cerințe

- PHP 7.4+
- Apache
- Permisiuni de scriere pentru folderul `storage/`

---

## 🔐 Securitate

- Listarea directoarelor dezactivată
- Fișiere sensibile blocate prin `.htaccess`
- Logurile și baza de date nu sunt accesibile public

---

## 🚀 Instalare, 🧾 Actulizări și Securitate

- 🛠️ [Instalare](INSTALL.md)
- 🧾 [Jurnal Modificări](CHANGELOG.md)
- 🔐 [Securitate](SECURITY.md)

---

## 🛡️ Licență

Proiectul este distribuit sub licența MIT .  
👉 Vezi [LICENSE.md](LICENSE.md)



