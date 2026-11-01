<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolesAndAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_preserves_existing_admin_credentials(): void
    {
        User::factory()->create([
            'name' => 'Existing Admin',
            'email' => 'admin@merza.com',
            'password' => 'old-password',
        ]);

        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::where('email', 'admin@merza.com')->firstOrFail();

        $this->assertSame('Existing Admin', $admin->name);
        $this->assertTrue(Hash::check('old-password', $admin->password));
        $this->assertTrue($admin->hasRole('Admin'));
    }
}
