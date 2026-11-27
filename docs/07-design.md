# Design

**Decisione 26/11/2026:** la bozza del cliente era solo un esempio. Il portale riprende colori, stile e
layout di **Optima** (optimaitalia.com), di cui Effepi è partner. Il logo Optima **non** si usa:
c'è un logo Effepi Master originale in stile Optima (`public/img/logo-effepi.png`, `.svg`,
`simbolo-effepi.svg` per la favicon).

- **Font**: Poppins (400/500/600/700 + 700 corsivo), servito dal sito, non da Google (privacy).
  Sostituisce UniSans di Optima (proprietario). Titoli h1/h2: Poppins 700 corsivo, maiuscolo, azzurro.
- **Colori** (variabili in `public/css/app.css`)

| Uso | Colore |
|---|---|
| Azzurro (titoli, link, tab attiva, logo) | `#00a9ea` (barra alta `#00aeef`, menu `#00b9e2`, scuro `#0086c8`) |
| Magenta (pulsante "Registrati", etichette, timer promo) | `#ed1b7c` |
| Verde lime (pulsante principale a pillola) | `#98c23a` (hover `#82a92c`) |
| Verde valori positivi | `#2f8f4e` |
| Rosso (errori, importo a rischio) | `#b3202c` |
| Testo | `#212529`, secondario `#495057`, attenuato `#6b7280` |
| Sfondo sezioni | `#f2f3f7`; bordi `#dfe2e8`; banner e footer `#111` / `#212529` |

- **Struttura pagina pubblica**: barra azzurra in alto (Contatti, Accesso area riservata), header bianco
  con logo e menu maiuscolo azzurro, pulsante "Registrati" magenta con bordo, banner nero con il premio,
  hero su fondo grigio chiaro con card bianca arrotondata (mappa), cerchi azzurri con i numeri,
  fascia "Chi siamo" in gradiente azzurro, footer scuro.
- **Pulsanti**: principale verde lime a pillola (raggio 600px, testo bianco); secondario bianco con bordo
  azzurro; "accent" magenta squadrato maiuscolo. Tab delle aree riservate: pillole azzurre, attiva piena.
- **Card**: bianche, raggio 12px, ombra leggera. Campi: sfondo grigio chiaro con bordo inferiore.
- **Promo**: etichetta "OFFERTA 24H · SCADE TRA" su fondo magenta + conto alla rovescia hh:mm:ss.
- Larghezza di riferimento 1280px; versione mobile con barra alta centrata e logo ridotto.
- Senza build sul server: CSS scritto a mano; poco JavaScript.
- Email: stessa palette (barra azzurra, logo, pulsanti verdi a pillola).
- Da ricevere dal cliente: mappa dell'Italia vettoriale (quella attuale è tricolore), loghi dei brand con
  permesso d'uso.

## Animazioni della home (26/11/2026)

Tre blocchi che spiegano il senso del progetto, solo HTML/CSS/SVG + poche righe in `public/js/app.js`
(IntersectionObserver che aggiunge `is-in` al blocco `[data-reveal]` quando entra nello schermo):

1. `partials/home-hub.blade.php`: "Il tuo negozio diventa un hub di servizi" (negozio al centro, linee tratteggiate in
   movimento verso i servizi Optima, Sharers, Rapicom, altri operatori; Effepi in alto che guida). Sotto 860px diventa una griglia.
2. `partials/home-chain.blade.php`: "Dal brand al cliente di quartiere" (Optima/Sharers/Rapicom → Effepi → agenti → negozio, con frecce animate).
3. `partials/home-steps.blade.php`: "Dal primo contatto al primo ordine" (registrazione, videocall, approvazione, inizio lavoro;
   la linea si riempie e i passaggi si accendono in sequenza).

Con `prefers-reduced-motion` le animazioni sono spente e tutto è visibile; senza JavaScript i blocchi restano visibili
(la classe `js` sull'elemento html, messa da uno script in `layouts/base.blade.php`, abilita lo stato nascosto iniziale).

## Mobile e conversione (26/11/2026)

- Header fisso; sotto 860px il menu si apre dal pulsante a tre linee (senza JavaScript resta visibile sotto il logo). "Registrati" sempre in vista.
- Hero: su mobile prima titolo e pulsanti, poi la mappa; sotto i pulsanti la riga "Chiama lo 0773 273136".
- Barra fissa in basso "Registrati / Chiama" (solo mobile): compare dopo l'hero e sparisce sopra ai moduli.
- Moduli: su mobile tab "Sono un dealer / Sono un agente", un modulo alla volta; i link `#registrati-dealer` e `#registrati-agente` aprono il modulo giusto. Campi a 16px (niente zoom su iPhone), tap target da 44px.
- Nuove sezioni: FAQ (`partials/home-faq`, solo fatti già stabiliti), pulsante dopo l'hub, contatti come pulsanti (chiama, mobile, email).
- Verificato senza scorrimento orizzontale a 320, 360, 390, 768, 1024 e 1280 px.
