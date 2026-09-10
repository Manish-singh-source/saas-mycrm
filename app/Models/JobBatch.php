<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class JobBatch extends Model
{
    protected $table = 'job_batches';

    public $timestamps = false;

    protected $guarded = ['id'];
}
