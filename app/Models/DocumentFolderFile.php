<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class DocumentFolderFile extends Model
{
    protected $table = 'document_folder_files';

    protected $guarded = ['id'];
}
