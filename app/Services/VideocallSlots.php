<?php

namespace App\Services;

use App\Models\RegistrationRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Orari prenotabili per la videocall di presentazione. */
class VideocallSlots
{
    public function __construct(private CalendarGateway $calendar) {}

    /** Il manager che segue le videocall di una regione. */
    public function managerFor(string $region): ?User
    {
        $username = config('effepi.manager_by_region')[$region] ?? config('effepi.default_manager');

        return User::query()->where('role', User::ROLE_ADMIN)->where('username', $username)->first()
            ?? User::query()->where('role', User::ROLE_ADMIN)->where('active', true)->orderBy('id')->first();
    }

    public function isBookableDay(CarbonInterface $day): bool
    {
        $cfg = config('effepi.videocall');
        $today = CarbonImmutable::today();

        return in_array($day->isoWeekday(), $cfg['weekdays'], true)
            && $day->greaterThanOrEqualTo($today)
            && $day->lessThanOrEqualTo($today->addDays($cfg['max_days_ahead']));
    }

    /**
     * Tutti gli orari teorici della giornata (14:00 … 18:30).
     *
     * @return list<string>
     */
    public function daySlots(): array
    {
        $cfg = config('effepi.videocall');
        $slots = [];
        $cursor = CarbonImmutable::createFromFormat('H:i', $cfg['start']);
        $end = CarbonImmutable::createFromFormat('H:i', $cfg['end']);
        while ($cursor->addMinutes($cfg['slot_minutes'])->lessThanOrEqualTo($end)) {
            $slots[] = $cursor->format('H:i');
            $cursor = $cursor->addMinutes($cfg['slot_minutes']);
        }

        return $slots;
    }

    /**
     * Orari ancora liberi per quel giorno e quella regione.
     *
     * @return list<string>
     */
    public function available(CarbonInterface $day, string $region): array
    {
        $day = CarbonImmutable::parse($day->toDateString());
        if (! $this->isBookableDay($day)) {
            return [];
        }
        $manager = $this->managerFor($region);
        $earliest = CarbonImmutable::now()->addHours(config('effepi.videocall.min_notice_hours'));

        $taken = RegistrationRequest::query()
            ->where('status', '!=', RegistrationRequest::REJECTED)
            ->when($manager, fn ($q) => $q->where('meeting_manager_id', $manager->id))
            ->whereDate('meeting_at', $day->toDateString())
            ->get()
            ->map(fn (RegistrationRequest $r) => $r->meeting_at->format('H:i'))
            ->all();
        if ($manager) {
            $taken = array_merge($taken, $this->calendar->busySlots($manager, $day));
        }

        return array_values(array_filter(
            $this->daySlots(),
            fn (string $slot) => ! in_array($slot, $taken, true)
                && $day->setTimeFromTimeString($slot)->greaterThan($earliest),
        ));
    }
}
