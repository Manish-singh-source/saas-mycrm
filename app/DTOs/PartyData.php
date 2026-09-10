<?php

namespace App\DTOs;

final readonly class PartyData
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $partyType,
        public ?string $code = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $taxNumber = null,
        public array $contacts = [],
        public array $addresses = [],
    ) {}
}