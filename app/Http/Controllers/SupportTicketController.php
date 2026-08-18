<?php

namespace App\Http\Controllers;

use App\Mail\SupportTicketBusinessReplied;
use App\Mail\SupportTicketCreated;
use App\Mail\SupportTicketReceived;
use App\Models\Admin;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * The business's own support tickets. Gated server-side by
 * Business::hasSupportAccess() (the standalone Priority Support
 * subscription) — a business without it gets a 404 here even if they
 * guess the URL, not just a hidden nav link.
 */
class SupportTicketController extends Controller
{
    public function index(): View
    {
        $business = Auth::user()->business;

        abort_unless($business->hasSupportAccess(), 404);

        $tickets = SupportTicket::latest()->paginate(25);

        return view('support.index', compact('tickets'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()->business->hasSupportAccess(), 404);

        return view('support.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($business->hasSupportAccess(), 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            ...$validated,
        ])->fresh();

        $this->notifyAdmins(new SupportTicketCreated($ticket));

        try {
            Mail::to(Auth::user()->email)->send(new SupportTicketReceived($ticket));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('support.show', $ticket)
            ->with('status', "Ticket {$ticket->tracking_number} submitted.");
    }

    public function show(SupportTicket $ticket): View
    {
        abort_unless(Auth::user()->business->hasSupportAccess(), 404);

        $ticket->load(['replies.admin', 'replies.user', 'user']);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless(Auth::user()->business->hasSupportAccess(), 404);

        abort_if(in_array($ticket->status, ['resolved', 'closed'], true), 422, 'This ticket is closed. Please open a new ticket if you need further help.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
        ]);

        $this->notifyAdmins(new SupportTicketBusinessReplied($reply));

        return redirect()->route('support.show', $ticket)->with('status', 'Reply sent.');
    }

    private function notifyAdmins($mailable): void
    {
        Admin::pluck('email')->each(function (string $email) use ($mailable) {
            try {
                Mail::to($email)->send($mailable);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
