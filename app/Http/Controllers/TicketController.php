<?php

namespace App\Http\Controllers;

use App\Mail\TicketMail;
use App\Models\ActivityLog;
use App\Models\Dealer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Segnalazioni al back office: dealer e agenti le aprono, i manager rispondono. */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tickets = Ticket::query()->with('dealer')
            ->where(function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->role === User::ROLE_AGENT) {
                    $q->orWhere('agent_id', $user->agent?->id);
                }
            })
            ->latest('last_message_at')->get();

        return view('tickets.index', compact('tickets'));
    }

    public function create(Request $request)
    {
        return view('tickets.create', ['dealers' => $this->portfolio($request->user())]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $portfolio = $this->portfolio($user);
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(config('effepi.ticket_categories')))],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'dealer_id' => ['nullable', Rule::in($portfolio->pluck('id')->all())],
        ]);

        $dealer = $user->role === User::ROLE_DEALER ? $user->dealer : $portfolio->firstWhere('id', (int) ($data['dealer_id'] ?? 0));
        $agentId = $user->role === User::ROLE_AGENT ? $user->agent?->id : $dealer?->agent_id;

        $ticket = DB::transaction(function () use ($user, $data, $dealer, $agentId) {
            $ticket = Ticket::query()->create([
                'user_id' => $user->id, 'dealer_id' => $dealer?->id, 'agent_id' => $agentId,
                'category' => $data['category'], 'subject' => $data['subject'],
                'status' => Ticket::OPEN, 'last_message_at' => now(),
            ]);
            $ticket->messages()->create(['user_id' => $user->id, 'is_staff' => false, 'body' => $data['body']]);

            return $ticket;
        });
        ActivityLog::record('ticket.opened', $ticket);
        Mail::to(config('effepi.mail.backoffice'))->send(new TicketMail($ticket, $ticket->messages()->first()->load('user'), true));

        return redirect()->route('tickets.show', $ticket)->with('status', 'Segnalazione inviata: ti risponde il back office.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->visibleTo($request->user()), 403);

        return view('tickets.show', ['ticket' => $ticket->load('messages.user', 'dealer', 'user')]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless($ticket->visibleTo($user), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $staff = $user->isBackOffice();

        $message = $ticket->messages()->create(['user_id' => $user->id, 'is_staff' => $staff, 'body' => $data['body']]);
        $ticket->update(['status' => $staff ? Ticket::ANSWERED : Ticket::OPEN, 'last_message_at' => now()]);

        if ($staff) {
            Mail::to($ticket->user->email)->send(new TicketMail($ticket, $message->load('user'), false));
        } else {
            Mail::to(config('effepi.mail.backoffice'))->send(new TicketMail($ticket, $message->load('user'), true));
        }

        return back()->with('status', 'Messaggio inviato.');
    }

    public function close(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->visibleTo($request->user()), 403);
        $ticket->update(['status' => Ticket::CLOSED]);
        ActivityLog::record('ticket.closed', $ticket);

        return back()->with('status', 'Segnalazione chiusa. Se serve, scrivi di nuovo per riaprirla.');
    }

    /** Dealer per cui si può aprire una segnalazione: il proprio negozio, o il portafoglio dell'agente. */
    private function portfolio(User $user)
    {
        if ($user->role === User::ROLE_AGENT) {
            return $user->agent?->dealers()->orderBy('business_name')->get() ?? collect();
        }
        if ($user->role === User::ROLE_DEALER) {
            return Dealer::query()->whereKey($user->dealer?->id)->get();
        }

        return collect();
    }
}
