<?php

namespace App\Enums;

enum Visibility: string
{
    case Private = 'private';
    case Team = 'team';
    case Tenant = 'tenant';
    case Client = 'client';
    case Public = 'public';
}