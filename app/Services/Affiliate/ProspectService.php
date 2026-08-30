<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProspect;
use Illuminate\Support\Carbon;

class ProspectService
{
    public function claimExpiryFrom(?Carbon $from = null): Carbon
    {
        return ($from ?? now())->copy()->addDays(AffiliateSettings::claimDays());
    }

    /**
     * The active claim (held by anyone) that collides with these keys, or null.
     */
    public function findConflict(array $keys, ?int $excludeId = null): ?AffiliateProspect
    {
        $query = AffiliateProspect::activeClaims()->with('partner');

        $query->where(function ($q) use ($keys) {
            foreach (['company_name_norm', 'phone_norm', 'email_norm', 'domain_norm'] as $k) {
                if (! empty($keys[$k])) {
                    $q->orWhere($k, $keys[$k]);
                }
            }
        });

        // If every key was empty the closure added no constraints — nothing to conflict with.
        if (empty(array_filter($keys))) {
            return null;
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('claimed_at')->first();
    }

    /**
     * @return array{ok:bool, prospect?:AffiliateProspect, conflict?:AffiliateProspect, error?:string}
     */
    public function claim(int $partnerId, array $data): array
    {
        $company = trim((string) ($data['company_name'] ?? ''));
        if ($company === '') {
            return ['ok' => false, 'error' => 'Company name is required.'];
        }

        $keys = Normalizer::keys($data);

        if ($conflict = $this->findConflict($keys)) {
            return [
                'ok' => false,
                'conflict' => $conflict,
                'error' => 'This prospect is already claimed. It becomes available on '
                    . $conflict->claim_expires_at->format('M j, Y') . '.',
            ];
        }

        $prospect = AffiliateProspect::create(array_merge(
            $this->fillable($data, $keys),
            [
                'partner_id' => $partnerId,
                'company_name' => $company,
                'status' => 'open',
                'claimed_at' => now(),
                'last_activity_at' => now(),
                'claim_expires_at' => $this->claimExpiryFrom(),
            ]
        ));

        return ['ok' => true, 'prospect' => $prospect];
    }

    /**
     * @return array{ok:bool, conflict?:AffiliateProspect, error?:string}
     */
    public function update(int $partnerId, int $prospectId, array $data): array
    {
        $prospect = AffiliateProspect::where('id', $prospectId)
            ->where('partner_id', $partnerId)->first();

        if (! $prospect) {
            return ['ok' => false, 'error' => 'Prospect not found.'];
        }
        if (in_array($prospect->status, ['won', 'released'], true)) {
            return ['ok' => false, 'error' => 'This prospect can no longer be edited.'];
        }

        $company = trim((string) ($data['company_name'] ?? $prospect->company_name));
        if ($company === '') {
            return ['ok' => false, 'error' => 'Company name is required.'];
        }

        $keys = Normalizer::keys([
            'company_name' => $company,
            'phone' => $data['phone'] ?? $prospect->phone,
            'email' => $data['email'] ?? $prospect->email,
            'website' => $data['website'] ?? $prospect->website,
        ]);

        if ($conflict = $this->findConflict($keys, $prospect->id)) {
            return [
                'ok' => false,
                'conflict' => $conflict,
                'error' => 'Those details now match a claim held by another partner.',
            ];
        }

        $status = in_array($data['status'] ?? '', ['open', 'working', 'lost'], true)
            ? $data['status'] : $prospect->status;

        $attrs = array_merge($this->fillable($data, $keys), [
            'company_name' => $company,
            'status' => $status,
            'last_activity_at' => now(),
        ]);

        if (AffiliateSettings::claimRenewsOnActivity() && in_array($status, ['open', 'working'], true)) {
            $attrs['claim_expires_at'] = $this->claimExpiryFrom();
        }
        if ($status === 'lost') {
            $attrs['released_at'] = now();
        }

        $prospect->update($attrs);

        return ['ok' => true];
    }

    public function release(int $prospectId, ?int $partnerId = null, ?int $adminId = null): int
    {
        $query = AffiliateProspect::where('id', $prospectId)
            ->whereIn('status', ['open', 'working']);
        if ($partnerId !== null) {
            $query->where('partner_id', $partnerId);
        }

        return $query->update([
            'status' => 'released',
            'released_at' => now(),
            'released_by' => $adminId,
        ]);
    }

    public function renew(int $prospectId, int $partnerId): int
    {
        return AffiliateProspect::where('id', $prospectId)
            ->where('partner_id', $partnerId)
            ->whereIn('status', ['open', 'working'])
            ->update([
                'last_activity_at' => now(),
                'claim_expires_at' => $this->claimExpiryFrom(),
            ]);
    }

    public function expireStale(): int
    {
        return AffiliateProspect::whereIn('status', ['open', 'working'])
            ->whereNull('released_at')
            ->whereNull('converted_business_id')
            ->where('claim_expires_at', '<=', now())
            ->update(['status' => 'expired', 'released_at' => now()]);
    }

    public function maskCompany(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '—';
        }
        $len = mb_strlen($name);
        if ($len <= 3) {
            return mb_substr($name, 0, 1) . str_repeat('•', 2);
        }
        return mb_substr($name, 0, 2) . str_repeat('•', min(6, $len - 3)) . mb_substr($name, -1);
    }

    /**
     * Shared opportunity board — every partner's active claims, masked.
     */
    public function marketBoard(?string $q = null, ?int $viewerPartnerId = null): \Illuminate\Support\Collection
    {
        $rows = AffiliateProspect::activeClaims()
            ->with('estimatedPlan:id,name')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('city', 'like', "%$q%")
                        ->orWhere('state_province', 'like', "%$q%")
                        ->orWhere('industry', 'like', "%$q%");
                });
            })
            ->orderBy('claim_expires_at')
            ->get();

        return $rows->map(function (AffiliateProspect $p) use ($viewerPartnerId) {
            $mine = $viewerPartnerId && $p->partner_id === $viewerPartnerId;

            return (object) [
                'id' => $p->id,
                'is_mine' => $mine,
                'masked_name' => $mine ? $p->company_name : $this->maskCompany($p->company_name),
                'region' => trim(implode(', ', array_filter([$p->city, $p->state_province]))) ?: '—',
                'industry' => $p->industry,
                'status' => $p->status,
                'estimated_plan' => $p->estimatedPlan?->name,
                'claim_expires_at' => $p->claim_expires_at,
            ];
        });
    }

    private function fillable(array $data, array $keys): array
    {
        return [
            'company_name_norm' => $keys['company_name_norm'],
            'contact_name' => trim((string) ($data['contact_name'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'phone_norm' => $keys['phone_norm'] ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'email_norm' => $keys['email_norm'] ?: null,
            'website' => trim((string) ($data['website'] ?? '')) ?: null,
            'domain_norm' => $keys['domain_norm'] ?: null,
            'city' => trim((string) ($data['city'] ?? '')) ?: null,
            'state_province' => trim((string) ($data['state_province'] ?? '')) ?: null,
            'country' => trim((string) ($data['country'] ?? '')) ?: null,
            'industry' => trim((string) ($data['industry'] ?? '')) ?: null,
            'estimated_plan_id' => ($data['estimated_plan_id'] ?? null) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ];
    }
}
