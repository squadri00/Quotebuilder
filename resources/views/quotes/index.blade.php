<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Quotes</h2>
            <x-primary-button onclick="window.location='{{ route('quotes.create') }}'">New Quote</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="GET" action="{{ route('quotes.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <x-input-label for="filter-search" value="Search" class="!text-xs" />
            <x-text-input id="filter-search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Customer name or email…" class="w-full" />
        </div>

        <div>
            <x-input-label for="filter-source" value="Source" class="!text-xs" />
            <select id="filter-source" name="source" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="">All</option>
                <option value="public" @selected($filters['source'] === 'public')>Public</option>
                <option value="internal" @selected($filters['source'] === 'internal')>Internal</option>
            </select>
        </div>

        @if ($statusTrackingEnabled)
            <div>
                <x-input-label for="filter-status" value="Status" class="!text-xs" />
                <select id="filter-status" name="status" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">All</option>
                    @foreach (\App\Models\Quote::STATUSES as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($products->count() > 1)
            <div>
                <x-input-label for="filter-product" value="Product" class="!text-xs" />
                <select id="filter-product" name="product" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">All</option>
                    @foreach ($products as $productOption)
                        <option value="{{ $productOption->id }}" @selected((string) $filters['productId'] === (string) $productOption->id)>{{ $productOption->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <x-input-label for="filter-sort" value="Sort" class="!text-xs" />
            <select id="filter-sort" name="sort" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                <option value="price_high" @selected($filters['sort'] === 'price_high')>Price: high to low</option>
                <option value="price_low" @selected($filters['sort'] === 'price_low')>Price: low to high</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <x-primary-button>Filter</x-primary-button>
            @if ($filters['search'] !== '' || $filters['source'] !== '' || $filters['status'] !== '' || $filters['productId'] !== '' || $filters['sort'] !== 'newest')
                <a href="{{ route('quotes.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Clear</a>
            @endif
        </div>
    </form>

    @if ($quotes->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No quotes submitted yet.
        </x-card>
    @else
        <x-card class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Quote #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Price</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                            @if ($statusTrackingEnabled)
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            @endif
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($quotes as $quote)
                            <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40" onclick="window.location='{{ route('quotes.show', $quote) }}'">
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                    #{{ $quote->displayReference() }}
                                    @if ($quote->revises_quote_id)
                                        <span class="block text-xs font-normal text-gray-400 dark:text-gray-500">revision of #{{ $quote->revisesQuote?->displayReference() }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($quote->isInternal())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                                            title="{{ $quote->createdBy?->name ? 'Created by '.$quote->createdBy->name : 'Created by staff' }}{{ $quote->internal_notes ? ' — has internal notes' : '' }}">
                                            Internal
                                            @if ($quote->internal_notes)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            Public
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $quote->customer_name }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $quote->customer_email }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $quote->product?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    ${{ number_format($quote->final_price, 2) }}
                                    @if ($quote->hasPriceOverride())
                                        <span class="block text-xs text-amber-600 dark:text-amber-400">was ${{ number_format($quote->calculated_price, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $quote->created_at->format('M j, Y g:i A') }}</td>
                                @if ($statusTrackingEnabled)
                                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                                        <form method="POST" action="{{ route('quotes.update-status', $quote) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()"
                                                class="rounded-lg border-gray-300 text-xs font-medium shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                                @foreach (\App\Models\Quote::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected($quote->status === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                @endif
                                <td class="px-4 py-3" onclick="event.stopPropagation()">
                                    @if ($quote->product)
                                        <a href="{{ route('quotes.edit', $quote) }}" class="text-sm font-medium brand-text hover:underline">
                                            Edit / Re-quote
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500" title="The product this quote was built from no longer exists">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $quotes->links() }}
        </div>
    @endif
</x-app-layout>
