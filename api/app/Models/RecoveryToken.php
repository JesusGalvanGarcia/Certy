<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryToken extends Model
{
    use SoftDeletes;

    protected $table = "recovery_tokens";

    protected $fillable = [
        'user_id',
        'token',
        'email',
        'status_id'
    ];
}
