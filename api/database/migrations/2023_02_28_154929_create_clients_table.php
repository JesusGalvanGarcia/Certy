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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('complete_name', 200);
            $table->string('email', 50);
            $table->timestamp('email_verified_at')->nullable($value = true);
            $table->string('cellphone', 15)->nullable($value = true);
            $table->integer('cp', false, true)->nullable($value = true);
            $table->integer('age', false, true)->nullable($value = true);
            $table->string('genre', 50)->nullable($value = true);
            $table->string('rfc', 13)->nullable($value = true);
            $table->string('suburb', 100)->nullable($value = true);
            $table->string('state', 50)->nullable($value = true);
            $table->string('township', 50)->nullable($value = true);
            $table->string('street_number', 20)->nullable($value = true);
            $table->string('int_street_number', 20)->nullable($value = true);
            $table->string('password', 255)->nullable($value = true);
            $table->integer('status_id', false, true);
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
        Schema::dropIfExists('clients');
    }
};
