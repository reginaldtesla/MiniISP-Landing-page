<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ticket::query()->with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tickets = $query->paginate(25)->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'statuses' => Ticket::statuses(),
            'filterStatus' => $request->input('status'),
        ]);
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in-progress,closed'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return back()->with('status', 'Ticket status updated.');
    }
}
