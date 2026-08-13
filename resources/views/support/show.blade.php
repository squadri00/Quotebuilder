<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $ticket->subject }}</h2>
            <x-support-status-badge :status="$ticket->status" />
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4 max-w-2xl" :status="session('status')" />

    <div class="max-w-2xl">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            {{ $ticket->tracking_number }} &middot; opened {{ $ticket->created_at->format('M j, Y') }}
        </p>

        <div class="space-y-4">
            <x-card>
                <div class="flex items-center justify-between gap-4 mb-2">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->user->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $ticket->created_at->format('M j, Y g:ia') }}</p>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->message }}</p>
            </x-card>

            @foreach ($ticket->replies as $reply)
                <x-card class="{{ $reply->isFromAdmin() ? 'bg-indigo-50/50 dark:bg-indigo-500/5 border-indigo-100 dark:border-indigo-500/20' : '' }}">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $reply->isFromAdmin() ? ($reply->admin->name . ' (' . config('app.name') . ' Support)') : $reply->user->name }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $reply->created_at->format('M j, Y g:ia') }}</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->message }}</p>
                </x-card>
            @endforeach
        </div>

        @if (in_array($ticket->status, ['resolved', 'closed'], true))
            <x-card class="mt-4 bg-gray-50 dark:bg-gray-700/50 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    This ticket is {{ $ticket->status }}.
                    <a href="{{ route('support.create') }}" class="brand-text font-medium">Open a new ticket</a> if you need further help.
                </p>
            </x-card>
        @else
            <x-card class="mt-4">
                <form method="POST" action="{{ route('support.reply', $ticket) }}">
                    @csrf
                    <x-input-label for="message" value="Reply" />
                    <textarea id="message" name="message" rows="4" required
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />

                    <div class="mt-3">
                        <x-primary-button>Send Reply</x-primary-button>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</x-app-layout>
