<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjectExpense extends Model
{
    protected $table = 'project_expenses';

    public $timestamps = false;

    protected $guarded = ['id'];
}
