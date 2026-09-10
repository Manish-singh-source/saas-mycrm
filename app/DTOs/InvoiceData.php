<?php

namespace App\DTOs;

use App\Enums\InvoiceStatus;

final readonly class InvoiceData
{
    public function __construct(
        public int $tenantId,
        public string $invoiceNumber,
        public string $issueDate,
        public ?string $dueDate = null,
        public string $subtotal = '0',
        public string $taxAmount = '0',
        public string $discountAmount = '0',
        public string $totalAmount = '0',
        public InvoiceStatus $status = InvoiceStatus::Draft,
        public array $items = [],
    ) {}
}