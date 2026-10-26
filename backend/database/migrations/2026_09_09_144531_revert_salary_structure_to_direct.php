<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the master/slave tables from my previous migration
        Schema::dropIfExists('employee_salary_structures');
        Schema::dropIfExists('salary_structure_components');
        Schema::dropIfExists('salary_structures');

        // 2. Recreate salary_structures as the direct employee component mapping
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_structure_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->date('effective_date');
            $table->timestamps();
        });
    }
};
