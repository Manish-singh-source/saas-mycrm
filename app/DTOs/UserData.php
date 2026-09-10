<?php

namespace App\DTOs;

use App\Enums\AccountType;
use App\Enums\CommonStatus;

final readonly class UserData
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $email,
        public ?string $password = null,
        public AccountType $accountType = AccountType::Staff,
        public CommonStatus $status = CommonStatus::Active,
        public ?string $phone = null,
        public ?string $employeeCode = null,
        public array $roles = [],
    ) {}
}