<?php

namespace App\Services;

use App\Models\RegistrationRequest;
use App\Models\User;
use Carbon\CarbonInterface;

/** Nessun calendario esterno: gli impegni sono solo le prenotazioni salvate nel portale. */
class LocalCalendar implements CalendarGateway
{
    public function busySlots(User $manager, CarbonInterface $day): array
    {
        return [];
    }

    public function createMeeting(RegistrationRequest $request): array
    {
        return ['event_id' => null, 'meet_url' => null];
    }
}
