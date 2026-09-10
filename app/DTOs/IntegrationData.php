<?php

namespace App\DTOs;

final readonly class IntegrationData
{
    public function __construct(
        public int $tenantId,
        public int $providerId,
        public string $name,
        public bool $enabled = true,
        public array $credentials = [],
        public array $settings = [],
        public array $fieldMappings = [],
    ) {}
}