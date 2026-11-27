<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        return view('manager.orders', ['orders' => Order::query()->with(['dealer', 'user'])->latest('id')->limit(300)->get()]);
    }

    /** Il bonifico è arrivato: l'ordine risulta pagato, con nome di chi lo ha segnato. */
    public function markPaid(Order $order)
    {
        if (! $order->isPaid()) {
            $order->update(['payment_status' => 'pagato', 'paid_at' => now(), 'paid_marked_by' => auth()->id()]);
            ActivityLog::record('order.paid', $order);
        }

        return back()->with('status', 'Ordine '.$order->number.' segnato come pagato.');
    }
}
