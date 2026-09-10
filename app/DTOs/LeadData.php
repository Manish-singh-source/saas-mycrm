<?php

namespace App\DTOs;

final readonly class LeadData
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?int $partyId = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?int $sourceId = null,
        public ?int $statusId = null,
        public ?int $assignedTo = null,
        public ?string $expectedValue = null,
        public ?string $notes = null,
    ) {}
}