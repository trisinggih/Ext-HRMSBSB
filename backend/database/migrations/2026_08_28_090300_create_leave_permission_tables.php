<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leave Types
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('category', ['annual', 'maternity', 'marriage', 'special', 'public', 'unpaid', 'custom'])->default('custom');
            $table->unsignedInteger('default_days')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_carry_forward')->default(false);
            $table->unsignedInteger('max_carry_forward_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Leave Balance per employee
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedInteger('year');
            $table->decimal('quota', 5, 1)->default(0);
            $table->decimal('used', 5, 1)->default(0);
            $table->decimal('carried_forward', 5, 1)->default(0);
            $table->decimal('expired', 5, 1)->default(0);
            $table->decimal('remaining', 5, 1)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        // Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status', 'start_date']);
        });

        // Permission Request (Izin)
        Schema::create('permission_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['personal', 'family', 'business', 'other'])->default('personal');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('reason');
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // Sick Leave Request
        Schema::create('sick_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('medical_certificate')->nullable(); // surat dokter
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // Late Request (pengajuan terlambat)
        Schema::create('late_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendance')->nullOnDelete();
            $table->date('date');
            $table->time('scheduled_clock_in')->nullable();
            $table->time('actual_clock_in')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->text('reason');
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // Employee Location (tracking)
        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('mode', ['working_hours', 'field', 'business_trip'])->default('working_hours');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['employee_id', 'recorded_at']);
        });

        // Location tracking sessions
        Schema::create('location_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('mode', ['working_hours', 'field', 'business_trip'])->default('working_hours');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_tracking_sessions');
        Schema::dropIfExists('employee_locations');
        Schema::dropIfExists('late_requests');
        Schema::dropIfExists('sick_leave_requests');
        Schema::dropIfExists('permission_requests');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
