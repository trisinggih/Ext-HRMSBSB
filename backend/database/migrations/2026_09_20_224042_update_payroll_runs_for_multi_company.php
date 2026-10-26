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
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->foreignId('employee_assignment_id')->nullable()->after('employee_id')->constrained('employee_assignments')->nullOnDelete();
        });

        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->foreignId('employee_assignment_id')->nullable()->after('employee_id')->constrained('employee_assignments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->dropForeign(['employee_assignment_id']);
            $table->dropColumn('employee_assignment_id');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropForeign(['employee_assignment_id']);
            $table->dropColumn('employee_assignment_id');
        });
    }
};
