@props(['status'])

@php
    $map = [
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-green-100 text-green-800',
        'active' => 'bg-green-100 text-green-800',
        'on_payout' => 'bg-blue-100 text-blue-800',
        'paid' => 'bg-green-100 text-green-800',
        'void' => 'bg-red-100 text-red-800',
        'rejected' => 'bg-red-100 text-red-800',
        'suspended' => 'bg-slate-200 text-slate-700',
        'ended' => 'bg-slate-200 text-slate-700',
        'open' => 'bg-blue-100 text-blue-800',
        'working' => 'bg-amber-100 text-amber-800',
        'won' => 'bg-green-100 text-green-800',
        'lost' => 'bg-slate-200 text-slate-700',
        'expired' => 'bg-slate-200 text-slate-700',
        'released' => 'bg-slate-200 text-slate-700',
        'draft' => 'bg-slate-200 text-slate-700',
        'finalized' => 'bg-blue-100 text-blue-800',
        'submitted' => 'bg-amber-100 text-amber-800',
        'trial' => 'bg-blue-100 text-blue-800',
    ];
    $cls = $map[$status] ?? 'bg-slate-200 text-slate-700';
@endphp

<span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $cls }}">{{ str_replace('_', ' ', $status) }}</span>
