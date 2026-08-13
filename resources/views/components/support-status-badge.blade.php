@props(['status'])

@php
$styles = [
    'open' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    'in_progress' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
    'resolved' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    'closed' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
][$status] ?? 'bg-gray-100 text-gray-600';

$labels = [
    'open' => 'Open',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $styles"]) }}>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
