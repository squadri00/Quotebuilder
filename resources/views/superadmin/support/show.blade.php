<x-superadmin-layout :title="$ticket->subject">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $ticket->subject }}</h2>
            <x-support-status-badge :status="$ticket->status" />
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4 max-w-2xl" :status="session('status')" />

    <div class="max-w-2xl">
        <div class="flex items-center justify-between gap-4 mb-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $ticket->tracking_number }} &middot;
                <a href="{{ route('superadmin.businesses.show', $ticket->business) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">{{ $ticket->business->name }}</a>
                &middot; opened by {{ $ticket->user->name }} on {{ $ticket->created_at->format('M j, Y') }}
            </p>

            <form method="POST" action="{{ route('superadmin.support.update-status', $ticket) }}">
                @csrf
                @method('PATCH')
                <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-xs font-medium shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (\App\Models\SupportTicket::STATUSES as $status)
                        <option value="{{ $status }}" @selected($ticket->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="space-y-4">
            <x-card>
                <div class="flex items-center justify-between gap-4 mb-2">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->user->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $ticket->created_at->format('M j, Y g:ia') }}</p>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->message }}</p>
            </x-card>

            @foreach ($ticket->replies as $reply)
                <x-card class="{{ $reply->isFromAdmin() ? 'bg-indigo-50/50 border-indigo-100 dark:border-indigo-800' : '' }}">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $reply->isFromAdmin() ? ($reply->admin->name . ' (you)') : $reply->user->name }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $reply->created_at->format('M j, Y g:ia') }}</p>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->message }}</p>
                </x-card>
            @endforeach
        </div>

        <x-card class="mt-4">
            <form method="POST" action="{{ route('superadmin.support.reply', $ticket) }}">
                @csrf
                <x-input-label for="message" value="Reply" />
                <textarea id="message" name="message" rows="4" required
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-2" />

                <div class="mt-3">
                    <x-primary-button>Send Reply</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-superadmin-layout>
