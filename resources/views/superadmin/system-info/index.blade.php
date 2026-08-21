<x-superadmin-layout title="System Info">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">System Info</h2>
    </x-slot>

    <p class="max-w-3xl mb-6 text-sm text-gray-500 dark:text-gray-400">
        A read-only snapshot of how this server is currently configured — check this yourself before contacting a developer or your host, or hand these details to them directly. Nothing here can be edited, and no passwords or secret keys are ever shown.
    </p>

    <div class="max-w-6xl columns-1 lg:columns-2 gap-6 [column-fill:_balance]">

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Environment</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Environment</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($appEnv) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Debug Mode</dt>
                    <dd>
                        @if ($appDebug)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">ON — errors show visitors full details</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Off</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Site URL</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100 font-mono text-xs">{{ $appUrl }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">This request arrived over</dt>
                    <dd>
                        @if ($requestIsSecure)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">HTTPS</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">HTTP (not secure)</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Session Cookie</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $sessionDriver }} · Secure: {{ $sessionSecureCookie }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Versions</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">PHP</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $phpVersion }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Laravel</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $laravelVersion }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Platform Version</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $settings?->version ?: '— not set —' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Database</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Connection</dt>
                    <dd>
                        @if ($databaseStatus['connected'])
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">{{ $databaseStatus['message'] }}</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ $databaseStatus['message'] }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Driver</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $databaseDriver }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Background Jobs</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Queue Driver</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $queueConnection }}</dd>
                </div>
                @if ($pendingJobs !== null)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Pending Jobs</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $pendingJobs }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Failed Jobs</dt>
                        <dd class="font-medium {{ $failedJobs > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $failedJobs }}</dd>
                    </div>
                    @if ($queueConnection !== 'sync' && $pendingJobs > 0)
                        <p class="text-xs text-amber-700 dark:text-amber-400">Jobs are piling up — this usually means the background worker process (<code class="text-xs">php artisan queue:work</code>) isn't running on the server.</p>
                    @endif
                @endif
            </dl>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Stripe</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Keys Configured</dt>
                    <dd>
                        @if ($stripeConfigured)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Yes</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Not configured</span>
                        @endif
                    </dd>
                </div>
                @if ($stripeMode)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Mode</dt>
                        <dd>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stripeMode === 'Live' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' }}">{{ $stripeMode }}</span>
                        </dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Webhook Secret</dt>
                    <dd>
                        @if ($webhookSecretConfigured)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Set</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Missing — webhooks will fail</span>
                        @endif
                    </dd>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Change these from Platform Settings, not here.</p>
            </dl>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Mail</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Mailer</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $mailMailer }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">From Address</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100 text-xs">{{ $mailFromAddress ?: '— not set —' }}</dd>
                </div>
                @if ($mailMailer === 'log')
                    <p class="text-xs text-amber-700 dark:text-amber-400">Emails are being written to the log file, not actually sent — fine for testing, not for a real launch.</p>
                @endif
            </dl>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Storage &amp; Logs</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">File Storage Link</dt>
                    <dd>
                        @if ($storageLinkExists)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Connected</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Missing — run storage:link</span>
                        @endif
                    </dd>
                </div>
                @if ($diskFreeGb !== null)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Disk Space Free</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $diskFreeGb }} GB @if($diskTotalGb) of {{ $diskTotalGb }} GB @endif</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Log Channel</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $logChannel }}@if($logStack) ({{ $logStack }}) @endif</dd>
                </div>
            </dl>
        </x-card>

    </div>
</x-superadmin-layout>
