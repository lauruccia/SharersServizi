<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Ticket;

class TicketController extends Controller
{
    public function index()
    {
        return view('manager.tickets', [
            'tickets' => Ticket::query()->with(['user', 'dealer'])
                ->orderByRaw("case status when 'open' then 0 when 'answered' then 1 else 2 end")
                ->latest('last_message_at')->limit(200)->get(),
        ]);
    }
}
