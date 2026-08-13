@props(['announcement' => null, 'businesses', 'audienceCounts'])

<div>
    <x-input-label for="title" value="Title" />
    <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" required autofocus
        :value="old('title', $announcement?->title)" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="message" value="Message" />
    <textarea id="message" name="message" rows="5" required
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">{{ old('message', $announcement?->message) }}</textarea>
    <x-input-error :messages="$errors->get('message')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="severity" value="Severity" />
        <select id="severity" name="severity" required
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
            @foreach (\App\Models\Announcement::SEVERITIES as $severity)
                <option value="{{ $severity }}" @selected(old('severity', $announcement?->severity ?? 'info') === $severity)>{{ ucfirst($severity) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('severity')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="expires_at" value="Expires (optional)" />
        <input id="expires_at" name="expires_at" type="datetime-local"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm"
            value="{{ old('expires_at', $announcement?->expires_at?->format('Y-m-d\TH:i')) }}">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to never expire.</p>
        <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
    </div>
</div>

<div class="mt-4" x-data="{ audience: '{{ old('target_audience', $announcement?->target_audience ?? 'all') }}' }">
    <x-input-label for="target_audience" value="Audience" />
    <select id="target_audience" name="target_audience" x-model="audience" required
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
        <option value="all">All businesses ({{ $audienceCounts['all'] }})</option>
        <option value="with_plan">Businesses with a plan ({{ $audienceCounts['with_plan'] }})</option>
        <option value="no_plan">Businesses without a plan ({{ $audienceCounts['no_plan'] }})</option>
        <option value="specific">One specific business</option>
    </select>
    <x-input-error :messages="$errors->get('target_audience')" class="mt-2" />

    <div class="mt-3" x-show="audience === 'specific'" x-cloak>
        <x-input-label for="target_business_id" value="Business" />
        <select id="target_business_id" name="target_business_id"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
            <option value="">Choose a business&hellip;</option>
            @foreach ($businesses as $business)
                <option value="{{ $business->id }}" @selected((string) old('target_business_id', $announcement?->target_business_id) === (string) $business->id)>{{ $business->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('target_business_id')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="send_email" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('send_email', $announcement?->send_email ?? false))
            @disabled($announcement?->email_sent_at)>
        <span class="text-sm text-gray-700 dark:text-gray-300">Also email recipients</span>
    </label>
    @if ($announcement?->email_sent_at)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Already emailed on {{ $announcement->email_sent_at->format('M j, Y g:ia') }} — editing never re-sends.</p>
    @else
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sends once, the moment you save — not resent on later edits.</p>
    @endif
</div>

<div class="mt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $announcement?->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Active (visible to matching businesses)</span>
    </label>
</div>
