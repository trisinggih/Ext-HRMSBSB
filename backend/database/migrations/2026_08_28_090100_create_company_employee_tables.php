<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('npwp')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Branch
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable()->index();
            $table->unsignedInteger('attendance_radius')->default(20); // meter
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'name']);
        });

        // Department
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['branch_id', 'name']);
        });

        // Division
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['department_id', 'name']);
        });

        // Position / Jabatan
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['division_id', 'branch_id']);
        });

        // Employees (auth Mobile)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique(); // NIP
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->string('ktp_number')->nullable()->index();
            $table->string('npwp')->nullable();
            $table->string('bpjs_kesehatan')->nullable();
            $table->string('bpjs_ketenagakerjaan')->nullable();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('join_date')->nullable();
            $table->enum('employment_status', ['permanent', 'contract', 'probation', 'internship', 'outsourcing'])->default('probation');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->index(['branch_id', 'department_id', 'employment_status']);
        });

        // Employee Family & Dependent
        Schema::create('employee_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('relation', ['spouse', 'husband', 'wife', 'child', 'parent', 'other'])->default('other');
            $table->string('name');
            $table->date('birth_date')->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->boolean('is_tax_dependent')->default(false);
            $table->timestamps();
            $table->index('employee_id');
        });

        // Employee Documents
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('document_type'); // ktp, npwp, bpjs, ijazah, kontrak
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'document_type']);
        });

        // Employee Device (Mobile device management)
        Schema::create('employee_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('device_name')->nullable();
            $table->string('device_id')->nullable()->index(); // unique device identifier
            $table->string('platform')->nullable(); // android/ios
            $table->string('push_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_devices');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_families');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
