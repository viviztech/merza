<?php

namespace App\Support;

class Seo
{
    public ?string $description = null;

    public ?string $ogImage = null;

    public string $ogType = 'website';

    public string $robots = 'index, follow';

    /** @var array<int, array<string, mixed>> */
    public array $schemas = [];

    public function description(?string $value): static
    {
        if (filled($value)) {
            $this->description = $value;
        }

        return $this;
    }

    public function ogImage(?string $value): static
    {
        if (filled($value)) {
            $this->ogImage = $value;
        }

        return $this;
    }

    public function ogType(string $value): static
    {
        $this->ogType = $value;

        return $this;
    }

    public function noindex(): static
    {
        $this->robots = 'noindex, nofollow';

        return $this;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function schema(array $schema): static
    {
        $this->schemas[] = $schema;

        return $this;
    }
}
