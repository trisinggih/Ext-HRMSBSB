<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur gaji per karyawan (assign komponen + jumlah + tanggal efektif).
 * Memetakan payroll_components ke employee dengan amount per karyawan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('percentage', 8, 4)->nullable(); // untuk komponen tipe percentage (override)
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_component_id'], 'emp_comp_unique');
            $table->index(['employee_id', 'is_active', 'effective_from'], 'emp_comp_active_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_components');
    }
};
