<?php

return [
    // Regioni di competenza.
    'regions' => [
        'Abruzzo', 'Basilicata', 'Calabria', 'Lazio', 'Marche',
        'Molise', 'Puglia', 'Sardegna', 'Sicilia', 'Umbria',
    ],

    // Chi segue le videocall, per regione (nome utente del manager).
    'manager_by_region' => [
        'Lazio' => 'stefania',
        'Sardegna' => 'stefania',
    ],
    'default_manager' => 'roberto',

    'activity_types' => [
        'Negozio di telefonia multibrand',
        'Specialista dell\'energia',
        'CAF',
        'Agenzia immobiliare',
        'Altro',
    ],

    // Videocall di presentazione: lunedì, mercoledì, venerdì dalle 14 alle 19, 30 minuti.
    'videocall' => [
        'weekdays' => [1, 3, 5], // ISO: 1 = lunedì
        'start' => '14:00',
        'end' => '19:00',
        'slot_minutes' => 30,
        'min_notice_hours' => 12,
        'max_days_ahead' => 45,
    ],

    // Durate ammesse per le Promo Dedicate (ore => etichetta).
    'promo_durations' => [
        24 => '24 ore',
        48 => '48 ore',
        72 => '72 ore',
        168 => '1 settimana',
    ],

    'mail' => [
        'backoffice' => env('EFFEPI_MAIL_BACKOFFICE', 'backoffice@effepimaster.it'),
        'orders' => env('EFFEPI_MAIL_ORDERS', 'gestioneordini@sharers.it'),
        'affiliations' => env('EFFEPI_MAIL_AFFILIATIONS', 'affiliazioni@effepimaster.it'),
        'info' => 'info@effepimaster.it',
    ],

    'ticket_categories' => [
        'ordini' => 'Ordini e pagamenti',
        'anagrafica' => 'Anagrafica dealer',
        'accesso' => 'Accesso e password',
        'offerte' => 'Offerte e prezzi',
        'altro' => 'Altro',
    ],

    'links' => [
        'optimaster' => env('EFFEPI_LINK_OPTIMASTER', '#'),
        'comparatore' => env('EFFEPI_LINK_COMPARATORE', '#'),
        'sharers' => 'https://www.sharers.it',
        'rapicom' => 'https://www.rapicom.it',
    ],

    // Numeri della home (ultimi 12 mesi): valori iniziali, modificabili dalla tabella settings.
    'home_numbers' => [
        'dealer' => 180,
        'sim' => 26000,
        'luce_gas' => 2000,
        'fibra' => 1000,
    ],

    // Coordinate per il bonifico, mostrate nella conferma e nell'email dell'ordine.
    'bank' => [
        'holder' => env('EFFEPI_BANK_HOLDER'),
        'iban' => env('EFFEPI_BANK_IBAN'),
    ],

    // Carta (Stripe) si aggiunge qui quando l'account è collegato.
    'payment_methods' => ['bonifico'],

    'vat_note_art74' => 'Operazione non soggetta a IVA ai sensi dell\'art. 74 DPR 633/72.',

    // Caricamenti dell'anagrafica dealer (kilobyte).
    'upload_max_kb' => 8192,

    // Regole di prezzo degli ordini (docs/04-ordini-e-prezzi.md).
    'pricing' => [
        'plafond' => [
            'min' => 100, 'max' => 1500, 'threshold' => 300, 'discount_low' => 4, 'discount_high' => 6,
            // Tagli proposti nel modulo: da confermare con Effepi.
            'amounts' => [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000, 1200, 1500],
        ],
        'sim_optima' => ['max_quantity' => 400, 'multiple' => 20, 'threshold' => 40, 'price_low_qty' => 3.50, 'price_high_qty' => 2.50, 'shipping' => 6.00],
        'sim_altri' => [
            'operators' => ['Daily Telecom', 'Italia Power', 'E-mobile'],
            'packages' => [30 => 9.00, 50 => 8.50, 75 => 8.50, 100 => 8.50, 150 => 8.00, 200 => 8.00],
            'shipping' => 8.00,
        ],
    ],
];
