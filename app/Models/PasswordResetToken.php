<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';

    public $timestamps = false;

    protected $primaryKey = 'email';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
}
