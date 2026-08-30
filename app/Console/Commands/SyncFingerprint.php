<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncEngine;
use Illuminate\Console\Command;

class SyncFingerprint extends Command
{
    protected $signature = 'sync:fingerprint {--manifest : include the full file manifest} {--fetch : git fetch first}';

    protected $description = 'Print this environment\'s sync fingerprint as JSON';

    public function handle(): int
    {
        $this->line(json_encode(
            SyncEngine::fingerprint([
                'include_manifest' => (bool) $this->option('manifest'),
                'fetch'            => (bool) $this->option('fetch'),
            ]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));

        return self::SUCCESS;
    }
}
