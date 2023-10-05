<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policy extends Model
{
    use SoftDeletes;

    protected $table = "policies";

    protected $fillable = [
        'client_id',
        'model',
        'brand_id',
        'brand',
        'unit_type',
        'type',
        'amis',
        'vehicle_description',
        'pack_id',
        'pack_name',
        'payment_frequency',
        'quotation_code',
        'brand_logo',
        'vehicle_code',
        'serial_no',
        'plate_no',
        'motor_no',
        'insurer',
        'insurer_logo',
        'paid_amount',
        'total_amount',
        'issuance_date',
        'issuance_code',
        'receipt_code',
        'policy_code',
        'policy_number',
        'init_date',
        'date_expire',
        'status_id',
        'lead_id'
    ];
}
