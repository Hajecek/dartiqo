# Dartiqo

PHP aplikace ve stejném duchu jako Privofit: jeden `index.php`, routy, kontroler a šablony.

Otevři http://localhost/dartiqo/.

- `routes/web.php` — adresy
- `app/Controllers/Web/PageController.php` — stránky
- `resources/views` — šablony
- `assets` — styly, skript a obrázky

## Databáze

1. Spusť MySQL v XAMPPu a nahraj schéma: `/Applications/XAMPP/xamppfiles/bin/mysql -uroot < database/schema.sql` (nebo import přes phpMyAdmin).
2. Přístupové údaje jsou v `config/database.php` (lze přepsat proměnnými `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3. Apache (uživatel `daemon`) musí mít zápis do `uploads/avatars`: `chmod 777 uploads/avatars`.

Registrace a přihlášení ukládají uživatele do tabulky `users`. Online hra a statistiky jsou zatím ukázková data.
