<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Processed = 'processed';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}