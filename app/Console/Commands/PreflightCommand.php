<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PreflightCommand extends Command
{
    protected $signature = 'hrms-lite:preflight {--json : Emit machine-readable JSON}';

    protected $description = 'Read-only production readiness checks (never prints secrets or mutates data)';

    public function handle(): int
    {
        $checks = [];

        $checks[] = $this->check('php_version', version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP '.PHP_VERSION, 'PHP 8.3+ required');
        foreach (['bcmath', 'pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'] as $ext) {
            $checks[] = $this->check('ext_'.$ext, extension_loaded($ext), $ext.' loaded', $ext.' extension missing');
        }

        $pdoOk = false;
        $pdoDetail = 'unreachable';
        try {
            DB::connection()->getPdo();
            $pdoOk = true;
            $pdoDetail = config('database.default').' connected';
        } catch (Throwable $e) {
            $pdoDetail = 'connection failed';
        }
        $checks[] = $this->check('database', $pdoOk, $pdoDetail, 'Database connectivity failed');

        $appKey = (string) config('app.key');
        $checks[] = $this->check('app_key', $appKey !== '', 'APP_KEY present', 'APP_KEY missing');

        $env = app()->environment();
        $checks[] = $this->check(
            'environment',
            in_array($env, ['production', 'staging', 'local', 'testing'], true),
            'APP_ENV='.$env,
            'Unexpected APP_ENV'
        );

        if ($env === 'production') {
            $checks[] = $this->check('debug_off', ! config('app.debug'), 'APP_DEBUG is false', 'APP_DEBUG must be false in production');
            $checks[] = $this->check('https_url', str_starts_with((string) config('app.url'), 'https://'), 'APP_URL uses https', 'APP_URL should use https in production');
            $checks[] = $this->check(
                'secure_cookies',
                (bool) config('session.secure'),
                'SESSION_SECURE_COOKIE enabled',
                'Enable SESSION_SECURE_COOKIE with HTTPS'
            );
        } else {
            $checks[] = $this->check(
                'session_driver',
                filled(config('session.driver')),
                'session driver='.config('session.driver'),
                'Session driver not configured'
            );
        }

        foreach (['storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $path = base_path($dir);
            $checks[] = $this->check(
                'writable_'.str_replace(['/', '\\'], '_', $dir),
                is_dir($path) && is_writable($path),
                $dir.' writable',
                $dir.' not writable'
            );
        }

        $privateDisk = storage_path('app/private');
        $checks[] = $this->check(
            'private_storage',
            is_dir($privateDisk) || @mkdir($privateDisk, 0755, true),
            'private storage path available',
            'private storage path missing'
        );

        $checks[] = $this->check(
            'vite_manifest',
            file_exists(public_path('build/manifest.json')),
            'Vite manifest present',
            'Run npm run build and deploy public/build'
        );

        $publicEnv = public_path('.env');
        $checks[] = $this->check('env_not_public', ! file_exists($publicEnv), '.env not in public/', '.env must never live under public/');

        $migrationOk = false;
        $migrationDetail = 'unable to inspect';
        try {
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                $migrationDetail = 'migration repository missing';
            } else {
                $files = $migrator->getMigrationFiles([database_path('migrations')]);
                $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());
                $migrationOk = $pending === [] && Schema::hasTable('users');
                $migrationDetail = $pending === []
                    ? 'migrations up to date'
                    : count($pending).' pending';
            }
        } catch (Throwable) {
            $migrationDetail = 'migration status unavailable';
        }
        $checks[] = $this->check('migrations', $migrationOk, $migrationDetail, 'Pending or unreadable migrations');

        $failed = collect($checks)->where('ok', false)->values();

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => $failed->isEmpty(),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));
        } else {
            foreach ($checks as $check) {
                $this->{$check['ok'] ? 'info' : 'error'}(sprintf('[%s] %s — %s', $check['ok'] ? 'OK' : 'FAIL', $check['id'], $check['detail']));
            }
            $this->newLine();
            $this->line($failed->isEmpty() ? 'Preflight passed.' : 'Preflight found '.$failed->count().' issue(s).');
        }

        return $failed->isEmpty() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{id: string, ok: bool, detail: string, message: string}
     */
    private function check(string $id, bool $ok, string $okDetail, string $failMessage): array
    {
        return [
            'id' => $id,
            'ok' => $ok,
            'detail' => $ok ? $okDetail : $failMessage,
            'message' => $ok ? $okDetail : $failMessage,
        ];
    }
}
