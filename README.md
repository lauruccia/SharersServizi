# Portale Effepi Master

App Laravel 13 per effepimaster.it: home pubblica, registrazione di dealer e agenti con videocall,
aree riservate (dealer, agente, manager), Promo Dedicate con conto alla rovescia.

- Istruzioni di lavoro e stato del codice: `CLAUDE.md`
- Specifiche: cartella `docs/` (partire da `01-requisiti.md`)
- Punti da chiarire col cliente: `docs/10-domande-aperte.md`

## Avvio in locale

Serve PHP 8.3 o superiore. La cartella `vendor/` è già inclusa (solo librerie di produzione).

```
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Con `DB_CONNECTION=sqlite` il database è il file `database/database.sqlite` (crearlo vuoto se manca).
Il seed crea i manager `roberto` e `stefania` con la password indicata in `MANAGER_INITIAL_PASSWORD`
(da cambiare al primo accesso). Le email in locale finiscono in `storage/logs/laravel.log`.

Per lanciare i test servono anche le librerie di sviluppo: `composer install`, poi `php artisan test`.

## Pubblicazione

Vedi `docs/08-deploy-cpanel.md`. SQL per phpMyAdmin in `database/sql/`.
