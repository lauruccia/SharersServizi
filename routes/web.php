<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Manager\CommissionPlanController;
use App\Http\Controllers\Manager\DirectoryController;
use App\Http\Controllers\Manager\ProductionImportController;
use App\Http\Controllers\Manager\ProductionRowController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\Manager\ContentController;
use App\Http\Controllers\Manager\NetworkController;
use App\Http\Controllers\Manager\TicketController as ManagerTicketController;
use App\Http\Controllers\Manager\OrderController as ManagerOrderController;
use App\Http\Controllers\Manager\RegistryController as ManagerRegistryController;
use App\Http\Controllers\Manager\PromoController;
use App\Http\Controllers\Manager\RequestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// --- Sito pubblico ---------------------------------------------------------
Route::get('/', HomeController::class)->name('home');
Route::view('/privacy', 'privacy')->name('privacy');
Route::get('/videocall/orari', [RegistrationController::class, 'slots'])->name('videocall.slots');
Route::middleware('throttle:10,10')->group(function () {
    Route::post('/registrazione/dealer', [RegistrationController::class, 'storeDealer'])->name('registration.dealer');
    Route::post('/registrazione/agente', [RegistrationController::class, 'storeAgent'])->name('registration.agent');
});
Route::get('/richiesta-inviata', [RegistrationController::class, 'sent'])->name('registration.sent');

// --- Accesso ---------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/accedi', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/accedi', [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/password/dimenticata', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/password/dimenticata', [AuthController::class, 'sendReset'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/password/reimposta/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/password/reimposta', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/esci', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/cambia', [AuthController::class, 'showChange'])->name('password.change');
    Route::post('/password/cambia', [AuthController::class, 'change']);
});

// --- Aree riservate (solo dopo aver cambiato la password provvisoria) ---------
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/dealer', [AreaController::class, 'dealer'])->middleware('role:dealer')->name('dealer.area');
    Route::get('/agente', [AreaController::class, 'agent'])->middleware('role:agent')->name('agent.area');
    Route::get('/capoarea', [AreaController::class, 'areaHead'])->middleware('role:capoarea')->name('areahead.area');
    // Ordini: dealer per sé, agente per conto di un dealer del suo portafoglio.
    // L'elenco ordini lo vede anche il capoarea (in sola lettura, solo i suoi agenti).
    Route::get('/ordini', [OrderController::class, 'index'])->middleware('role:dealer,agent,capoarea')->name('orders.index');
    Route::middleware('role:dealer,agent')->group(function () {
        Route::get('/ordini/nuovo/{slug}', [OrderController::class, 'create'])->whereIn('slug', array_keys(OrderController::SLUGS))->name('orders.create');
        Route::post('/ordini/nuovo/{slug}', [OrderController::class, 'store'])->whereIn('slug', array_keys(OrderController::SLUGS))->middleware('throttle:20,10')->name('orders.store');
    });
    Route::get('/ordini/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');

    // Nuova anagrafica dealer: solo l'agente.
    Route::middleware('role:agent')->group(function () {
        Route::get('/agente/anagrafica/{dealer?}', [RegistryController::class, 'create'])->whereNumber('dealer')->name('registry.create');
        Route::post('/agente/anagrafica/{dealer?}', [RegistryController::class, 'store'])->whereNumber('dealer')->name('registry.store');
    });

    // Assistente e FAQ.
    Route::get('/assistente', AssistantController::class)->middleware('role:dealer,agent')->name('assistant');

    // Segnalazioni al back office (i manager rispondono dalla stessa pagina).
    Route::get('/segnalazioni/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
    Route::post('/segnalazioni/{ticket}/rispondi', [TicketController::class, 'reply'])->whereNumber('ticket')->middleware('throttle:30,10')->name('tickets.reply');
    Route::post('/segnalazioni/{ticket}/chiudi', [TicketController::class, 'close'])->whereNumber('ticket')->name('tickets.close');
    Route::middleware('role:dealer,agent')->group(function () {
        Route::get('/segnalazioni', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/segnalazioni/nuova', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/segnalazioni', [TicketController::class, 'store'])->middleware('throttle:10,10')->name('tickets.store');
    });

    // Produzione, compensi ed extra gare: ognuno vede i dealer che stanno sotto di lui.
    Route::middleware('role:dealer,agent,capoarea,admin,superadmin')->group(function () {
        Route::get('/produzione', [ProductionController::class, 'index'])->name('production.index');
        Route::get('/produzione/dealer/{dealer}', [ProductionController::class, 'dealer'])->whereNumber('dealer')->name('production.dealer');
        Route::get('/gare', [ContestController::class, 'index'])->name('contests.index');
    });

    Route::get('/in-arrivo/{feature}', [AreaController::class, 'comingSoon'])->middleware('role:dealer,agent')->name('coming-soon');

    Route::prefix('manager')->name('manager.')->middleware('role:admin,superadmin')->group(function () {
        Route::get('/', [RequestController::class, 'index'])->name('requests');
        Route::post('/richieste/{registration}/approva', [RequestController::class, 'approve'])->name('requests.approve');
        Route::post('/richieste/{registration}/informazioni', [RequestController::class, 'askInfo'])->name('requests.info');
        Route::post('/richieste/{registration}/rifiuta', [RequestController::class, 'reject'])->name('requests.reject');

        Route::get('/promo', [PromoController::class, 'index'])->name('promos');
        Route::post('/promo', [PromoController::class, 'store'])->name('promos.store');
        Route::post('/promo/{promo}/chiudi', [PromoController::class, 'close'])->name('promos.close');
        Route::post('/catalogo-sharers', [PromoController::class, 'saveShowcase'])->name('showcase.save');

        Route::get('/ordini', [ManagerOrderController::class, 'index'])->name('orders');
        Route::post('/ordini/{order}/pagato', [ManagerOrderController::class, 'markPaid'])->name('orders.paid');

        Route::get('/anagrafiche', [ManagerRegistryController::class, 'index'])->name('registries');
        Route::get('/anagrafiche/documenti/{document}', [ManagerRegistryController::class, 'document'])->name('registries.document');
        Route::post('/anagrafiche/{dealer}/attiva', [ManagerRegistryController::class, 'activate'])->name('registries.activate');

        Route::get('/segnalazioni', [ManagerTicketController::class, 'index'])->name('tickets');

        Route::get('/materiali', [ContentController::class, 'index'])->name('content');
        Route::post('/materiali', [ContentController::class, 'storeResource'])->name('content.resource.store');
        Route::post('/materiali/{resource}/elimina', [ContentController::class, 'destroyResource'])->name('content.resource.destroy');
        Route::post('/faq', [ContentController::class, 'storeFaq'])->name('content.faq.store');
        Route::post('/faq/{faq}', [ContentController::class, 'updateFaq'])->name('content.faq.update');
        Route::post('/faq/{faq}/elimina', [ContentController::class, 'destroyFaq'])->name('content.faq.destroy');

        Route::get('/produzione', [ProductionImportController::class, 'index'])->name('production.import');
        Route::post('/produzione', [ProductionImportController::class, 'store'])->middleware('throttle:10,10')->name('production.import.store');
        Route::post('/produzione/righe/{row}/forza', [ProductionImportController::class, 'force'])->whereNumber('row')->name('production.force');
        Route::get('/produzione/righe/nuova', [ProductionRowController::class, 'create'])->name('production.row.create');
        Route::post('/produzione/righe', [ProductionRowController::class, 'store'])->name('production.row.store');
        Route::get('/produzione/righe/{row}/modifica', [ProductionRowController::class, 'edit'])->whereNumber('row')->name('production.row.edit');
        Route::post('/produzione/righe/{row}', [ProductionRowController::class, 'update'])->whereNumber('row')->name('production.row.update');
        Route::post('/produzione/righe/{row}/elimina', [ProductionRowController::class, 'destroy'])->whereNumber('row')->name('production.row.destroy');
        Route::post('/produzione/rettifiche', [ProductionRowController::class, 'storeAdjustment'])->name('production.adjust.store');
        Route::post('/produzione/rettifiche/{adjustment}/elimina', [ProductionRowController::class, 'destroyAdjustment'])->whereNumber('adjustment')->name('production.adjust.destroy');
        Route::get('/compensi', [CommissionPlanController::class, 'index'])->name('plan');
        Route::post('/compensi', [CommissionPlanController::class, 'update'])->name('plan.update');
        Route::post('/gare', [ContestController::class, 'store'])->name('contests.store');
        Route::post('/gare/{contest}/chiudi', [ContestController::class, 'close'])->name('contests.close');

        Route::get('/elenchi/{tab?}', [DirectoryController::class, 'index'])->where('tab', 'dealer|aree|ambassador|agenti|capoarea')->name('directory');
        Route::post('/elenchi/dealer', [DirectoryController::class, 'storeDealer'])->name('directory.dealer.store');
        Route::post('/elenchi/dealer/{dealer}', [DirectoryController::class, 'updateDealer'])->name('directory.dealer.update');
        Route::post('/elenchi/dealer/{dealer}/elimina', [DirectoryController::class, 'destroyDealer'])->name('directory.dealer.destroy');
        Route::post('/elenchi/aree', [DirectoryController::class, 'storeArea'])->name('directory.area.store');
        Route::post('/elenchi/aree/{area}', [DirectoryController::class, 'updateArea'])->name('directory.area.update');
        Route::post('/elenchi/aree/{area}/elimina', [DirectoryController::class, 'destroyArea'])->name('directory.area.destroy');
        Route::post('/elenchi/ambassador', [DirectoryController::class, 'storeAmbassador'])->name('directory.ambassador.store');
        Route::post('/elenchi/ambassador/{ambassador}', [DirectoryController::class, 'updateAmbassador'])->name('directory.ambassador.update');
        Route::post('/elenchi/ambassador/{ambassador}/elimina', [DirectoryController::class, 'destroyAmbassador'])->name('directory.ambassador.destroy');
        Route::post('/elenchi/agenti', [DirectoryController::class, 'storeAgent'])->name('directory.agent.store');
        Route::post('/elenchi/agenti/{agent}', [DirectoryController::class, 'updateAgent'])->name('directory.agent.update');
        Route::post('/elenchi/capoarea/{areaHead}', [DirectoryController::class, 'updateAreaHead'])->name('directory.areahead.update');

        Route::get('/rete', [NetworkController::class, 'index'])->name('network');
        Route::post('/rete/agenti/{agent}/capoarea', [NetworkController::class, 'assignAreaHead'])->name('network.area-head');
        Route::post('/rete/staff', [NetworkController::class, 'storeStaff'])->name('network.staff');
        Route::post('/rete/dealer/{dealer}/agente', [NetworkController::class, 'assign'])->name('network.assign');
        Route::post('/rete/utenti/{user}/password', [NetworkController::class, 'resendPassword'])->name('network.password');
        Route::post('/rete/utenti/{user}/stato', [NetworkController::class, 'toggleActive'])->name('network.toggle');
    });
});
