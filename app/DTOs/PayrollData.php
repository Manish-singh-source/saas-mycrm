<?php

namespace App\DTOs;

final readonly class PayrollData
{
    public function __construct(
        public int $tenantId,
        public int $payrollCycleId,
        public int $staffId,
        public string $employeeCode,
        public string $grossSalary = '0',
        public string $totalEarnings = '0',
        public string $totalDeductions = '0',
        public string $taxableIncome = '0',
        public string $taxAmount = '0',
        public string $netSalary = '0',
        public string $paymentStatus = 'pending',
        public ?string $paymentReference = null,
        public ?string $remarks = null,
        public array $items = [],
    ) {}
}