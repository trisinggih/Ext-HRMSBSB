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
        if (!Schema::hasTable('employee_signatures')) {
            Schema::create('employee_signatures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->string('signature_path');
                $table->longText('strokes_data')->nullable();
                $table->string('device_info')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->foreign('employee_id')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_signatures');
    }
};
