@props(['status'])

@php
    $map = [
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'on_payout' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'void' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'suspended' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'ended' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'open' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'working' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'won' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'lost' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'expired' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'released' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'draft' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'finalized' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'submitted' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    ];
    $cls = $map[$status] ?? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
@endphp

<span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $cls }}">{{ str_replace('_', ' ', $status) }}</span>
