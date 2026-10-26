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
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('date');
            $table->text('reason')->nullable();
            $table->time('requested_start_time')->nullable();
            $table->time('requested_end_time')->nullable();
            $table->enum('status', ['pending', 'supervisor_approved', 'hr_approved', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
