<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Version extends Model
{
    use SoftDeletes;

    protected $table = "versions";

    protected $fillable = [
        'amis',
        'description',
        'model',
        'brand_id',
        'type'
    ];
}
