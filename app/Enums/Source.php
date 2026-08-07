<?php

namespace App\Enums;

enum Source: string
{
    case Website = 'website';
    case Referral = 'referral';
    case Social = 'social';
    case Email = 'email';
    case Phone = 'phone';
    case Import = 'import';
    case Manual = 'manual';
    case Api = 'api';
}