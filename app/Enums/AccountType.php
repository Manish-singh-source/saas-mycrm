<?php

namespace App\Enums;
enum AccountType: string
{
    case Owner = 'owner';
    case Staff = 'staff';
    case Client = 'client';
}
