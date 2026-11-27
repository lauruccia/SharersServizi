# Import dei dati (Excel/CSV)

Deciso: i dati di rete non arrivano da collegamenti automatici ma da **file caricati dai manager**
nell'area manager. Il momento dell'ultimo import riuscito è il "Dati aggiornati al [data e ora]".

## File attesi (da ricevere dal cliente: finché non li vediamo le colonne sono ipotesi)

| Import | Alimenta | Frequenza presunta |
|---|---|---|
| Report dealer (produzione) | Avanzamento oggi/mese di SIM, luce, gas, fibra | Giornaliera |
| Target mensili | Target di dealer e agenti, guadagno al target | Mensile |
| Gare extra | Nome, scadenza, premio, avanzamento | Quando serve |
| Analisi punti vendita Sharers | Siti, iscritti, target iscrizioni, ordini | Da definire |
| Rapicom | Clienti assegnati e ordini (area agente) | Da definire |

## Guadagni: calcolati, non importati

Deciso il 12/11/2026: i guadagni si **calcolano dal piano compensi** a partire dalla produzione
importata. Vedi la sezione "Calcolo dei guadagni" in `03-modello-dati.md`.

## Come deve funzionare

1. Il manager sceglie il tipo di import e carica il file (.xlsx o .csv).
2. **Anteprima**: righe lette, righe valide, righe scartate con il motivo
   (dealer non riconosciuto, numero non valido, data fuori periodo).
3. Conferma → scrittura in un'unica transazione. Ricaricare lo stesso file non duplica:
   si aggiorna per chiave (dealer + data, oppure dealer + mese).
4. Storico degli import con chi, quando, file ed esito; possibilità di scaricare gli scarti.

## Punti tecnici

- Il collegamento riga → dealer serve una chiave stabile: codice dealer Optima o partita IVA
  (`dealers.external_code`). Il nome del negozio non basta.
- Mappatura delle colonne in configurazione, così se Optima cambia il formato non si riscrive il codice.
- Niente coda sul server: file piccoli in modo sincrono; se i file sono grandi, lettura a
  blocchi con libreria leggera (es. OpenSpout) per non superare tempo e memoria di PHP su cPanel.
- Dati Sharers al lancio: si parte dall'import del "file analisi punti vendita Sharers" citato
  nelle slide. Se sharers.it offre un'esportazione o un'API si potrà automatizzare (vedi domande aperte).

## Report dealer (produzione): realizzato il 26/11/2026

File di riferimento: `ReportDealer` di Optima (.xls/.xlsx/.csv), una riga per contratto/servizio, 48 colonne.
Import da **Area manager > Import**; ricaricare il file aggiorna per `Id Contratto`, senza duplicati, e conserva il flag "forza conteggio".

**Servizi** (colonna `Servizi Richiesti`; suffisso `_C` = consumer, senza = business):
MOBILE = SIM, ENERGIA = luce, GAS = gas, ADSL = fibra. VOIP è un'opzione dell'ADSL e **non si conteggia**.
H24, TUTELALEGALE, VIDEOCONSULTO_MEDICO/VETERINARIO si importano e si contano a parte, senza compenso.

**Data**: `Data Stipula` fa fede per mese, trimestre, anno e intervalli.

**Esito del contratto** (calcolato all'import, poi eventualmente forzato dall'admin riga per riga):
- *Attivo*: `Ultimo Stato Ctr` = "OK Attivazione".
- *Non conteggiato*: `Motivo Cest` = "QCC KO" o "KO Solvibilità"; contratto cestinato; CheckCard negativa.
- *In lavorazione*: ogni altro stato (Campagna QCC, Fine Verifica Utenza, Upload Contratto, Contratto Importato, Check Antifrode, Non censibile). Si mostra a parte e non si paga, salvo l'opzione "paga anche in lavorazione" nel piano.
- Flag **forza calcolo** per riga: conta sempre / non conta mai / automatico (scheda dealer in Produzione).

**SIM**: `nSimVendute` = SIM del contratto, `nSimPortabilita` = quante in portabilità. Tariffa 6,95 se `Sottotipo Ctr` è in elenco (oggi solo `SMOB_5G_DE_0926`), altrimenti 4,95.
**Fibra con portabilità**: `Tipo Attivazione` = MIGRASOLODATI (impostabile in alternativa su `nSimPortabilita` > 0).
**Energy**: SDD se `Modalita Pagamento` = SDD; email se `Modalita Sped. Fattura` = Email.

Si conservano anche: Codice Amico Insert_Pda, Stato CC, Nota CCN, Motivo Cest, Tipo Attivazione e Stato Utenza (solo luce/gas e ADSL), simPagate.
Dati del cliente: solo il nome (codice fiscale e telefono non vengono salvati).

**Dealer**: si legano per `Id Dealer` = `dealers.external_code`; se manca, per nome identico; altrimenti il dealer viene creato (senza agente: lo si assegna da Rete). Area e ambassador del file riempiono solo i campi vuoti.

**Conteggi** (pagina Produzione, per dealer, agente, capoarea, ambassador, area): SIM, di cui MNP, luce, gas, fibra, fibra MNP,
attivazioni totali (SIM + luce + gas + fibra) e di cui in portabilità (SIM + fibra), in lavorazione, compensi.
**Compensi**: `EarningsCalculator`, per dealer e per mese, dal piano in vigore (Area manager > Compensi). Lo scaglione SIM si conta sul **totale SIM del dealer** nel mese (impostabile per offerta).
**Extra gare** sulla portabilità: le avvia l'admin da Gare; classifica dei dealer su un periodo.

**Correzioni manuali dell'admin (26/11/2026)**: l'admin può forzare ogni operazione sui conteggi dalla scheda del dealer in Produzione:
correggere qualsiasi dato di un contratto (dealer, servizio, data, stato, SIM e portabilità, offerta, pagamento, fattura, fibra con MNP sì/no, conta sempre/non conta),
inserire contratti a mano (`MAN-…`), eliminare (i contratti importati vengono esclusi e bloccati, perché un nuovo import non li riporti) e
aggiungere rettifiche in euro (positive o negative) ai compensi di un dealer in un mese. Le righe corrette o inserite a mano sono *bloccate*: l'import non le sovrascrive.
Ogni correzione è registrata (chi e quando) nel registro attività.
