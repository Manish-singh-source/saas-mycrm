<?php

namespace App\DTOs;

final readonly class StaffData
{
    public function __construct(
        public int $tenantId,
        public string $firstName,
        public string $displayName,
        public ?string $lastName = null,
        public ?string $employeeCode = null,
        public ?string $personalEmail = null,
        public ?string $workEmail = null,
        public ?string $mobile = null,
        public ?string $dateOfBirth = null,
        public ?string $joiningDate = null,
        public ?int $departmentId = null,
        public ?int $designationId = null,
        public ?int $officeId = null,
        public ?int $primaryTeamId = null,
        public ?string $employmentType = null,
        public string $employmentStatus = 'active',
    ) {}
}