<?php

namespace App\Services;

use App\Models\RegistrationRequest;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Collegamento al calendario dei manager.
 * Oggi è attiva l'implementazione locale (LocalCalendar); quella per Google
 * Calendar e Meet si aggancia qui senza toccare il resto (docs/06-integrazioni.md).
 */
interface CalendarGateway
{
    /**
     * Orari già occupati sul calendario esterno del manager in quel giorno.
     *
     * @return list<string> orari "H:i"
     */
    public function busySlots(User $manager, CarbonInterface $day): array;

    /**
     * Crea l'evento della videocall.
     *
     * @return array{event_id: ?string, meet_url: ?string}
     */
    public function createMeeting(RegistrationRequest $request): array;
}
