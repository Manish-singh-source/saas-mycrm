<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';
    case PendingPayment = 'pending_payment';
    case GracePeriod = 'grace_period';
}