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
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->dropUnique('emp_comp_unique');
            $table->unique(['employee_assignment_id', 'payroll_component_id'], 'emp_assign_comp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->dropUnique('emp_assign_comp_unique');
            $table->unique(['employee_id', 'payroll_component_id'], 'emp_comp_unique');
        });
    }
};
