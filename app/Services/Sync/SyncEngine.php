<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Environment drift detection between this checkout and the live server.
 *
 * Produces a small JSON "fingerprint" of an environment (git state, database
 * schema, Laravel migrations, watched config tables, file-manifest hash) and
 * diffs two fingerprints to a plain-English issue list.
 *
 * Laravel port of Meccora's includes/sync.php. Pure logic — no output.
 */
class SyncEngine
{
    /* =====================================================================
     * Configuration
     * ===================================================================== */

    /**
     * Resolved sync config. A live_endpoint that points at THIS host is
     * blanked — otherwise the box HTTP-calls its own endpoint on every check,
     * which stalls a worker-limited host and "compares" against itself.
     */
    public static function config(): array
    {
        $c = config('sync');
        $live = (string) ($c['live_endpoint'] ?? '');

        $selfHost = request()?->getHost();
        if ($live !== '' && $selfHost) {
            $epHost = parse_url($live, PHP_URL_HOST);
            if ($epHost && strcasecmp($selfHost, (string) $epHost) === 0) {
                $live = '';
                $c['live_apply_endpoint'] = '';
            }
        }

        return [
            'secret'              => (string) ($c['shared_secret'] ?? ''),
            'apply_secret'        => (string) ($c['apply_secret'] ?? '') ?: (string) ($c['shared_secret'] ?? ''),
            'live_endpoint'       => $live,
            'live_apply_endpoint' => (string) ($c['live_apply_endpoint'] ?? ''),
            'alert_email'         => (string) ($c['alert_email'] ?? ''),
            'realert_hours'       => (int) ($c['realert_hours'] ?? 12),
        ];
    }

    public static function projectRoot(): string
    {
        return base_path();
    }

    /** Watched config tables that actually exist in this DB (dedup). */
    public static function watchedTables(): array
    {
        $wanted = array_values(array_unique(config('sync.watched_tables', [])));
        $existing = array_map(
            fn ($r) => strtolower($r->t),
            DB::select(
                "SELECT LOWER(table_name) AS t FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
            )
        );

        return array_values(array_intersect($wanted, $existing));
    }

    /* =====================================================================
     * Git state
     * ===================================================================== */

    public static function hasGitBinary(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        if (! function_exists('shell_exec')) {
            return $has = false;
        }
        $out = @shell_exec('git --version 2>&1');

        return $has = ($out !== null && stripos($out, 'git version') !== false);
    }

    /** Read a ref SHA straight from .git (loose ref or packed-refs). */
    public static function readGitRef(string $root, string $ref): ?string
    {
        $loose = $root.'/.git/'.$ref;
        if (is_file($loose)) {
            $v = trim((string) @file_get_contents($loose));
            if (preg_match('/^[0-9a-f]{40}$/i', $v)) {
                return $v;
            }
            if (str_starts_with($v, 'ref:')) {
                return static::readGitRef($root, trim(substr($v, 4)));
            }
        }
        $packed = $root.'/.git/packed-refs';
        if (is_file($packed)) {
            foreach (file($packed, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] === '#' || $line[0] === '^') {
                    continue;
                }
                [$sha, $name] = array_pad(explode(' ', $line, 2), 2, '');
                if (trim($name) === $ref) {
                    return $sha;
                }
            }
        }

        return null;
    }

    public static function gitState(string $root, bool $fetch = false): array
    {
        $q = escapeshellarg($root);
        $branch = $head = $subject = $date = $originHead = null;
        $dirty = [];
        $ahead = $behind = null;

        $headFile = @file_get_contents($root.'/.git/HEAD');
        if ($headFile && str_starts_with($headFile, 'ref:')) {
            $branch = basename(trim(substr($headFile, 4)));
        }
        if ($branch) {
            $head = static::readGitRef($root, 'refs/heads/'.$branch);
            $originHead = static::readGitRef($root, 'refs/remotes/origin/'.$branch);
        }

        if (static::hasGitBinary()) {
            if ($fetch) {
                @shell_exec("git -C $q fetch --quiet origin 2>&1");
                if ($branch) {
                    $originHead = trim((string) @shell_exec("git -C $q rev-parse origin/$branch 2>&1")) ?: $originHead;
                }
            }
            $branch  = trim((string) @shell_exec("git -C $q rev-parse --abbrev-ref HEAD 2>&1")) ?: $branch;
            $head    = trim((string) @shell_exec("git -C $q rev-parse HEAD 2>&1")) ?: $head;
            $subject = trim((string) @shell_exec("git -C $q log -1 --pretty=%s 2>&1")) ?: null;
            $date    = trim((string) @shell_exec("git -C $q log -1 --pretty=%cI 2>&1")) ?: null;

            $porc = (string) @shell_exec("git -C $q status --porcelain 2>&1");
            $dirtyRaw = array_values(array_filter(array_map('trim', explode("\n", $porc)), 'strlen'));
            $keep = static::pathKeeper($root);
            $dirty = array_values(array_filter($dirtyRaw, static function ($line) use ($keep) {
                $p = trim(substr($line, 2));
                if (str_contains($p, ' -> ')) {
                    $p = substr($p, strpos($p, ' -> ') + 4);
                }

                return $keep(trim($p, " \"'"));
            }));

            if ($branch) {
                $a = trim((string) @shell_exec("git -C $q rev-list --count origin/$branch..HEAD 2>&1"));
                $b = trim((string) @shell_exec("git -C $q rev-list --count HEAD..origin/$branch 2>&1"));
                $ahead  = ctype_digit($a) ? (int) $a : null;
                $behind = ctype_digit($b) ? (int) $b : null;
            }
        }

        return [
            'branch'         => $branch,
            'head'           => $head,
            'head_short'     => $head ? substr($head, 0, 10) : null,
            'head_subject'   => $subject,
            'head_date'      => $date,
            'dirty'          => count($dirty) > 0,
            'dirty_files'    => array_slice($dirty, 0, 50),
            'dirty_count'    => count($dirty),
            'ahead_origin'   => $ahead,
            'behind_origin'  => $behind,
            'origin_head'    => $originHead ?: null,
            'has_git_binary' => static::hasGitBinary(),
        ];
    }

    /** The TRUE origin/<branch> SHA (network). Comparator machine only. */
    public static function remoteHead(string $root, string $branch): ?string
    {
        if (! static::hasGitBinary() || $branch === '' || $branch === 'HEAD') {
            return null;
        }
        $q = escapeshellarg($root);
        $b = escapeshellarg($branch);
        $out = (string) @shell_exec("git -C $q ls-remote origin $b 2>&1");
        if (preg_match('/^([0-9a-f]{40})\s/i', $out, $m)) {
            return $m[1];
        }

        return null;
    }

    /* =====================================================================
     * Database — schema fingerprint (version-stable, from information_schema)
     * ===================================================================== */

    private static function rows(string $sql, array $bindings = []): array
    {
        return array_map(fn ($r) => (array) $r, DB::select($sql, $bindings));
    }

    public static function schemaFingerprint(array &$tableFingerprints): string
    {
        $ignore = array_map('strtolower', config('sync.schema_ignore', []));

        $tables = array_map(
            fn ($r) => strtolower((string) ($r['table_name'] ?? $r['TABLE_NAME'] ?? '')),
            static::rows(
                "SELECT table_name FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
                 ORDER BY table_name"
            )
        );
        $tables = array_values(array_diff($tables, $ignore));

        $allCols = static::rows(
            "SELECT table_name, column_name, column_type, is_nullable, column_default, extra,
                    character_set_name, collation_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             ORDER BY table_name, ordinal_position"
        );
        $allIdx = static::rows(
            "SELECT table_name, index_name, non_unique, seq_in_index, column_name, sub_part
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
             ORDER BY table_name, index_name, seq_in_index"
        );
        $allFks = static::rows(
            "SELECT k.table_name, k.constraint_name, k.column_name, k.referenced_table_name,
                    k.referenced_column_name, r.update_rule, r.delete_rule
             FROM information_schema.key_column_usage k
             JOIN information_schema.referential_constraints r
               ON r.constraint_schema = k.table_schema
              AND r.constraint_name  = k.constraint_name
             WHERE k.table_schema = DATABASE() AND k.referenced_table_name IS NOT NULL
             ORDER BY k.table_name, k.constraint_name, k.ordinal_position"
        );

        $groupByTable = static function (array $rows): array {
            $out = [];
            foreach ($rows as $r) {
                $r = array_change_key_case($r, CASE_LOWER);
                $t = strtolower((string) $r['table_name']);
                unset($r['table_name']);
                $out[$t][] = $r;
            }

            return $out;
        };
        $colsBy = $groupByTable($allCols);
        $idxBy  = $groupByTable($allIdx);
        $fksBy  = $groupByTable($allFks);

        $normType = static function (string $t): string {
            $t = strtolower(trim($t));
            $t = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\s*\(\d+\)/', '$1', $t);

            return preg_replace('/\s+/', ' ', $t);
        };
        $normDefault = static function ($v): ?string {
            if ($v === null) {
                return null;
            }
            $s = trim((string) $v);
            if (strcasecmp($s, 'null') === 0) {
                return null;
            }
            if (strlen($s) >= 2 && $s[0] === "'" && substr($s, -1) === "'") {
                $s = substr($s, 1, -1);
            }
            if (preg_match('/^(current_timestamp|now)\s*\(\s*\d*\s*\)$/i', $s)) {
                return 'current_timestamp';
            }
            if (strcasecmp($s, 'current_timestamp') === 0) {
                return 'current_timestamp';
            }

            return $s;
        };
        $normExtra = static function (string $e): string {
            $e = strtolower(trim($e));
            $e = str_replace('current_timestamp()', 'current_timestamp', $e);
            $e = str_replace('default_generated', '', $e);

            return trim(preg_replace('/\s+/', ' ', $e));
        };

        $tableFingerprints = [];
        foreach ($tables as $key) {
            $cols = [];
            foreach ($colsBy[$key] ?? [] as $c) {
                $cols[] = [
                    'name'     => strtolower((string) $c['column_name']),
                    'type'     => $normType((string) $c['column_type']),
                    'nullable' => strtoupper((string) $c['is_nullable']),
                    'default'  => $normDefault($c['column_default'] ?? null),
                    'extra'    => $normExtra((string) ($c['extra'] ?? '')),
                ];
            }

            $idx = [];
            foreach ($idxBy[$key] ?? [] as $i) {
                $idx[] = [
                    'name'   => strtolower((string) $i['index_name']),
                    'unique' => ((string) $i['non_unique']) === '0' ? 1 : 0,
                    'seq'    => (int) $i['seq_in_index'],
                    'col'    => strtolower((string) $i['column_name']),
                ];
            }
            usort($idx, fn ($a, $b) => [$a['name'], $a['seq']] <=> [$b['name'], $b['seq']]);

            $fks = [];
            foreach ($fksBy[$key] ?? [] as $f) {
                $fks[] = [
                    'col'       => strtolower((string) $f['column_name']),
                    'ref_table' => strtolower((string) $f['referenced_table_name']),
                    'ref_col'   => strtolower((string) $f['referenced_column_name']),
                    'on_update' => strtolower((string) ($f['update_rule'] ?? '')),
                    'on_delete' => strtolower((string) ($f['delete_rule'] ?? '')),
                ];
            }
            usort($fks, fn ($a, $b) => json_encode($a) <=> json_encode($b));

            $tableFingerprints[$key] = hash('sha256', json_encode([
                'columns' => $cols, 'indexes' => $idx, 'fks' => $fks,
            ]));
        }
        ksort($tableFingerprints);

        return hash('sha256', json_encode($tableFingerprints));
    }

    /* =====================================================================
     * Database — Laravel migrations
     * ===================================================================== */

    public static function migrationsTableExists(): bool
    {
        try {
            DB::table('migrations')->limit(1)->exists();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Applied migration names (from the `migrations` table). */
    public static function appliedMigrations(): array
    {
        try {
            return DB::table('migrations')->orderBy('migration')
                ->get(['migration', 'batch'])
                ->map(fn ($r) => ['filename' => $r->migration, 'batch' => (int) $r->batch])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Migration files on disk (basename, no .php). */
    public static function migrationFiles(): array
    {
        $out = [];
        foreach (glob(database_path('migrations').'/*.php') ?: [] as $f) {
            $out[] = basename($f, '.php');
        }
        sort($out);

        return $out;
    }

    /** Files on disk not yet in the migrations table. */
    public static function pendingMigrations(): array
    {
        $applied = array_column(static::appliedMigrations(), 'filename');

        return array_values(array_map(
            fn ($n) => ['filename' => $n],
            array_diff(static::migrationFiles(), $applied)
        ));
    }

    /* =====================================================================
     * Database — backup + apply
     * ===================================================================== */

    public static function backupDir(): string
    {
        $dir = storage_path('app/sync-backups');
        if (! is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        return $dir;
    }

    /**
     * Gzipped full-DB dump. mysqldump first, pure-PHP fallback. Keeps 10.
     *
     * @return array{ok:bool, path:?string, size:?int, method:?string, error:?string}
     */
    public static function backupDatabase(string $label = 'pre-migrate'): array
    {
        $dir = static::backupDir();
        if (! is_dir($dir) || ! is_writable($dir)) {
            return ['ok' => false, 'path' => null, 'size' => null, 'method' => null,
                'error' => "Backup folder is not writable ($dir)."];
        }

        $db   = config('database.connections.'.config('database.default'));
        $name = $db['database'];
        $file = $dir.'/'.$label.'-'.gmdate('Ymd-His').'-'.substr(bin2hex(random_bytes(3)), 0, 6).'.sql.gz';

        $method = null;
        $error  = null;

        if (function_exists('shell_exec')) {
            $dump = null;
            foreach (['mysqldump', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'] as $cand) {
                $v = @shell_exec(escapeshellarg($cand).' --version 2>&1');
                if ($v && stripos($v, 'mysqldump') !== false) {
                    $dump = $cand;
                    break;
                }
            }
            if ($dump) {
                $cnf = tempnam(sys_get_temp_dir(), 'qbmp');
                @chmod($cnf, 0600);
                @file_put_contents($cnf, "[client]\n"
                    ."host=".($db['host'] ?? '127.0.0.1')."\n"
                    ."port=".($db['port'] ?? 3306)."\n"
                    ."user=".($db['username'] ?? '')."\n"
                    ."password=\"".addslashes((string) ($db['password'] ?? ''))."\"\n");
                $cmd = escapeshellarg($dump)
                    .' --defaults-extra-file='.escapeshellarg($cnf)
                    .' --single-transaction --quick --routines --triggers --no-tablespaces --skip-lock-tables '
                    .escapeshellarg($name)
                    .' 2>'.escapeshellarg($cnf.'.err')
                    .' | gzip > '.escapeshellarg($file);
                @shell_exec($cmd);
                $err = @file_get_contents($cnf.'.err');
                @unlink($cnf);
                @unlink($cnf.'.err');

                if (is_file($file) && filesize($file) > 100 && (! $err || stripos($err, 'error') === false)) {
                    $method = 'mysqldump';
                } else {
                    @unlink($file);
                    $error = 'mysqldump failed'.($err ? ': '.trim($err) : '');
                }
            }
        }

        if ($method === null) {
            try {
                $pdo = DB::connection()->getPdo();
                $gz = @gzopen($file, 'wb6');
                if (! $gz) {
                    throw new \RuntimeException('cannot open '.$file);
                }
                gzwrite($gz, "-- Quotaire PHP dump of $name at ".gmdate('c')."\nSET FOREIGN_KEY_CHECKS=0;\n\n");
                $tables = array_map(
                    fn ($r) => $r['table_name'] ?? $r['TABLE_NAME'],
                    static::rows("SELECT table_name FROM information_schema.tables
                                  WHERE table_schema = DATABASE() AND table_type='BASE TABLE' ORDER BY table_name")
                );
                foreach ($tables as $t) {
                    $create = (array) $pdo->query("SHOW CREATE TABLE `$t`")->fetch(\PDO::FETCH_ASSOC);
                    $ddl = $create['Create Table'] ?? ($create['Create View'] ?? '');
                    gzwrite($gz, "DROP TABLE IF EXISTS `$t`;\n$ddl;\n\n");
                    $stmt = $pdo->query("SELECT * FROM `$t`");
                    $buf = [];
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
                        $buf[] = '('.implode(',', $vals).')';
                        if (count($buf) >= 200) {
                            gzwrite($gz, "INSERT INTO `$t` VALUES ".implode(",\n", $buf).";\n");
                            $buf = [];
                        }
                    }
                    if ($buf) {
                        gzwrite($gz, "INSERT INTO `$t` VALUES ".implode(",\n", $buf).";\n");
                    }
                    gzwrite($gz, "\n");
                }
                gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
                gzclose($gz);
                $method = 'php';
            } catch (\Throwable $e) {
                @unlink($file);

                return ['ok' => false, 'path' => null, 'size' => null, 'method' => null,
                    'error' => 'PHP dump failed: '.$e->getMessage().($error ? " (mysqldump also failed: $error)" : '')];
            }
        }

        if (! is_file($file) || filesize($file) < 100) {
            return ['ok' => false, 'path' => null, 'size' => null, 'method' => $method,
                'error' => 'Backup file was not written.'];
        }

        $existing = glob($dir.'/*.sql.gz') ?: [];
        usort($existing, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($existing, 10) as $old) {
            @unlink($old);
        }

        return ['ok' => true, 'path' => $file, 'size' => filesize($file), 'method' => $method, 'error' => null];
    }

    /**
     * Run `php artisan migrate --force`. Callers back up FIRST.
     *
     * @return array{ok:bool, output:string}
     */
    public static function runMigrate(): array
    {
        try {
            $code = Artisan::call('migrate', ['--force' => true]);

            return ['ok' => $code === 0, 'output' => trim(Artisan::output())];
        } catch (\Throwable $e) {
            return ['ok' => false, 'output' => $e->getMessage()];
        }
    }

    /* =====================================================================
     * Database — watched config-table row fingerprints
     * ===================================================================== */

    private static function configTableColumns(string $table): array
    {
        $cols = array_map(
            fn ($r) => strtolower((string) $r['c']),
            static::rows(
                "SELECT LOWER(column_name) AS c FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position",
                [$table]
            )
        );

        return array_values(array_diff($cols, config('sync.volatile_columns', [])));
    }

    public static function configTableFingerprint(string $table): array
    {
        $use = static::configTableColumns($table);
        if (! $use) {
            return ['hash' => null, 'rows' => 0, 'error' => 'no comparable columns'];
        }
        $rows = static::configTableRows($table, $use);

        return ['hash' => hash('sha256', json_encode($rows)), 'rows' => count($rows)];
    }

    public static function configTableRows(string $table, ?array $use = null): array
    {
        $use ??= static::configTableColumns($table);
        if (! $use) {
            return [];
        }
        $orderBy = in_array('id', $use, true)
            ? '`id`'
            : '`'.implode('`,`', array_slice($use, 0, 3)).'`';
        $select = '`'.implode('`,`', $use).'`';

        return static::rows("SELECT $select FROM `$table` ORDER BY $orderBy");
    }

    /* =====================================================================
     * Files — manifest
     * ===================================================================== */

    private const WALK_IGNORE = [
        '.git', 'vendor', 'node_modules', 'storage', 'public/build', 'public/hot',
        'bootstrap/cache', '.idea', '.vscode', '.fleet', 'tests/Browser/screenshots',
    ];

    private const TEXT_EXT = [
        'php', 'phtml', 'blade', 'inc', 'js', 'mjs', 'cjs', 'ts', 'jsx', 'tsx',
        'json', 'map', 'css', 'scss', 'sass', 'less', 'html', 'htm', 'xml', 'svg',
        'vue', 'twig', 'sql', 'md', 'markdown', 'txt', 'csv', 'yml', 'yaml',
        'ini', 'conf', 'env', 'dist', 'stub', 'sh', 'bash', 'lock',
    ];

    public static function gitignoreMatcher(string $root): callable
    {
        $rules = [];
        $file = $root.'/.gitignore';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || $line[0] === '!') {
                    continue;
                }
                $dirOnly = str_ends_with($line, '/');
                $pat = trim($line, '/');
                if ($pat === '') {
                    continue;
                }
                $rules[] = ['pat' => $pat, 'dir' => $dirOnly, 'anchored' => str_contains($pat, '/')];
            }
        }

        return static function (string $rel) use ($rules): bool {
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            $segs = explode('/', $rel);
            foreach ($rules as $r) {
                $p = $r['pat'];
                if ($r['anchored']) {
                    if ($rel === $p || str_starts_with($rel, $p.'/')) {
                        return true;
                    }
                } elseif (in_array($p, $segs, true)) {
                    return true;
                }
                if ($r['dir'] && ($rel === $p || str_starts_with($rel, $p.'/'))) {
                    return true;
                }
            }

            return false;
        };
    }

    public static function pathKeeper(string $root): callable
    {
        $ignored = static::gitignoreMatcher($root);

        return static function (string $rel) use ($ignored): bool {
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            foreach (self::WALK_IGNORE as $ig) {
                if ($rel === $ig || str_starts_with($rel, $ig.'/')) {
                    return false;
                }
            }
            if (preg_match('/\.(zip|mp4|wfp|log)$/i', $rel)) {
                return false;
            }

            return ! $ignored($rel);
        };
    }

    public static function trackedFiles(string $root): array
    {
        $keep = static::pathKeeper($root);

        if (static::hasGitBinary()) {
            $q = escapeshellarg($root);
            $out = (string) @shell_exec("git -C $q ls-files 2>&1");
            $paths = array_filter(array_map('trim', explode("\n", $out)), 'strlen');
            if ($paths) {
                return ['method' => 'git-ls-files', 'paths' => array_values(array_filter($paths, $keep))];
            }
        }

        $paths = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                function ($cur) use ($root, $keep) {
                    $rel = str_replace('\\', '/', substr($cur->getPathname(), strlen($root) + 1));
                    if ($cur->isDir()) {
                        foreach (self::WALK_IGNORE as $ig) {
                            if ($rel === $ig || str_starts_with($rel, $ig.'/')) {
                                return false;
                            }
                        }

                        return true;
                    }

                    return $keep($rel);
                }
            )
        );
        foreach ($it as $f) {
            if ($f->isFile()) {
                $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
                if ($keep($rel)) {
                    $paths[] = $rel;
                }
            }
        }
        sort($paths);

        return ['method' => 'walk', 'paths' => $paths];
    }

    public static function fileHash(string $full): string
    {
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $base = strtolower(basename($full));
        $isText = in_array($ext, self::TEXT_EXT, true)
            || in_array($base, ['.htaccess', '.gitignore', '.gitattributes', '.editorconfig', '.env.example'], true)
            || str_ends_with($base, '.blade.php');

        if (! $isText) {
            return hash_file('sha256', $full);
        }
        $data = @file_get_contents($full);
        if ($data === false) {
            return hash_file('sha256', $full);
        }
        if (strncmp($data, "\xEF\xBB\xBF", 3) === 0) {
            $data = substr($data, 3);
        }
        $data = preg_replace("/\r\n?/", "\n", $data);

        return 't:'.hash('sha256', $data);
    }

    public static function fileManifest(string $root): array
    {
        $t = static::trackedFiles($root);
        $manifest = [];
        foreach ($t['paths'] as $rel) {
            $full = $root.'/'.$rel;
            if (! is_file($full)) {
                continue;
            }
            $manifest[$rel] = ['sha256' => static::fileHash($full), 'size' => filesize($full)];
        }
        ksort($manifest);

        return ['method' => $t['method'], 'files' => $manifest];
    }

    /* =====================================================================
     * The fingerprint
     * ===================================================================== */

    public static function fingerprint(array $opts = []): array
    {
        $root = static::projectRoot();
        $git  = static::gitState($root, ! empty($opts['fetch']));

        $tableFingerprints = [];
        $schema = static::schemaFingerprint($tableFingerprints);

        $applied = static::appliedMigrations();
        $pending = static::pendingMigrations();

        $config = [];
        foreach (static::watchedTables() as $t) {
            $config[$t] = static::configTableFingerprint($t);
        }

        $manifest = static::fileManifest($root);
        $manifestHash = hash('sha256', json_encode(array_map(fn ($m) => $m['sha256'], $manifest['files'])));

        $fp = [
            'env'          => app()->environment(),
            'host'         => request()?->getHost() ?? gethostname() ?: 'cli',
            'app_version'  => config('app.version'),
            'generated_at' => gmdate('c'),
            'git'          => $git,
            'db' => [
                'ledger_installed'    => static::migrationsTableExists(),
                'applied_migrations'  => $applied,
                'pending_migrations'  => $pending,
                'schema_fingerprint'  => $schema,
                'table_fingerprints'  => $tableFingerprints,
                'config_fingerprints' => $config,
            ],
            'files' => [
                'method'        => $manifest['method'],
                'count'         => count($manifest['files']),
                'manifest_hash' => $manifestHash,
            ],
        ];

        if (! empty($opts['include_manifest'])) {
            $fp['files']['manifest'] = $manifest['files'];
        }

        return $fp;
    }

    /**
     * fingerprint() from a 5-minute cache. Repeated checks and the cron read
     * the cache; ?fresh=1 or the manifest variant bypass it.
     */
    public static function fingerprintCached(array $opts = [], int $ttl = 300): array
    {
        if (! empty($opts['include_manifest']) || ! empty($opts['fresh'])) {
            $fp = static::fingerprint($opts);
            $fp['_cache'] = empty($opts['fresh']) ? 'miss' : 'bypass';
            if (empty($opts['include_manifest'])) {
                Cache::put('sync.fingerprint', ['at' => time(), 'fp' => $fp], $ttl);
            }

            return $fp;
        }

        $c = Cache::get('sync.fingerprint');
        if (is_array($c) && isset($c['fp']['db']['schema_fingerprint'])) {
            $fp = $c['fp'];
            $fp['_cache'] = 'hit';
            $fp['_cache_age'] = time() - (int) ($c['at'] ?? time());

            return $fp;
        }

        $fp = static::fingerprint($opts);
        $fp['_cache'] = 'miss';
        Cache::put('sync.fingerprint', ['at' => time(), 'fp' => $fp], $ttl);

        return $fp;
    }

    /* =====================================================================
     * Diff
     * ===================================================================== */

    public static function diff(array $local, array $live, ?string $trueOriginHead = null): array
    {
        $issues = [];
        $add = function (string $sev, string $area, string $msg, ?string $action = null, $detail = null) use (&$issues) {
            $issues[] = compact('sev', 'area', 'msg', 'action') + ['detail' => $detail];
        };

        $lg = $local['git'];
        $rg = $live['git'];

        if (! empty($lg['dirty'])) {
            $add('critical', 'git',
                $lg['dirty_count'].' uncommitted change(s) on this machine — they can never reach live until committed.',
                'Commit them, then push.', $lg['dirty_files']);
        }

        $origin = $trueOriginHead ?: ($lg['origin_head'] ?? null);

        if ($origin && ! empty($lg['head']) && $lg['head'] !== $origin) {
            $n = $lg['ahead_origin'];
            $add('critical', 'git',
                'This machine has '.($n !== null ? $n.' ' : '').'commit(s) not pushed to the remote.',
                'git push  (or use the "Push now" button)');
        }

        if ($origin && ! empty($rg['head']) && $rg['head'] !== $origin) {
            $add('critical', 'git',
                'Live is behind the remote — the server has not pulled the latest commit(s).',
                'On the server: git pull, then composer install / npm run build / php artisan migrate --force.',
                ['live_head' => $rg['head_short'] ?? substr((string) $rg['head'], 0, 10),
                    'remote_head' => substr((string) $origin, 0, 10)]);
        } elseif (! $origin && ! empty($lg['head']) && ! empty($rg['head']) && $lg['head'] !== $rg['head']) {
            $add('warn', 'git',
                'Live and this machine are on different commits (could not verify the remote).',
                'Check that your last push and the live pull both completed.',
                ['live' => $rg['head_short'], 'local' => $lg['head_short']]);
        }

        $liveDetached = in_array($rg['branch'] ?? '', ['HEAD', '', null], true);
        if ($liveDetached) {
            $add('info', 'git', 'Live is a detached checkout — it reports no branch name (normal for many git deploys).');
        } elseif (! empty($lg['branch']) && ! empty($rg['branch']) && $lg['branch'] !== $rg['branch']) {
            $add('warn', 'git', "Different branches — this machine: {$lg['branch']}, live: {$rg['branch']}.");
        }

        /* ---- migrations ---- */
        if (empty($live['db']['ledger_installed'])) {
            $add('critical', 'migrations',
                'The migrations table does not exist on live.',
                'On live, run:  php artisan migrate --force');
        }

        $liveApplied = array_column($live['db']['applied_migrations'] ?? [], 'filename');
        foreach (($local['db']['applied_migrations'] ?? []) as $m) {
            if (! in_array($m['filename'], $liveApplied, true)) {
                $add('critical', 'migrations',
                    "Live has not run migration: {$m['filename']}",
                    'Use "Back up & migrate live", or run php artisan migrate --force on the server.');
            }
        }
        foreach (($live['db']['pending_migrations'] ?? []) as $p) {
            $add('critical', 'migrations',
                "Live has a migration file it has not run: {$p['filename']}",
                'Run migrations on live.');
        }
        // A migration applied on live but whose file isn't here (local behind).
        $localApplied = array_column($local['db']['applied_migrations'] ?? [], 'filename');
        foreach (($live['db']['applied_migrations'] ?? []) as $m) {
            if (! in_array($m['filename'], $localApplied, true)) {
                $add('warn', 'migrations',
                    "Live has run a migration this checkout doesn't have: {$m['filename']}",
                    'Pull the latest code here.');
            }
        }

        /* ---- schema ---- */
        if (($local['db']['schema_fingerprint'] ?? null) !== ($live['db']['schema_fingerprint'] ?? null)) {
            $lt = $local['db']['table_fingerprints'] ?? [];
            $rt = $live['db']['table_fingerprints'] ?? [];
            foreach ($lt as $t => $h) {
                if (! isset($rt[$t])) {
                    $add('critical', 'schema', "Table `$t` exists here but NOT on live.", 'A migration is missing on live.');
                } elseif ($rt[$t] !== $h) {
                    $add('warn', 'schema', "Table `$t` structure differs between the two environments.",
                        'If no migration explains it, someone altered the table by hand — review.');
                }
            }
            foreach ($rt as $t => $h) {
                if (! isset($lt[$t])) {
                    $add('warn', 'schema', "Table `$t` exists on live but not here.");
                }
            }
        }

        /* ---- config tables (report-only) ---- */
        foreach (($local['db']['config_fingerprints'] ?? []) as $t => $meta) {
            $lh = $meta['hash'] ?? null;
            $rh = $live['db']['config_fingerprints'][$t]['hash'] ?? null;
            if ($lh !== $rh) {
                $add('info', 'config',
                    "Config table `$t` has different rows between the two environments.",
                    'Report-only (live has real data, dev has seed data). Open the row-level diff; apply by hand if intended.',
                    ['table' => $t,
                        'rows_local' => $meta['rows'] ?? null,
                        'rows_live' => $live['db']['config_fingerprints'][$t]['rows'] ?? null]);
            }
        }

        /* ---- files ---- */
        if (($local['files']['manifest_hash'] ?? null) !== ($live['files']['manifest_hash'] ?? null)) {
            $methodsDiffer = ($local['files']['method'] ?? '') !== ($live['files']['method'] ?? '');
            $sameCommit    = ! empty($lg['head']) && ! empty($rg['head']) && $lg['head'] === $rg['head'];
            $liveClean     = empty($rg['dirty']);

            if ($sameCommit && $liveClean && $methodsDiffer) {
                $add('info', 'files',
                    'File list hashes differ only because the two servers enumerate files differently ('
                    .($local['files']['method'] ?? '?').' vs '.($live['files']['method'] ?? '?')
                    .'). Both are on commit '.substr((string) $lg['head'], 0, 10)
                    .' with a clean working tree — the deployed files match.',
                    'Optional: open "Compare files" to confirm.');
            } else {
                $why = ! $sameCommit
                    ? ' The two sides are on different commits.'
                    : (! $liveClean ? ' Live has uncommitted local edits.' : '');
                $add('critical', 'files',
                    'File contents differ between the two environments — a push or deploy is incomplete.'.$why,
                    'Open "Compare files"; then push, pull on live, or revert the hand-edit on live.');
            }
        }

        $counts = ['critical' => 0, 'warn' => 0, 'info' => 0];
        foreach ($issues as $i) {
            $counts[$i['sev']] = ($counts[$i['sev']] ?? 0) + 1;
        }

        return [
            'ok'     => $counts['critical'] === 0 && $counts['warn'] === 0,
            'issues' => $issues,
            'counts' => $counts,
        ];
    }

    public static function manifestDiff(array $localManifest, array $liveManifest): array
    {
        $onlyLocal = $onlyLive = $changed = [];
        foreach ($localManifest as $path => $m) {
            if (! isset($liveManifest[$path])) {
                $onlyLocal[] = $path;
            } elseif ($liveManifest[$path]['sha256'] !== $m['sha256']) {
                $changed[] = ['path' => $path, 'local_size' => $m['size'] ?? null, 'live_size' => $liveManifest[$path]['size'] ?? null];
            }
        }
        foreach ($liveManifest as $path => $m) {
            if (! isset($localManifest[$path])) {
                $onlyLive[] = $path;
            }
        }
        sort($onlyLocal);
        sort($onlyLive);

        return ['only_local' => $onlyLocal, 'only_live' => $onlyLive, 'changed' => $changed];
    }

    /* =====================================================================
     * HTTP helpers (comparator -> live)
     * ===================================================================== */

    public static function httpGet(string $url, string $secret, int $timeout = 45): array
    {
        try {
            $r = Http::withHeaders(['X-Sync-Token' => $secret, 'Accept' => 'application/json'])
                ->timeout($timeout)->get($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Connection failed: '.$e->getMessage()];
        }
        $data = $r->json();
        if (! $r->ok() || ! is_array($data)) {
            return ['ok' => false, 'error' => 'HTTP '.$r->status().' from live endpoint', 'raw' => substr($r->body(), 0, 400)];
        }

        return ['ok' => true, 'data' => $data];
    }

    public static function httpPost(string $url, string $secret, array $payload, int $timeout = 180): array
    {
        try {
            $r = Http::withHeaders(['X-Sync-Token' => $secret, 'Accept' => 'application/json'])
                ->timeout($timeout)->post($url, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Connection failed: '.$e->getMessage()];
        }
        $data = $r->json();

        return ['ok' => $r->ok(), 'http' => $r->status(), 'data' => is_array($data) ? $data : null,
            'raw' => is_array($data) ? null : substr($r->body(), 0, 800)];
    }

    /* =====================================================================
     * Status cache (drives the banner / health pill)
     * ===================================================================== */

    public static function writeStatus(bool $ok, string $mode, ?bool $reachable, array $topMsgs): void
    {
        Cache::forever('sync.status', [
            'checked_at' => gmdate('c'),
            'ok'         => $ok,
            'mode'       => $mode,        // 'compare' | 'selfcheck'
            'reachable'  => $reachable,
            'top'        => array_slice($topMsgs, 0, 5),
        ]);
    }

    public static function status(): ?array
    {
        $s = Cache::get('sync.status');

        return is_array($s) ? $s : null;
    }

    /** Health-pill descriptor for the Super Admin layout. */
    public static function pill(): array
    {
        $s = static::status();
        $age = ($s && ! empty($s['checked_at'])) ? time() - strtotime($s['checked_at']) : null;
        $fresh = $age !== null && $age < 172800;
        $mode = $s['mode'] ?? null;
        $ok = $s['ok'] ?? true;

        if ($s && $ok === false && $fresh) {
            if ($mode === 'compare' && ($s['reachable'] ?? true) === false) {
                return ['level' => 'warn', 'text' => 'Cannot reach the other environment', 'href' => 'superadmin.sync.index'];
            }

            return ['level' => 'bad',
                'text' => $mode === 'selfcheck' ? 'Deploy or migrations pending' : 'Environments out of sync',
                'href' => 'superadmin.sync.index'];
        }
        if ($age !== null && $age >= 172800) {
            return ['level' => 'warn', 'text' => 'Sync check overdue', 'href' => 'superadmin.sync.index'];
        }
        if ($s && $ok && $mode === 'compare') {
            return ['level' => 'ok', 'text' => 'In sync with live', 'href' => null];
        }
        if ($s && $ok && $mode === 'selfcheck') {
            return ['level' => 'ok', 'text' => 'Server checks OK', 'href' => null];
        }

        return ['level' => 'ok', 'text' => 'All systems operational', 'href' => null];
    }
}
