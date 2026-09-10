<?php

namespace App\DTOs;

use App\Enums\CompanySize;
use App\Enums\TenantStatus;

final readonly class TenantData
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $legalName = null,
        public ?CompanySize $companySize = null,
        public ?int $countryId = null,
        public ?int $stateId = null,
        public ?int $cityId = null,
        public ?int $currencyId = null,
        public ?int $timezoneId = null,
        public TenantStatus $status = TenantStatus::Pending,
        public array $settings = [],
    ) {}
}