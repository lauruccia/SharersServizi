# Integrazioni

Tutte e quattro devono funzionare al lancio. Chiavi e credenziali solo nel file `.env`, mai nel repository.

## Stripe (pagamento con carta negli ordini)

- **Stripe Checkout** (pagina di pagamento ospitata da Stripe): nessun dato di carta sul sito.
- Flusso: invio ordine → ordine salvato `in_attesa` → reindirizzamento a Checkout →
  **webhook** `checkout.session.completed` → ordine `pagato` → email a gestioneordini@ e al cliente.
- L'URL del webhook va escluso dalla protezione CSRF e verificato con la firma di Stripe.
- Con bonifico: email subito con le coordinate; il pagamento lo segna a mano un manager.
- Serve dal cliente: account Stripe intestato alla società giusta (Effepi o Sharers? gli ordini
  vanno a gestioneordini@sharers.it), chiavi di test e di produzione.

## Google Calendar e Meet (videocall)

- Due account Gmail (uno per Roberto, uno per Stefania), come da slide.
- Il modulo mostra solo gli **orari liberi**: lun/mer/ven, 14–19, slot da 30 minuti, sul
  calendario del manager competente per la regione scelta (lettura free/busy).
- Alla registrazione si crea l'evento con conferenza Meet e il candidato come invitato;
  si salvano `google_event_id` e `meet_url`.
- Autorizzazione: OAuth dei due account fatta una volta dall'area manager ("Collega Google
  Calendar"), con token di aggiornamento salvato cifrato. Serve un progetto Google Cloud con
  Calendar API attiva.
- Da prevedere: doppia prenotazione dello stesso slot (ricontrollo al salvataggio), preavviso
  minimo, festività, annullo o spostamento dall'area manager.

## Assistente virtuale ("Chiedi all'assistente")

- Risponde **solo** a partire dalle domande frequenti e dai materiali caricati da Effepi; se non
  trova la risposta rimanda alla segnalazione (dealer) o al back office (agente).
- **Deciso il 12/11/2026: al lancio senza API.** L'assistente cerca nelle FAQ di Effepi e
  mostra le risposte più vicine alla domanda (ricerca per parole, senza accenti e senza parole
  vuote, con parole chiave aggiuntive che i manager possono associare a ogni FAQ).
  Nessun risultato → invito ad aprire una segnalazione.
- **Più avanti**: modello linguistico via API con le FAQ come unica fonte. Per non riscrivere
  nulla, la logica sta dietro un'unica interfaccia (es. `AssistantResponder`) con oggi
  l'implementazione "ricerca FAQ" e domani quella "API". Quando si farà serviranno: fornitore,
  chiave in `.env`, limite di domande per utente, tetto di spesa; chiamata sempre dal server.
- Testo "Assistente virtuale automatico" sempre visibile (già in bozza), registro
  delle domande per arricchire le FAQ, nessun dato personale inviato a servizi esterni.

## Dati Sharers

Iscritti e ordini dei siti dei negozi. Al lancio via import di file (`05-import-dati.md`);
collegamento diretto solo se sharers.it lo consente.

## Email

SMTP delle caselle del dominio. Invio sincrono (nessun worker): messaggi brevi, allegati
dell'anagrafica con limite di dimensione, oppure link protetto ai documenti al posto degli allegati.

## Link esterni (modificabili dai manager)

Optimaster · Comparatore bollette · sharers.it · rapicom.it
