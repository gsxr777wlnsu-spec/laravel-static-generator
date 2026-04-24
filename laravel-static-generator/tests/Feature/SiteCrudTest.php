<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiteCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-site@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_store_persists_status_and_locale_fields(): void
    {
        $payload = [
            'name' => 'Landing RU',
            'domain' => 'landing-ru.example',
            'template_set' => 'base',
            'output_path' => 'generated/landing-ru',
            'status' => 'active',
            'locale' => 'ru',
            'default_locale' => 'ru',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/sites', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'active');
        $response->assertJsonPath('locale', 'ru');
        $response->assertJsonPath('output_path', 'generated/landing-ru');

        $this->assertDatabaseHas('sites', [
            'domain' => 'landing-ru.example',
            'status' => 'active',
            'locale' => 'ru',
            'default_locale' => 'ru',
            'output_path' => 'generated/landing-ru',
        ]);
    }

    public function test_update_persists_output_path_locale_and_status(): void
    {
        $site = Site::create([
            'name' => 'Initial Site',
            'domain' => 'initial-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/initial',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/sites/{$site->id}", [
            'output_path' => 'generated/updated',
            'locale' => 'de',
            'default_locale' => 'de',
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJsonPath('output_path', 'generated/updated');
        $response->assertJsonPath('locale', 'de');
        $response->assertJsonPath('default_locale', 'de');
        $response->assertJsonPath('status', 'inactive');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'output_path' => 'generated/updated',
            'locale' => 'de',
            'default_locale' => 'de',
            'status' => 'inactive',
        ]);
    }
}
