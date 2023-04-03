<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErrorsLog extends Model
{
    use SoftDeletes;

    protected $table = "errors_log";

    protected $fillable = [
        'description',
        'http_code',
        'module',
        'prefix_code',
        'created_at',
        'updated_at'
    ];
}
