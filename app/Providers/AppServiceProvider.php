<?php

namespace App\Providers;

use App\Services\CalendarGateway;
use App\Services\FaqAssistant;
use App\Services\KeywordFaqAssistant;
use App\Services\LocalCalendar;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Quando sarà pronto il collegamento a Google Calendar basta cambiare questa riga.
        $this->app->bind(CalendarGateway::class, LocalCalendar::class);
        // Assistente: oggi ricerca sulle FAQ; per passare a un'API di IA basta cambiare questa riga.
        $this->app->bind(FaqAssistant::class, KeywordFaqAssistant::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('it');
        CarbonImmutable::setLocale('it');
    }
}
