<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\AffiliateProspect;
use App\Models\Country;
use App\Models\Plan;
use App\Services\Affiliate\AffiliateSettings;
use App\Services\Affiliate\ProspectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProspectController extends PortalController
{
    public function __construct(private ProspectService $prospects)
    {
    }

    public function index(Request $request): View
    {
        $partner = $this->partner();

        return view('affiliate.portal.prospects', [
            'partner' => $partner,
            'countries' => Country::orderBy('name')->pluck('name'),
            'plans' => Plan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'mine' => AffiliateProspect::where('partner_id', $partner->id)
                ->orderByRaw("CASE status WHEN 'working' THEN 0 WHEN 'open' THEN 1 WHEN 'won' THEN 2 WHEN 'lost' THEN 3 WHEN 'expired' THEN 4 ELSE 5 END")
                ->orderBy('claim_expires_at')
                ->get(),
            'board' => $this->prospects->marketBoard($request->query('q'), $partner->id),
            'claimDays' => AffiliateSettings::claimDays(),
            'editing' => $request->integer('edit') ?: null,
            'conflict' => session('conflict'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->prospects->claim($this->partner()->id, $this->input($request));

        if ($result['ok']) {
            return redirect()->route('affiliate.portal.prospects')
                ->with('status', 'Prospect claimed — it is now locked to you.');
        }

        return back()->withInput()->withErrors(['company_name' => $result['error']])
            ->with('conflict', $result['conflict'] ?? null);
    }

    public function update(Request $request, AffiliateProspect $prospect): RedirectResponse
    {
        $result = $this->prospects->update($this->partner()->id, $prospect->id, $this->input($request));

        if ($result['ok']) {
            return redirect()->route('affiliate.portal.prospects')->with('status', 'Prospect updated.');
        }

        return back()->withInput()->withErrors(['company_name' => $result['error']])
            ->with('conflict', $result['conflict'] ?? null);
    }

    public function release(AffiliateProspect $prospect): RedirectResponse
    {
        $this->prospects->release($prospect->id, $this->partner()->id);

        return back()->with('status', 'Claim released — other partners can now take it.');
    }

    public function renew(AffiliateProspect $prospect): RedirectResponse
    {
        $this->prospects->renew($prospect->id, $this->partner()->id);

        return back()->with('status', 'Claim extended.');
    }

    private function input(Request $request): array
    {
        return $request->only([
            'company_name', 'contact_name', 'phone', 'email', 'website', 'city',
            'state_province', 'country', 'industry', 'estimated_plan_id', 'notes', 'status',
        ]);
    }
}
