<?php

namespace App\DTOs;

final readonly class LeaveRequestData
{
    public function __construct(
        public int $tenantId,
        public int $staffId,
        public int $leaveTypeId,
        public string $startDate,
        public string $endDate,
        public string $totalDays,
        public ?string $reason = null,
        public ?int $statusId = null,
    ) {}
}