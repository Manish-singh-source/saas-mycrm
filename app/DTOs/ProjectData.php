<?php

namespace App\DTOs;

final readonly class ProjectData
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?string $projectNumber = null,
        public ?string $description = null,
        public ?int $clientPartyId = null,
        public ?int $projectManagerId = null,
        public ?int $categoryId = null,
        public ?int $typeId = null,
        public ?int $statusId = null,
        public ?int $priorityId = null,
        public ?string $startDate = null,
        public ?string $dueDate = null,
        public string $budgetAmount = '0',
        public ?string $billingType = null,
    ) {}
}