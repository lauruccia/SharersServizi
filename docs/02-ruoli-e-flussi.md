# Ruoli, permessi e flussi

## Ruoli

| Ruolo | Chi è | Cosa vede |
|---|---|---|
| `superadmin` | Laura | Controllo completo, compreso il sistema e gli altri superadmin |
| `admin` | Sharers / Effepi (Roberto e Stefania) | Controllo completo tranne il sistema e i superadmin: approvazioni, promo, import, contenuti, utenti |
| `capoarea` | Responsabile di più agenti | In sola lettura: i suoi agenti, i dealer sotto di loro e i loro ordini |
| `agent` | Agente di zona | Solo il proprio portafoglio di dealer; inserisce nuove anagrafiche |
| `dealer` | Negozio di prossimità | Solo i propri dati, ordini, segnalazioni, materiali |

**Regola di visibilità (deciso il 23/11/2026):** ognuno vede tutti i dati di chi sta sotto di lui,
a ogni livello di profondità, e mai quelli di chi sta sopra. Nel codice la regola sta in un solo
punto: lo scope `visibleTo($user)` di `Agent`, `Dealer`, `Order` e `Ticket`.

**Chi gestisce chi:** un admin agisce su tutti gli account tranne i superadmin; nessuno agisce
su se stesso (password, sospensione). Il superadmin crea admin e capoarea dalla pagina "Rete";
l'admin crea solo capoarea. Agenti e dealer nascono dall'approvazione delle richieste.
Il capoarea ha una scheda "Area capoarea" e l'elenco ordini dei suoi agenti, senza poter ordinare.
Il back office assegna gli agenti ai capoarea dalla pagina "Rete".

Competenza videocall: **Stefania** Lazio e Sardegna; **Roberto** Abruzzo, Basilicata, Calabria,
Marche, Molise, Puglia, Sicilia, Umbria. Dopo la selezione i contatti passano agli agenti di
riferimento. I due admin hanno gli stessi permessi: la regione decide solo a chi viene
assegnata la videocall (e su quale calendario Google).

## Dalla registrazione all'area riservata

1. **Registrazione** dalla home (dealer o agente) con scelta di data e ora della videocall.
   → si salva la richiesta in stato `in_attesa`; si crea l'evento sul calendario del manager di
   competenza con link Meet; email di conferma al candidato e avviso al manager.
2. **Videocall** di 30 minuti su Google Meet (lun/mer/ven, 14–19).
3. **Selezione** nell'area manager:
   - *Approva* → si crea l'utente con una **password provvisoria**, parte l'email con nome
     utente e password provvisoria; stato `approvata`.
   - *Chiedi informazioni* → email al candidato con il testo scritto dal manager; resta `in_attesa`.
   - *Rifiuta* → stato `rifiutata` (email al candidato: da decidere, vedi domande aperte).
4. **Credenziali** (deciso il 12/11/2026): al primo accesso il portale obbliga a scegliere una
   password propria (almeno 10 caratteri, lettere e numeri, diversa dalla provvisoria); fino ad
   allora nessuna pagina dell'area riservata si apre. Se la persona perde l'email, il manager
   invia una nuova password provvisoria dalla pagina "Rete".
5. **Area riservata**: dopo il login si va all'area del proprio ruolo.

Fino all'approvazione l'area riservata non è accessibile.

### Secondo canale: anagrafica inserita dall'agente

L'agente compila "Nuova anagrafica dealer" con dati fiscali e documenti → avviso ad
affiliazioni@effepimaster.it (senza IBAN né allegati: si consultano nell'area manager, pagina
"Anagrafiche") → il manager verifica e preme "Attiva il dealer". Il dealer è già assegnato a
quell'agente e, se non aveva un accesso, riceve le credenziali in quel momento.

**Deciso il 12/11/2026**: i dati fiscali e i documenti li inserisce **sempre l'agente**.
Un dealer arrivato dalla home, dopo l'approvazione, viene assegnato dal manager a un agente;
l'agente lo trova nel suo portafoglio come "anagrafica da completare" e compila il modulo con
i dati già noti precompilati. Finché l'anagrafica non è completa il dealer può entrare
nell'area riservata ma **non può ordinare**: "Ordina adesso" mostra un avviso che rimanda al
suo agente. Il blocco va applicato dal server, non solo nascondendo il pulsante.

## Email del sistema

| Evento | Destinatario | Mittente |
|---|---|---|
| Registrazione ricevuta + link Meet | Candidato | backoffice@effepimaster.it |
| Nuova richiesta | Manager di competenza | sistema |
| Richiesta di informazioni | Candidato | backoffice@ |
| Approvazione con credenziali | Candidato | backoffice@ |
| Reimposta password | Utente | backoffice@ |
| Nuovo ordine | gestioneordini@sharers.it + copia all'email cliente | sistema |
| Nuova anagrafica dealer (con allegati) | affiliazioni@effepimaster.it | sistema |
| Segnalazione dealer | backoffice@effepimaster.it | sistema |
| Invio contratto al back office | backoffice@ (da confermare) | sistema |

Caselle da creare sul dominio: info@, backoffice@, affiliazioni@ (più eventuale noreply@).
Sul dominio vanno configurati SPF e DKIM, altrimenti le email finiscono in spam.

## Sicurezza e privacy

- La password provvisoria viaggia per email una sola volta e va cambiata al primo accesso;
  quella scelta dall'utente non viene mai inviata né mostrata.
- Limite ai tentativi di login e ai moduli pubblici; protezione anti-spam (honeypot o captcha).
- Documenti d'identità, visure e IBAN: archivio privato, accesso solo ai manager, tempi di
  conservazione da definire con l'informativa privacy.
- Consenso privacy registrato con data e ora per ogni richiesta e ogni anagrafica.
- Registro delle azioni dei manager.
