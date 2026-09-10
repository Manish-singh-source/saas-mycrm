<?php

namespace App\DTOs;

use App\Enums\SubscriptionStatus;

final readonly class SubscriptionData
{
    public function __construct(
        public int $tenantId,
        public int $planId,
        public string $startsAt,
        public ?string $endsAt = null,
        public SubscriptionStatus $status = SubscriptionStatus::Trial,
        public ?string $billingCycle = null,
        public array $addons = [],
    ) {}
}