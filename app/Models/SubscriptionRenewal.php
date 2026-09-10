<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionRenewal extends Model
{
    protected $table = 'subscription_renewals';

    public $timestamps = false;

    protected $guarded = ['id'];
}
