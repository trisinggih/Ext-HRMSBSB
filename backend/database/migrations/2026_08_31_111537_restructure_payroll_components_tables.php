<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove hardcoded allowances from employee_payroll_settings
        Schema::table('employee_payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'basic_salary',
                'transport_allowance',
                'meal_allowance',
                'position_allowance',
                'communication_allowance',
            ]);
        });

        // 2. Remove amounts and calculation logic from master payroll_components
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn([
                'calculation_type',
                'amount',
                'percentage',
                'formula'
            ]);
        });

        // No new table needed, as employee_salary_components already exists
    }

    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])->default('fixed');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('percentage', 8, 4)->nullable();
            $table->text('formula')->nullable();
        });

        Schema::table('employee_payroll_settings', function (Blueprint $table) {
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('meal_allowance', 15, 2)->default(0);
            $table->decimal('position_allowance', 15, 2)->default(0);
            $table->decimal('communication_allowance', 15, 2)->default(0);
        });
    }
};
