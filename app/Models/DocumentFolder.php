<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DocumentFolder extends Model
{
    use SoftDeletes;

    protected $table = 'document_folders';

    protected $guarded = ['id'];
}
