# Pubblicazione su cPanel (senza terminale)

Hosting: **Serverplan, piano condiviso** con cPanel. Nessun sito di prova: ogni aggiornamento
va direttamente sul sito vero, quindi prima del lancio si lavora a sito non pubblicizzato e
dopo il lancio si testa tutto in locale prima di pubblicare.

## Regole

- Nessun comando sul server. Aggiornamento: cPanel → **Git Version Control** → repository del
  progetto → "Pull or Deploy" → **Update from Remote**, poi **Deploy HEAD Commit**.
- Database: mai `migrate` in produzione. Ogni modifica = SQL da lanciare in **phpMyAdmin**
  (compatibile MySQL/MariaDB) + riga nella tabella `migrations`.
- Errori: `storage/logs/laravel-AAAA-MM-GG.log` dal File Manager.
- Dopo modifiche grafiche: ricarico forzato (Ctrl+F5).

## Da predisporre la prima volta

- **Laravel 13 richiede PHP 8.3 o superiore**: verificare nel selettore PHP di Serverplan.
- Database: in phpMyAdmin lanciare, in ordine, i file di `database/sql/`:
  1. `2026_11_12_struttura_iniziale.sql` (tutte le tabelle + tabella `migrations`);
  2. `2026_11_12_accessi_manager.sql` (Roberto e Stefania, con password provvisoria), dopo aver
     sostituito le due email con quelle reali;
  3. `2026_11_12_ordini_e_anagrafica.sql` (ordini, righe d'ordine, documenti dei dealer).
- I documenti dei dealer finiscono in `storage/app/private/dealer-documents` (fuori dalla cartella
  pubblica). Verificare che il PHP dell'hosting accetti caricamenti di almeno 8 MB per file
  (`upload_max_filesize`, `post_max_size`), altrimenti l'anagrafica con quattro allegati viene rifiutata.
  La struttura è generata dalle migrazioni ma **non è ancora stata provata su un MySQL vero**.
- Le foto delle promo finiscono in `public/uploads/promos` (cartella scrivibile, esclusa da Git).

- Versione di PHP dal selettore di cPanel compatibile con la versione di Laravel scelta, con
  estensioni: mbstring, openssl, pdo_mysql, fileinfo, zip, gd, intl, curl.
- La radice del dominio deve puntare alla cartella `public` dell'app (oppure app fuori da
  `public_html` e solo il contenuto di `public` dentro).
- `vendor/` **committato nel repository** (deciso): sul server non c'è Composer, così
  "Update from Remote" porta online anche le librerie. In locale installare con
  `composer install --no-dev` prima del commit, per non pubblicare gli strumenti di sviluppo.
- Asset compilati (`public/build`) committati allo stesso modo.
- `.env` creato a mano dal File Manager (APP_KEY generata in locale, `APP_DEBUG=false`).
- Cartelle scrivibili: `storage/` e `bootstrap/cache/`.
- File caricati: senza `storage:link` si servono con una route protetta (scelta consigliata,
  perché molti file sono riservati).
- **Cron di cPanel** ogni minuto per lo scheduler di Laravel: serve per far scadere le promo in
  modo pulito, promemoria videocall, pulizia dei link scaduti.
- Pulsanti di manutenzione nell'area manager: svuota cache, rigenera cache di configurazione.
- HTTPS attivo (AutoSSL) prima di collegare Stripe e Google.

## Dati da annotare qui quando disponibili

- Provider: Serverplan (hosting condiviso)
- Indirizzo del cPanel e nome del repository in cPanel: …
- Repository GitHub e branch: …
- Nome del database e utente MySQL: …
- Sito di prova: nessuno (deciso)
