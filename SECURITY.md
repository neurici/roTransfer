# Politică de securitate – roTransfer

## 🔐 Raportarea vulnerabilităților

Dacă descoperi o vulnerabilitate de securitate în roTransfer, **nu o publica public**.

Te rugăm să o raportezi responsabil:
- prin email către administratorul proiectului
- sau printr-un issue **privat** (dacă repository-ul permite)

Vom analiza problema și vom reveni cu un răspuns în cel mai scurt timp posibil.

---

## 🛡️ Măsuri de securitate implementate

- Acces blocat la fișiere sensibile (`config.php`, `.sqlite`, `.log`, `.ini`)
- Listarea directoarelor dezactivată
- Validare input utilizator
- Token-uri unice pentru transferuri
- Expirare automată a transferurilor
- Log email doar la trimitere reușită

---

## 🚫 Ce NU este suportat

- Acces public la `log.php`
- Utilizare în medii neîncrezătoare fără autentificare
- Garanții de securitate tip enterprise

---

## ⚠️ Declarație

roTransfer este oferit „ca atare”, fără garanții.
Utilizatorul este responsabil de securizarea serverului și a accesului la aplicație.
