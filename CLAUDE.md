# Portale Effepi Master (effepimaster.it)

Sito e area riservata di **Effepi Telefonia Srls**, Master Dealer Optima per il Centro Sud.
Serve a tre cose: **acquisire** dealer e agenti (registrazione + videocall), **selezionarli**
(approvazione del back office e invio credenziali), **supportarli** (aree riservate con dati,
ordini, strumenti e materiali).

Obiettivo: **online entro il 1° gennaio 2027**.

## Grafica (26/11/2026)

Stile Optima (colori, layout, Poppins al posto di UniSans), logo Effepi Master originale: vedi `docs/07-design.md`.

## Decisioni prese (12/11/2026)

- **Tutto in Laravel**: home pubblica, aree riservate, ordini, area manager in un'unica app.
  (Le slide del cliente parlano di WordPress su Aruba: superato.)
- **Hosting Serverplan condiviso**, cPanel senza terminale (vedi `docs/08-deploy-cpanel.md`).
  Nessun sito di prova: si pubblica direttamente sul dominio vero.
- **`vendor/` committato** nel repository (sul server non c'è Composer).
- Target, avanzamenti e guadagni arrivano da **import di file Excel/CSV** caricati dai manager.
- I **guadagni si calcolano dal piano compensi** del mese, non si importano.
- SIM Optima: oltre le 40, 2,50 € su **tutte** le SIM dell'ordine.
- **Ordini**: li compilano sia i dealer (per sé) sia gli agenti (per conto di un loro dealer).
- **Assistente virtuale**: al lancio **senza API**, ricerca nelle FAQ di Effepi. Il passaggio a
  un modello linguistico via API è rimandato: il codice va scritto in modo da poterlo sostituire.
- **Anagrafica fiscale** del dealer: la inserisce sempre l'agente, anche per i dealer arrivati dalla home.
  Finché non è completa il dealer **non può ordinare**.
- Al lancio devono funzionare davvero: **Stripe**, **Google Calendar + Meet**,
  **assistente virtuale**, **dati Sharers**.

- **Ruoli** (23/11/2026): superadmin (Laura) > admin (Sharers; Roberto e Stefania) > capoarea >
  agente > dealer. Ognuno vede i dati di chi sta sotto, mai di chi sta sopra; vedi `docs/02`.
- **Credenziali**: dopo l'approvazione parte un'email con nome utente e **password provvisoria**;
  al primo accesso il portale obbliga a cambiarla (deciso da Laura, al posto del link della bozza).

## Stato del codice (26/11/2026)

Laravel 13 (PHP 8.3 o superiore), senza build: CSS e JS scritti a mano in `public/`.

Fatto e coperto da test (66 test):
- gerarchia a cinque ruoli (26/11): scope `visibleTo`, area capoarea in sola lettura, assegnazione agenti ai
  capoarea, creazione accessi interni, regole admin/superadmin. SQL per cPanel: `database/sql/2026_11_26_ruoli_gerarchia.sql`;
- home pubblica con i due moduli e la prenotazione della videocall (lun/mer/ven 14–19, slot da 30 minuti,
  manager scelto per regione, niente doppie prenotazioni);
- accesso con nome utente o email, cambio password obbligatorio al primo accesso, password dimenticata;
- area manager: richieste (approva / chiedi informazioni / rifiuta), Promo Dedicate con conto alla
  rovescia, tre prodotti del catalogo Sharers, rete (assegna dealer → agente, nuova password
  provvisoria, sospendi accesso);
- aree dealer e agente con le cinque schede; l'agente vede solo i dealer del suo portafoglio;
- i quattro moduli d'ordine (Optima, SIM altri operatori, Promo Dedicate, generico): prezzi
  ricalcolati dal server (`OrderPricing`, `OrderBuilder`), email a gestioneordini@sharers.it con il
  cliente in copia, elenco ordini per dealer e agente, elenco completo per i manager con
  "Segna pagato". Pagamento: per ora solo bonifico;
- nuova anagrafica dealer compilata dall'agente (nuovo dealer o dealer arrivato dalla home), con i
  quattro documenti in archivio privato; i manager la verificano in "Anagrafiche" e attivano il
  dealer, che da quel momento può ordinare (se non aveva un accesso riceve le credenziali).

- produzione e compensi (26/11): import del ReportDealer (`ProductionImporter`), conteggi per dealer/agente/capoarea/
  ambassador/area con filtro mese-trimestre-anno-intervallo, calcolo compensi (`EarningsCalculator`) dal piano modificabile
  dall'admin, flag "forza calcolo" per riga, extra gare sulla portabilità, elenchi gestibili (dealer, aree, ambassador,
  agenti, capoarea); l'admin può correggere a mano ogni dato dei contratti, inserirne o escluderne e aggiungere rettifiche ai
  compensi (righe bloccate: l'import non le sovrascrive). SQL per cPanel: `database/sql/2026_11_26_produzione_compensi.sql` e
  poi `database/sql/2026_11_26_rettifiche_e_correzioni.sql`. Dettagli in `docs/05`.
  Richiede `phpoffice/phpspreadsheet` (in `vendor/`, committato).

Non ancora fatto (i pulsanti portano a una pagina "in preparazione"):
pagamento con carta (Stripe), invio contratto, segnalazioni, materiali e FAQ,
assistente, import di target e dati Sharers, compensi propri di agenti e capoarea,
supplemento SIM Optima oltre le 40 nel piano compensi, collegamento a Google Calendar/Meet.
Dove mancano i dati le aree mostrano "—".

Dove mettere le mani:
- `config/effepi.php`: regioni, manager per regione, orari videocall, durate promo, prezzi, email, link;
- `app/Services/VideocallSlots.php` e `CalendarGateway.php`: orari liberi; Google si aggancia qui
  (oggi è attiva `LocalCalendar`, che non crea il link Meet);
- `app/Models/Promo.php` (`scopeActive`): un'offerta è visibile solo tra inizio e fine, quindi sparisce
  da sola senza cron; il conto alla rovescia in `public/js/app.js` parte dall'ora del server;
- `database/sql/`: SQL per phpMyAdmin, uno per ogni modifica al database.

## Vincoli fissi

- Sul server non c'è terminale né SSH: mai proporre comandi da eseguire in produzione
  (`php artisan`, `composer`, `npm`). Manutenzione = pulsanti nell'area manager o cron di cPanel.
- In produzione **niente `php artisan migrate`**: ogni modifica al database va consegnata anche
  come SQL per phpMyAdmin (MySQL/MariaDB), con la riga per la tabella `migrations`.
- Niente build sul server: gli asset compilati vanno committati oppure si evita la build.
- Niente worker di coda: email e lavori lenti in modo sincrono o con lo scheduler via cron.
- **Git**: non committare e non pushare finché Laura non lo dice esplicitamente.
  Nei messaggi di commit e nelle PR nessun riferimento a Claude (niente Co-Authored-By,
  niente righe di attribuzione).
- Testi dell'interfaccia in italiano. Codice (classi, tabelle, colonne) in inglese.

## Convenzioni

- Cinque ruoli: `superadmin`, `admin`, `capoarea`, `agent`, `dealer`. Ognuno vede **solo chi sta sotto di lui**:
  ogni query va filtrata lato server con lo scope `visibleTo($user)` (mai solo in vista).
  Back office = `admin` + `superadmin` (`User::isBackOffice()`); le route `/manager/...` sono solo per loro.
- Importi in centesimi (interi) o `decimal(10,2)`, mai float. Fuso orario `Europe/Rome`.
- Le regole di prezzo stanno in una sola classe/config (vedi `docs/04`), non sparse nelle viste:
  il calcolo lato browser è solo un'anteprima, il totale valido è quello ricalcolato dal server.
- File caricati (documenti dealer, materiali) fuori dalla cartella pubblica, serviti da route
  con controllo dei permessi.
- Ogni azione dei manager (approvazioni, promo, import) registra **chi l'ha fatta e quando**.

## Documenti

| File | Contenuto |
|---|---|
| `docs/01-requisiti.md` | Schermate e funzioni, una per una |
| `docs/02-ruoli-e-flussi.md` | Ruoli, permessi, flusso registrazione → credenziali, email |
| `docs/03-modello-dati.md` | Tabelle e relazioni proposte |
| `docs/04-ordini-e-prezzi.md` | Moduli d'ordine e regole di calcolo |
| `docs/05-import-dati.md` | Import Excel/CSV di target, avanzamenti, guadagni, Sharers |
| `docs/06-integrazioni.md` | Stripe, Google Calendar/Meet, assistente, email |
| `docs/07-design.md` | Colori, font, componenti della bozza |
| `docs/08-deploy-cpanel.md` | Pubblicazione e aggiornamento su cPanel |
| `docs/09-roadmap.md` | Fasi di lavoro fino al 1° gennaio |
| `docs/10-domande-aperte.md` | Cosa manca dal cliente e cosa va deciso |

Fonti: `Effepi_Master_bozza_sito.pdf` (23 schermate), `Effepi_Master_progetto_sito.pptx`,
mockup HTML "Area dealer" (nel Project "SharersServizi" su Claude). La bozza è dimostrativa:
dati, foto e prezzi sono in parte segnaposto.
