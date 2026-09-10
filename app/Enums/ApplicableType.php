<?php

namespace App\Enums;
enum ApplicableType: string
{
    case Country = 'country';
    case State = 'state';
    case City = 'city';
    case Office = 'office';
    case Department = 'department';
    case Team = 'team';
    case Staff = 'staff';
    case Group = 'group';
}
