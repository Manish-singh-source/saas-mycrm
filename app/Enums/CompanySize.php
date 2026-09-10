<?php

namespace App\Enums;
enum CompanySize: string
{
    case Self = 'self';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Enterprise = 'enterprise';
}
