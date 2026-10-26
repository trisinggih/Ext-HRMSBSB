<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('cycle_type')->default('monthly'); // 'daily', 'weekly', 'monthly'
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->string('salary_cycle_type')->default('monthly'); // 'daily', 'weekly', 'monthly'
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'cycle_type']);
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropColumn('salary_cycle_type');
        });
    }
};
