<?php

namespace Tests\Feature;

use App\Filament\Resources\ApiKeyResource\Pages\ListApiKeys;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_secret_generation_and_hashing(): void
    {
        $secret = ApiKey::generateSecret();
        
        $this->assertStringStartsWith('merza_live_', $secret);
        $this->assertEquals(ApiKey::prefixFor($secret), substr($secret, 0, 20));

        $user = User::factory()->create();

        $apiKey = ApiKey::create([
            'created_by' => $user->id,
            'name'       => 'Test API Key',
            'key_prefix' => ApiKey::prefixFor($secret),
            'key_hash'   => ApiKey::hashSecret($secret),
            'abilities'  => ['catalog:read'],
        ]);

        $this->assertDatabaseHas('api_keys', [
            'id'   => $apiKey->id,
            'name' => 'Test API Key',
        ]);

        $this->assertTrue($apiKey->isActive());
        $this->assertTrue($apiKey->allows('catalog:read'));
        $this->assertFalse($apiKey->allows('orders:write'));
    }

    public function test_api_key_authentication_middleware(): void
    {
        $secret = ApiKey::generateSecret();
        
        ApiKey::create([
            'name'       => 'Test Key',
            'key_prefix' => ApiKey::prefixFor($secret),
            'key_hash'   => ApiKey::hashSecret($secret),
            'abilities'  => ['catalog:read'],
        ]);

        // Request with missing key
        $this->getJson('/api/v1/health')
            ->assertStatus(401);

        // Request with valid bearer token to base route
        $this->withToken($secret)
            ->getJson('/api/v1')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        // Request with valid bearer token to health route
        $this->withToken($secret)
            ->getJson('/api/v1/health')
            ->assertStatus(200);

        // Request with valid X-API-Key header
        $this->withHeaders(['X-API-Key' => $secret])
            ->getJson('/api/v1/products')
            ->assertStatus(200);

        // Request with query parameter
        $this->getJson('/api/v1/categories?api_key=' . $secret)
            ->assertStatus(200);
    }

    public function test_integration_settings_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']));

        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Filament\Pages\IntegrationSettingsPage::class)
            ->assertSuccessful()
            ->assertDontSee('Generate API Key')
            ->assertDontSee('External API Access')
            ->assertDontSee('Manage Webhooks');
    }

    public function test_generate_api_key_action_opens_secret_modal_popup(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']));

        $this->actingAs($user);

        $testable = \Livewire\Livewire::test(ListApiKeys::class)
            ->callAction('generateApiKey', [
                'name' => 'Test Key Popup',
                'abilities' => ['orders:read', 'orders:write'],
            ])
            ->assertSet('generatedSecret', fn (string $secret): bool => str_starts_with($secret, 'merza_live_'))
            ->assertSet('mountedActions.0.data.secret_key_display', fn (string $secret): bool => str_starts_with($secret, 'merza_live_'));

        $testable->assertStatus(200);

        $this->assertDatabaseHas('api_keys', [
            'name' => 'Test Key Popup',
        ]);

        $createdKey = ApiKey::where('name', 'Test Key Popup')->first();
        $this->assertTrue($createdKey->allows('orders:read'));
        $this->assertTrue($createdKey->allows('orders:write'));
    }

    public function test_api_key_order_read_and_write_permissions(): void
    {
        $readSecret = ApiKey::generateSecret();
        ApiKey::create([
            'name'       => 'Read Only Key',
            'key_prefix' => ApiKey::prefixFor($readSecret),
            'key_hash'   => ApiKey::hashSecret($readSecret),
            'abilities'  => ['orders:read'],
        ]);

        $writeSecret = ApiKey::generateSecret();
        ApiKey::create([
            'name'       => 'Write Only Key',
            'key_prefix' => ApiKey::prefixFor($writeSecret),
            'key_hash'   => ApiKey::hashSecret($writeSecret),
            'abilities'  => ['orders:write'],
        ]);

        // Key with orders:read can list orders
        $this->withToken($readSecret)
            ->getJson('/api/v1/orders')
            ->assertStatus(200);

        // Key with orders:read CANNOT create orders
        $this->withToken($readSecret)
            ->postJson('/api/v1/orders', [
                'customer_name'    => 'John Doe',
                'customer_phone'   => '9876543210',
                'delivery_address' => '123 Street',
                'items'            => [
                    ['quantity' => 1, 'unit_price' => 100, 'product_name' => 'Custom Product']
                ]
            ])
            ->assertStatus(403);

        // Key with orders:write CAN create orders
        $response = $this->withToken($writeSecret)
            ->postJson('/api/v1/orders', [
                'customer_name'    => 'John Doe',
                'customer_phone'   => '9876543210',
                'customer_email'   => 'john@example.com',
                'delivery_address' => '123 Street, Mumbai',
                'city'             => 'Mumbai',
                'state'            => 'Maharashtra',
                'postcode'         => '400001',
                'payment_method'   => 'cod',
                'items'            => [
                    ['quantity' => 2, 'unit_price' => 250, 'product_name' => 'Test Honey']
                ]
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.customer_name', 'John Doe');

        $orderId = $response->json('data.id');

        // Key with orders:write can update order status
        $this->withToken($writeSecret)
            ->patchJson('/api/v1/orders/' . $orderId, [
                'status' => 'confirmed',
                'notes'  => 'Confirmed via API',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_artisan_api_key_generate_command(): void
    {
        $this->artisan('api-key:generate "Integration Test Key" --abilities=orders:read,orders:write')
            ->assertSuccessful();

        $this->assertDatabaseHas('api_keys', [
            'name' => 'Integration Test Key',
        ]);
    }
}
