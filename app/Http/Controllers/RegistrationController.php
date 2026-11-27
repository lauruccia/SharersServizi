<?php

namespace App\Http\Controllers;

use App\Mail\NewRegistrationMail;
use App\Mail\RegistrationReceivedMail;
use App\Models\RegistrationRequest;
use App\Services\CalendarGateway;
use App\Services\VideocallSlots;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function __construct(private VideocallSlots $slots, private CalendarGateway $calendar) {}

    /** Orari liberi per data e regione (usato dai due moduli della home). */
    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'data' => ['required', 'date_format:Y-m-d'],
            'regione' => ['required', Rule::in(config('effepi.regions'))],
        ]);
        $day = CarbonImmutable::parse($data['data']);

        return response()->json([
            'bookable' => $this->slots->isBookableDay($day),
            'slots' => $this->slots->available($day, $data['regione']),
        ]);
    }

    public function storeDealer(Request $request)
    {
        $data = $request->validateWithBag('dealer', [
            'business_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'activity_type' => ['required', Rule::in(config('effepi.activity_types'))],
            'region' => ['required', Rule::in(config('effepi.regions'))],
            'municipality' => ['required', 'string', 'max:120'],
            ...$this->commonRules(),
        ]);

        return $this->store('dealer', $data);
    }

    public function storeAgent(Request $request)
    {
        $data = $request->validateWithBag('agent', [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'region' => ['required', Rule::in(config('effepi.regions'))],
            'zones' => ['required', 'string', 'max:255'],
            'experience' => ['nullable', 'string', 'max:3000'],
            ...$this->commonRules(),
        ]);

        return $this->store('agent', $data);
    }

    private function commonRules(): array
    {
        return [
            'meeting_date' => ['required', 'date_format:Y-m-d'],
            'meeting_time' => ['required', 'date_format:H:i'],
            'privacy' => ['accepted'],
            'website' => ['prohibited'], // campo trappola anti-spam: deve restare vuoto
        ];
    }

    private function store(string $profile, array $data)
    {
        $day = CarbonImmutable::parse($data['meeting_date']);

        $registration = DB::transaction(function () use ($profile, $data, $day) {
            // Ricontrollo al salvataggio: due persone non possono prendere lo stesso orario.
            if (! in_array($data['meeting_time'], $this->slots->available($day, $data['region']), true)) {
                throw ValidationException::withMessages([
                    'meeting_time' => 'Questo orario non è più disponibile: scegline un altro.',
                ])->errorBag($profile);
            }

            return RegistrationRequest::query()->create([
                'profile' => $profile,
                'name' => $data['name'],
                'business_name' => $data['business_name'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'activity_type' => $data['activity_type'] ?? null,
                'region' => $data['region'],
                'municipality' => $data['municipality'] ?? null,
                'zones' => $data['zones'] ?? null,
                'experience' => $data['experience'] ?? null,
                'meeting_at' => $day->setTimeFromTimeString($data['meeting_time']),
                'meeting_manager_id' => $this->slots->managerFor($data['region'])?->id,
                'privacy_accepted_at' => now(),
            ]);
        });

        $meeting = $this->calendar->createMeeting($registration);
        $registration->update(['google_event_id' => $meeting['event_id'], 'meet_url' => $meeting['meet_url']]);

        Mail::to($registration->email)->send(new RegistrationReceivedMail($registration));
        Mail::to($registration->manager?->email ?? config('effepi.mail.backoffice'))
            ->send(new NewRegistrationMail($registration));

        return redirect()->route('registration.sent')->with('meeting_at', $registration->meeting_at->toIso8601String());
    }

    public function sent()
    {
        $meetingAt = session('meeting_at') ? CarbonImmutable::parse(session('meeting_at')) : null;

        return view('registration-sent', ['meetingAt' => $meetingAt]);
    }
}
