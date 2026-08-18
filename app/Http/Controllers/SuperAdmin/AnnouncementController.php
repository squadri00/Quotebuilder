<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\AnnouncementNotice;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with(['targetBusiness', 'creator'])->latest()->paginate(20);

        return view('superadmin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('superadmin.announcements.create', [
            'businesses' => $this->businessOptions(),
            'audienceCounts' => $this->audienceCounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $announcement = Announcement::create([
            ...$validated,
            'send_email' => $request->boolean('send_email'),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::guard('admin')->id(),
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        $this->maybeSendEmail($announcement);

        AuditLog::record(
            $request->user('admin'),
            'announcement.created',
            $announcement,
            "Created announcement \"{$announcement->title}\" (audience: {$announcement->target_audience})."
        );

        return redirect()->route('superadmin.announcements.index')->with('status', "Announcement \"{$announcement->title}\" created.");
    }

    public function edit(Announcement $announcement): View
    {
        return view('superadmin.announcements.edit', [
            'announcement' => $announcement,
            'businesses' => $this->businessOptions(),
            'audienceCounts' => $this->audienceCounts(),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $this->validated($request, $announcement);

        $announcement->update([
            ...$validated,
            'send_email' => $request->boolean('send_email'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        $this->maybeSendEmail($announcement);

        AuditLog::record(
            $request->user('admin'),
            'announcement.updated',
            $announcement,
            "Updated announcement \"{$announcement->title}\"."
        );

        return redirect()->route('superadmin.announcements.index')->with('status', "Announcement \"{$announcement->title}\" updated.");
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();

        AuditLog::record(
            $request->user('admin'),
            'announcement.deleted',
            null,
            "Deleted announcement \"{$title}\"."
        );

        return redirect()->route('superadmin.announcements.index')->with('status', "Announcement \"{$title}\" deleted.");
    }

    public function toggleActive(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);

        AuditLog::record(
            $request->user('admin'),
            $announcement->is_active ? 'announcement.activated' : 'announcement.deactivated',
            $announcement,
            ($announcement->is_active ? 'Activated' : 'Withdrew')." announcement \"{$announcement->title}\"."
        );

        return back()->with('status', "\"{$announcement->title}\" is now ".($announcement->is_active ? 'active.' : 'withdrawn.'));
    }

    private function validated(Request $request, ?Announcement $announcement = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'severity' => ['required', Rule::in(Announcement::SEVERITIES)],
            'target_audience' => ['required', Rule::in(Announcement::AUDIENCES)],
            'target_business_id' => ['nullable', Rule::exists('businesses', 'id')],
            'expires_at' => ['nullable', 'date'],
        ];

        $validated = $request->validate($rules);

        if ($validated['target_audience'] !== 'specific') {
            $validated['target_business_id'] = null;
        } elseif (! $validated['target_business_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'target_business_id' => 'Choose a business when targeting a specific business.',
            ]);
        }

        return $validated;
    }

    /**
     * email_sent_at is the guard — once set, editing the announcement
     * (even with send_email still checked) never re-sends. Only a
     * brand-new announcement, or one that was created without sending
     * and then edited to turn sending on, will actually dispatch.
     */
    private function maybeSendEmail(Announcement $announcement): void
    {
        if (! $announcement->send_email || $announcement->email_sent_at) {
            return;
        }

        $businesses = Business::where('is_template', false)
            ->when($announcement->target_audience === 'with_plan', fn ($q) => $q->whereNotNull('plan_id'))
            ->when($announcement->target_audience === 'no_plan', fn ($q) => $q->whereNull('plan_id'))
            ->when($announcement->target_audience === 'specific', fn ($q) => $q->where('id', $announcement->target_business_id))
            ->pluck('id');

        $recipients = User::whereIn('business_id', $businesses)->where('is_active', true)->get(['id', 'name', 'email']);

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new AnnouncementNotice($announcement, $recipient->name));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $announcement->update(['email_sent_at' => now()]);
    }

    private function businessOptions()
    {
        return Business::where('is_template', false)->orderBy('name')->get(['id', 'name']);
    }

    private function audienceCounts(): array
    {
        $base = Business::where('is_template', false);

        return [
            'all' => (clone $base)->count(),
            'with_plan' => (clone $base)->whereNotNull('plan_id')->count(),
            'no_plan' => (clone $base)->whereNull('plan_id')->count(),
        ];
    }
}
