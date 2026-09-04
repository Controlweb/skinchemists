<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_public_and_scoped_to_the_panel(): void
    {
        // The browser fetches the manifest without session credentials, so it
        // must not sit behind the panel's auth middleware.
        $manifest = $this->get('/admin/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->json();

        $this->assertSame('/admin', $manifest['scope']);
        $this->assertSame('/admin', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        // Chrome only offers "install" when both a 192 and a 512 icon are declared.
        $this->assertSame(['192x192', '512x512'], array_column($manifest['icons'], 'sizes'));
    }

    public function test_the_icons_the_manifest_advertises_exist(): void
    {
        foreach ($this->get('/admin/manifest.webmanifest')->json('icons') as $icon) {
            $path = public_path(parse_url($icon['src'], PHP_URL_PATH));

            $this->assertFileExists($path, "Missing PWA icon: {$icon['src']}");
            [$width, $height] = getimagesize($path);
            $this->assertSame($icon['sizes'], "{$width}x{$height}");
        }
    }

    public function test_the_service_worker_is_served_from_the_panel_path(): void
    {
        // Scope follows the worker's own URL: served anywhere but /admin/ it
        // could not control the panel.
        $this->get('/admin/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertSee('/admin/hors-ligne')
            ->assertSee("addEventListener('fetch'", false);
    }

    public function test_the_offline_page_stands_alone(): void
    {
        $html = $this->get('/admin/hors-ligne')->assertOk()->getContent();

        // It is cached on install, so it must not pull in any other asset.
        $this->assertStringNotContainsString('<script src', $html);
        $this->assertStringNotContainsString('<link rel="stylesheet"', $html);
    }

    public function test_the_panel_advertises_the_manifest_and_registers_the_worker(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/admin/manifest.webmanifest')
            ->assertSee('serviceWorker.register', false)
            ->assertSee('apple-touch-icon', false);
    }
}
