<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee Face Template (biometric, aman — feature descriptor terenkripsi)
        Schema::create('employee_faces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('face_template'); // encrypted feature descriptor
            $table->text('face_image')->nullable(); // encrypted reference
            $table->string('model_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('employee_id');
        });

        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->time('clock_in');
            $table->time('clock_out');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('working_hours')->default(8);
            $table->unsignedInteger('late_tolerance_minutes')->default(0);
            $table->unsignedInteger('overtime_threshold_minutes')->default(0);
            $table->text('overtime_rules')->nullable();
            $table->boolean('cross_midnight')->default(false); // shift lintas hari
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['employee_id', 'effective_date', 'end_date']);
        });

        // Shift Swap (tukar shift)
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('target_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('swap_date');
            $table->enum('status', ['pending', 'target_confirmed', 'supervisor_approved', 'hr_approved', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('target_confirmed_at')->nullable();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // Workday Swap (tukar hari kerja)
        Schema::create('workday_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->date('swap_to_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'supervisor_approved', 'hr_approved', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // Attendance
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('date')->index();
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->timestamp('break_start_at')->nullable();
            $table->timestamp('break_end_at')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->decimal('working_hours', 5, 2)->default(0);
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->decimal('clock_in_accuracy', 8, 2)->nullable(); // meter
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->enum('status', ['present', 'late', 'absent', 'leave', 'sick', 'permission', 'holiday'])->default('present');
            $table->boolean('is_face_verified')->default(false);
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });

        // Attendance Logs (audit trail per action)
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendance')->nullOnDelete();
            $table->enum('action', ['clock_in', 'clock_out', 'break', 'return_break', 'location_update']);
            $table->timestamp('action_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('device_info')->nullable();
            $table->boolean('is_face_verified')->default(false);
            $table->timestamps();
            $table->index(['employee_id', 'action_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('workday_swap_requests');
        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employee_faces');
    }
};
