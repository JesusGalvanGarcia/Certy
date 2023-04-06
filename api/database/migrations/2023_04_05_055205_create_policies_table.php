<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->string('model');
            $table->integer('brand_id');
            $table->string('brand');
            $table->string('unit_type');
            $table->string('type');
            $table->string('amis');
            $table->string('vehicle_description');
            $table->integer('pack_id')->nullable($value = true);
            $table->string('pack_name')->nullable($value = true);
            $table->string('payment_frequency')->nullable($value = true);
            $table->integer('quotation_code')->nullable($value = true);
            $table->string('brand_logo')->nullable($value = true);
            $table->string('vehicle_code')->nullable($value = true);
            $table->string('insurer')->nullable($value = true);
            $table->string('insurer_logo')->nullable($value = true);
            $table->string('motor_no')->nullable($value = true);
            $table->string('plate_no')->nullable($value = true);
            $table->string('motor_no')->nullable($value = true);
            $table->float('paid_amount', 22, 2, true)->nullable($value = true);
            $table->float('total_amount', 22, 2, true)->nullable($value = true);
            $table->date('issuance_date')->nullable($value = true);
            $table->string('issuance_code')->nullable($value = true);
            $table->string('receipt_code')->nullable($value = true);
            $table->string('policy_code')->nullable($value = true);
            $table->string('policy_number')->nullable($value = true);
            $table->date('init_date')->nullable($value = true);
            $table->date('date_expire')->nullable($value = true);
            $table->integer('status_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable($value = true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
