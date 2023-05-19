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
        Schema::create('emitted_policies', function (Blueprint $table) {
            $table->id();
            $table->integer('policy_id');
            $table->string('receipt_id');
            $table->string('policy_number');
            $table->bigInteger('emission_id');
            $table->string('insurer');
            $table->date('emission_date');
            $table->date('date_init');
            $table->date('date_expires');
            $table->string('payment_frequency');
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
        Schema::dropIfExists('emitted_policies');
    }
};
