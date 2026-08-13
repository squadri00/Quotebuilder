<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Announcements</h2>
    </x-slot>

    @if ($announcements->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No announcements yet.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($announcements as $announcement)
                @php
                    $isDismissed = $announcement->reads->first()?->dismissed_at !== null;
                    $sevClasses = match ($announcement->severity) {
                        'critical' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950',
                        'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950',
                        default => 'border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950',
                    };
                @endphp
                <x-card class="{{ $sevClasses }} {{ $isDismissed ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $announcement->title }}
                                @if ($announcement->isExpired())
                                    <span class="ml-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Expired</span>
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $announcement->message }}</p>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $announcement->created_at->format('M j, Y g:ia') }}</p>
                        </div>

                        @unless ($isDismissed)
                            <form method="POST" action="{{ route('announcements.dismiss', $announcement) }}">
                                @csrf
                                <button type="submit" class="shrink-0 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                    Dismiss
                                </button>
                            </form>
                        @endunless
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
