<?php

namespace App\DTOs;

use App\Enums\PaymentStatus;

final readonly class PaymentData
{
    public function __construct(
        public int $tenantId,
        public string $amount,
        public string $paymentDate,
        public ?int $invoiceId = null,
        public ?string $method = null,
        public ?string $reference = null,
        public PaymentStatus $status = PaymentStatus::Pending,
        public ?string $notes = null,
    ) {}
}