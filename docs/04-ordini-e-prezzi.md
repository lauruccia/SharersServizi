# Moduli d'ordine e regole di prezzo

Tutti gli ordini vanno a **gestioneordini@sharers.it** con copia all'email del cliente.
Pagamento: bonifico o carta (Stripe). "La merce verrà evasa solo alla ricezione del pagamento."

Campi comuni: ragione sociale, indirizzo di consegna, email cliente, agente (precompilato),
data ordine, modalità di pagamento, note.

**Chi ordina (deciso il 12/11/2026)**: dealer e agenti.
- Il *dealer* ordina per sé: ragione sociale, indirizzo ed email precompilati dalla sua
  anagrafica; il campo "Agente" mostra il suo agente di riferimento, non modificabile.
  Può ordinare solo se la sua anagrafica fiscale è completa.
- L'*agente* ordina per conto di un suo dealer: lo sceglie da un elenco limitato al proprio
  portafoglio (solo quelli con anagrafica completa) e i dati si compilano di conseguenza;
  "Agente" è lui stesso.

## 1. Ordine Optima (p. 17)

**Plafond ricariche** — importo da 100 € a 1.500 € (elenco a tendina; tagli da definire):
- sconto **4%** fino a 300 €, **6%** oltre → costo = importo − sconto.
- Operazione non soggetta a IVA (art. 74 DPR 633/72).

**SIM Optima** — quantità in **multipli di 20**:
- **3,50 €** a SIM fino a 40 SIM, **2,50 €** a SIM oltre le 40.
- **Confermato (12/11/2026)**: oltre le 40 il prezzo di 2,50 € vale per **tutte** le SIM
  dell'ordine. Esempi: 20 SIM = 70 €, 40 SIM = 140 €, 60 SIM = 150 €, 80 SIM = 200 €.
  Nota: 60 SIM costano poco più di 40; è voluto.

**Trasporto**: 6,00 € se ci sono SIM (non soggetto a IVA, art. 74); nessuna spedizione per il
solo plafond.

## 2. Ordine SIM altri operatori (p. 18)

Operatori: **Daily Telecom**, **Italia Power**, **E-mobile**. Per ciascuno un pacchetto:

| Pacchetto | Prezzo per SIM |
|---|---|
| 30 pz | 9,00 € |
| 50, 75, 100 pz | 8,50 € |
| 150, 200 pz | 8,00 € |

(Tagli riletti dalla bozza ad alta risoluzione: 30 · 50, 75, 100 · 150, 200.)
Trasporto: **8,00 € per ordine**. Non soggetto a IVA (art. 74).
⚠️ Da confermare: lo scaglione si applica per singolo operatore o sul totale dei pezzi?

## 3. Ordine Promo Dedicate (p. 19)

Prodotto e prezzo arrivano dalla promo: l'utente inserisce solo i pezzi. "+ Aggiungi un'altra
offerta" per più righe. Prezzi IVA compresa. Spese di trasporto: campo nella bozza, regola di
calcolo da definire (slide: "pesi dei prodotti e listino SpediamoPro"). Una promo scaduta non è
più ordinabile: il controllo va rifatto dal server all'invio.

## 4. Ordine generico (p. 20)

Cinque righe libere (quantità, descrizione, importo unitario IVA compresa), tipo di merce
(elenco da definire), spese di trasporto, totale IVA compresa.
⚠️ Chi inserisce prezzo e trasporto? Se li scrive il dealer serve una regola o un controllo.

## Come è stato costruito (12/11/2026)

- Plafond: tagli proposti 100–1.000 a passi di 100, poi 1.200 e 1.500 (`config/effepi.php`,
  da confermare). SIM Optima: fino a 400 per ordine.
- Promo Dedicate e ordine generico: il trasporto **non viene calcolato**; ordine ed email
  riportano "da quantificare" e il totale è indicato come "trasporto escluso".
- Ordine generico: prezzo e descrizione li scrive chi ordina; "Tipo di merce" è un campo libero.
- Pagamento: solo bonifico. La carta comparirà quando Stripe sarà collegato
  (`effepi.payment_methods`). Le coordinate bancarie si impostano nel `.env`
  (`EFFEPI_BANK_HOLDER`, `EFFEPI_BANK_IBAN`); finché mancano, l'ordine dice che le comunica il back office.
- Numero d'ordine: `ORD-anno-progressivo`, usato anche come causale.

## Regole tecniche

- Calcolo in una sola classe (es. `App\Services\OrderPricing`) con test per ogni scaglione e
  per i casi limite (40 / 60 SIM, 300 / 320 € di plafond, solo plafond senza trasporto).
- Prezzi e scaglioni in configurazione o tabella modificabile dai manager, non nel codice delle viste.
- Numero d'ordine progressivo; l'email parte una sola volta; con carta l'ordine risulta pagato
  solo dopo la conferma di Stripe (webhook), non al ritorno dell'utente sulla pagina.
