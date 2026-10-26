<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_components', 'is_separate_payslip')) {
                $table->boolean('is_separate_payslip')->default(false)->after('is_active');
            }
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'run_type')) {
                $table->string('run_type')->default('main')->after('employee_assignment_id'); // main, bonus
            }
            if (!Schema::hasColumn('payroll_runs', 'salary_cycle_type')) {
                $table->string('salary_cycle_type')->default('monthly')->after('run_type'); // monthly, weekly, daily
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('is_separate_payslip');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn(['run_type', 'salary_cycle_type']);
        });
    }
};
