<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\SupportTicketReplied;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        // No web-guard user is authenticated here, so BelongsToBusiness's
        // global scope is a no-op — this legitimately sees every
        // business's tickets, same pattern as BusinessController@index.
        $query = SupportTicket::with('business');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $tickets = $query->latest()->paginate(25)->withQueryString();

        return view('superadmin.support.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['business', 'user', 'replies.admin', 'replies.user']);

        return view('superadmin.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $reply = $ticket->replies()->create([
            'admin_id' => Auth::guard('admin')->id(),
            'message' => $validated['message'],
        ]);

        $ticket->markInProgressIfOpen();

        $recipients = $ticket->business->users()->where('is_active', true)->pluck('email');

        foreach ($recipients as $email) {
            Mail::to($email)->send(new SupportTicketReplied($reply));
        }

        return redirect()->route('superadmin.support.show', $ticket)->with('status', 'Reply sent.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return back()->with('status', 'Ticket status updated.');
    }
}
