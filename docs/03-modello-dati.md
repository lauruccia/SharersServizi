# Modello dati (proposta)

Punto di partenza da rifinire quando arrivano i file reali del cliente (vedi `05-import-dati.md`).

## Utenti e rete

- **users**: name, username (unico), email (unica), password (nullable finché non creata),
  role (`dealer`/`agent`/`capoarea`/`admin`/`superadmin`), active, last_login_at.
- **area_heads**: user_id, phone, region (i capoarea).
- **agents**: user_id, area_head_id (nullable: il capoarea), phone, regions (json), zones, target_sim, target_energy, target_fiber.
- **dealers**: user_id, agent_id, business_name, contact_name, phone, activity_type, region,
  municipality, status; dati fiscali: vat_number, sdi_code, iban, pec, legal_address,
  operating_address, legal_rep_mobile, billing_regime (`standard`/`ritenuta_ridotta`/`forfettario`);
  external_code (codice con cui il dealer compare nei report Optima), sharers_site_url.
- **dealer_documents**: dealer_id, type (visura, documento, foto), path, uploaded_by.

## Registrazioni

- **registration_requests**: profile (`dealer`/`agent`), campi del modulo, region,
  meeting_at, meeting_manager_id, google_event_id, meet_url, privacy_accepted_at,
  status (`in_attesa`/`approvata`/`rifiutata`), decided_by, decided_at, user_id.
- **registration_messages**: request_id, manager_id, body (le "Chiedi informazioni").

## Dati di produzione (da import)

- **imports**: type, file_name, period, rows_ok, rows_error, uploaded_by, created_at
  (l'ultimo import dà il "Dati aggiornati al").
- **monthly_targets**: owner_type/owner_id (dealer o agente), month, sim, energy, fiber,
  earning_at_target.
- **daily_progress**: dealer_id, date, sim, luce, gas, fiber (unico per dealer+data).
- **compensation_plans**: month, audience (`dealer`/`agent`), name, pdf_path, created_by
  (un piano per mese; il PDF è quello mostrato in "Piano compensi del mese").
- **compensation_rules**: plan_id, product (`sim`/`luce`/`gas`/`fiber`/…), tipo di regola
  (importo fisso a pezzo, scaglioni per volume, bonus al raggiungimento del target), soglie, importi.
- **earnings** (risultato del calcolo, rigenerato a ogni import): owner_type/owner_id, month,
  current_amount, target_amount, at_risk_amount, calculated_at.
- **contests** (gare extra): name, valid_until, prize, goal; **contest_progress**: contest_id,
  dealer_id, done.
- **sharers_sites**: dealer_id, url, joined_at, subscribers, subscribers_target.
- **sharers_orders**: dealer_id, date, order_number, amount.
- **rapicom_clients**: agent_id, name, municipality, assigned_at; **rapicom_orders**.

## Ordini

- **orders**: number, type (`optima`/`altri_operatori`/`promo`/`generico`), user_id,
  dealer_id, agent_id, business_name, delivery_address, customer_email, order_date,
  shipping_cost, total, vat_note, payment_method (`bonifico`/`carta`),
  payment_status (`in_attesa`/`pagato`), stripe_session_id, notes, emailed_at.
- **order_items**: order_id, description, quantity, unit_price, amount, promo_id (nullable).

## Promo e catalogo

- **promos**: name, photo_path, price, starts_at, ends_at, created_by.
- **sharers_showcase**: position (1–3), name, url, photo_path, updated_by.

## Contenuti e supporto

- **materials**: brand (optima/sharers/rapicom/altri), kind (modulo/grafica/video/piano_compensi/
  file_punto_vendita), title, path o url, audience (dealer/agent/entrambi), month (per i piani compensi).
- **faqs**: question, answer, audience, position.
- **ticket_topics**: name; **tickets**: dealer_id, topic_id, body, status, reply, replied_by.
- **contracts_sent**: user_id, file, note (per "Invia contratto al back office").
- **settings**: numeri della home, link esterni (Optimaster, comparatore, sharers.it, rapicom.it).
- **activity_log**: user_id, action, subject, data, created_at.

## Calcolo dei guadagni (deciso il 12/11/2026)

I guadagni non si importano: li calcola il portale applicando il **piano compensi del mese**
alla produzione importata.

- *Dealer* — "Stai guadagnando" = piano applicato ai pezzi fatti finora nel mese;
  "Guadagni raggiungendo il target" = piano applicato ai pezzi del target pattuito.
- *Agente* — "Guadagno previsto" = piano agenti applicato alla produzione del suo portafoglio;
  "Rischi di perdere" = differenza tra il guadagno al target e quello attuale (ipotesi da confermare).
- Il calcolo sta in una sola classe (es. `App\Services\EarningsCalculator`) con test costruiti
  su casi reali forniti da Effepi (produzione di un mese → compenso effettivamente pagato).
- Il ricalcolo parte dopo ogni import e quando un manager modifica il piano del mese.
- I piani dei mesi passati non si modificano: restano come storico.

La struttura delle regole è un'ipotesi finché non vediamo un piano compensi vero: serve capire
se bastano regole inserite da un modulo nell'area manager o se ogni mese cambia la logica.
