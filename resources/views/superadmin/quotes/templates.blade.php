<x-superadmin-layout title="Demo Quote">
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Testing / Demo — never a real business</p>
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Demo Quote</h2>
        </div>
    </x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Pick which template to demo. This is a real, fully-working quote (real save, real email, real PDF) —
        it just never touches a real business's account or their Quote Inbox.
    </p>

    @if ($templates->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No templates yet — go to <a href="{{ route('superadmin.templates.index') }}" class="font-medium text-indigo-600 dark:text-indigo-400">Templates</a> and build one.
        </x-card>
    @else
        <div class="space-y-3">
            @foreach ($templates as $template)
                <a href="{{ $template->products_count > 0 ? route('superadmin.quotes.create.products', $template) : '#' }}"
                    @class([
                        'flex items-center justify-between gap-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 transition',
                        'hover:shadow-md' => $template->products_count > 0,
                        'opacity-60 cursor-not-allowed' => $template->products_count === 0,
                    ])
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $template->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $template->industry?->name ?? 'No industry set' }} &middot; {{ $template->products_count }} quotable product(s)
                        </p>
                    </div>
                    @if ($template->products_count > 0)
                        <span class="shrink-0 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 rounded-lg text-sm font-semibold text-white">
                            Choose Product
                        </span>
                    @else
                        <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">Nothing published yet</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
