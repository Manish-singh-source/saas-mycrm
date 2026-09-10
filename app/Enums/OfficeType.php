<?php

namespace App\Enums;
enum OfficeType: string
{
    case HeadOffice = 'head_office';
    case Branch = 'branch';
    case Regional = 'regional';
    case Warehouse = 'warehouse';
    case Factory = 'factory';
    case Store = 'store';
    case Remote = 'remote';
    case Franchise = 'franchise';
}
