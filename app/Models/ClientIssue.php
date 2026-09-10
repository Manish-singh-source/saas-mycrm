<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ClientIssue extends Model
{
    use SoftDeletes;

    protected $table = 'client_issues';

    protected $guarded = ['id'];
}
