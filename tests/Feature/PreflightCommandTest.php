<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PreflightCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(storage_path('app/private'));
        File::ensureDirectoryExists(public_path('build'));
        File::put(
            public_path('build/manifest.json'),
            '{"resources/css/app.css":{"file":"assets/app.css","src":"resources/css/app.css","isEntry":true}}'
        );
    }

    public function test_preflight_passes_on_a_migrated_local_environment(): void
    {
        $this->artisan('hrms-lite:preflight')
            ->expectsOutputToContain('Preflight passed.')
            ->assertSuccessful();
    }

    public function test_preflight_json_does_not_emit_secrets(): void
    {
        $exit = Artisan::call('hrms-lite:preflight', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString((string) config('app.key'), $output);
        $this->assertStringNotContainsString('DB_PASSWORD', $output);
        $this->assertStringContainsString('"ok": true', $output);

        $payload = json_decode($output, true);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['ok']);
        $this->assertNotEmpty($payload['checks']);
    }

    public function test_preflight_fails_when_vite_manifest_is_missing(): void
    {
        $manifest = public_path('build/manifest.json');
        $original = File::exists($manifest) ? File::get($manifest) : null;
        File::delete($manifest);

        try {
            $this->artisan('hrms-lite:preflight')
                ->expectsOutputToContain('vite_manifest')
                ->assertFailed();
        } finally {
            if ($original !== null) {
                File::ensureDirectoryExists(dirname($manifest));
                File::put($manifest, $original);
            }
        }
    }
}
