<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment Sync
    |--------------------------------------------------------------------------
    |
    | Drift detection between this checkout and the live server. Set the same
    | SYNC_SHARED_SECRET on BOTH environments. Set SYNC_LIVE_ENDPOINT only on
    | the machine that should COMPARE (your laptop) — the live server leaves
    | it blank and runs a self-check.
    |
    */

    'shared_secret' => (string) env('SYNC_SHARED_SECRET', ''),

    // Separate secret for the destructive "apply migrations" endpoint.
    // Falls back to the shared secret when unset.
    'apply_secret' => (string) (env('SYNC_APPLY_SECRET') ?: env('SYNC_SHARED_SECRET', '')),

    // Comparator only. Full URL of the OTHER environment's read endpoint,
    // e.g. https://quotaire.com/sync/endpoint
    'live_endpoint' => (string) env('SYNC_LIVE_ENDPOINT', ''),

    // Comparator only. https://quotaire.com/sync/apply
    'live_apply_endpoint' => (string) env('SYNC_LIVE_APPLY_ENDPOINT', ''),

    // Where the "environments out of sync" e-mail goes. Blank => no e-mail.
    'alert_email' => (string) env('SYNC_ALERT_EMAIL', ''),

    // Do not re-send the same alert more often than this.
    'realert_hours' => (int) env('SYNC_REALERT_HOURS', 12),

    /*
    | Platform-level config tables whose ROWS should match between
    | environments. Tenant data (businesses, users, quotes, orders,
    | invoices, subscriptions…) is deliberately excluded. Row differences
    | here are reported only — never applied automatically.
    */
    'watched_tables' => [
        'platform_settings',
        'plans',
        'plan_feature',
        'plan_features',
        'features',
        'platform_tax_rates',
        'countries',
        'industries',
        'implementation_tiers',
        'support_addons',
        'options',
        'questions',
        'rules',
        'site_pages',
    ],

    /*
    | Tables excluded from the STRUCTURE fingerprint (framework/runtime
    | churn that legitimately differs or does not matter).
    */
    'schema_ignore' => [
        'migrations',
        'cache', 'cache_locks',
        'sessions',
        'jobs', 'job_batches', 'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'telescope_entries', 'telescope_entries_tags', 'telescope_monitoring',
        'pulse_entries', 'pulse_aggregates', 'pulse_values',
    ],

    // Columns dropped from config-table row hashes (they drift harmlessly).
    'volatile_columns' => [
        'created_at', 'updated_at', 'deleted_at',
        'created_by', 'updated_by',
        'last_used_at', 'last_sent_at', 'sent_at', 'last_run_at',
        'remember_token',
    ],
];
