<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmittedPolicy extends Model
{
    use SoftDeletes;

    protected $table = "emitted_policies";

    protected $fillable = [
        'policy_id',
        'receipt_id',
        'policy_number',
        'emission_id',
        'insurer',
        'date_init',
        'date_expires',
        'emission_date',
        'payment_frequency',
        'status_id'
    ];
}
