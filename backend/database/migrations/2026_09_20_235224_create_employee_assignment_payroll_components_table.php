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
        Schema::create('employee_assignment_payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_assignment_id')->constrained('employee_assignments', 'id', 'emp_assign_fk')->onDelete('cascade');
            $table->foreignId('payroll_component_id')->constrained('payroll_components', 'id', 'pay_comp_fk')->onDelete('cascade');
            $table->timestamps();
            
            // A component shouldn't be added multiple times for the same assignment
            $table->unique(['employee_assignment_id', 'payroll_component_id'], 'emp_assign_comp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_assignment_payroll_components');
    }
};
