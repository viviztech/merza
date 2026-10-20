<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-key:generate 
                            {name=External Platform : The descriptive name for the API key}
                            {--abilities=orders:read,orders:write : Comma-separated list of abilities (*, catalog:read, orders:read, orders:write)}
                            {--expires= : Expiration date (e.g. 2026-12-31 or +30 days)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new API key with specific permissions (e.g. orders:read, orders:write)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $rawAbilities = (string) $this->option('abilities');
        $abilities = array_values(array_filter(array_map('trim', explode(',', $rawAbilities))));

        if (empty($abilities)) {
            $abilities = [ApiKey::ABILITY_ORDERS_READ, ApiKey::ABILITY_ORDERS_WRITE];
        }

        $expiresAt = null;
        if ($this->option('expires')) {
            try {
                $expiresAt = now()->parse($this->option('expires'));
            } catch (\Throwable $e) {
                $this->error('Invalid date format for --expires.');
                return Command::FAILURE;
            }
        }

        $adminUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::first();

        $secret = ApiKey::generateSecret();

        $apiKey = ApiKey::create([
            'created_by' => $adminUser?->id,
            'name'       => $name,
            'key_prefix' => ApiKey::prefixFor($secret),
            'key_hash'   => ApiKey::hashSecret($secret),
            'abilities'  => $abilities,
            'expires_at' => $expiresAt,
        ]);

        $this->info('API Key created successfully!');
        $this->table(
            ['ID', 'Name', 'Prefix', 'Permissions', 'Expires At'],
            [
                [
                    $apiKey->id,
                    $apiKey->name,
                    $apiKey->key_prefix,
                    implode(', ', $abilities),
                    $expiresAt ? $expiresAt->toDateTimeString() : 'Never',
                ]
            ]
        );

        $this->newLine();
        $this->warn('Secret Key (copy now, it cannot be retrieved again):');
        $this->line($secret);
        $this->newLine();

        return Command::SUCCESS;
    }
}

