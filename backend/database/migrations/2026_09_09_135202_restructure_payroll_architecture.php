<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Payroll Components
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('calculation_source')->default('FIXED');
            $table->string('calculation_method')->default('FIXED_AMOUNT');
            $table->decimal('default_value', 15, 2)->nullable();
            $table->text('formula')->nullable();
            $table->boolean('is_bpjs_basis')->default(false);
            $table->boolean('show_on_payslip')->default(true);
        });

        // 2. Payroll Rules
        Schema::create('payroll_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('source')->default('FIXED');
            $table->string('method')->default('FIXED_AMOUNT');
            $table->text('condition')->nullable();
            $table->text('formula')->nullable();
            $table->integer('priority')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Payroll Rule Details
        Schema::create('payroll_rule_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_rule_id')->constrained('payroll_rules')->cascadeOnDelete();
            $table->decimal('min_value', 15, 2)->nullable();
            $table->decimal('max_value', 15, 2)->nullable();
            $table->decimal('rate', 8, 4)->nullable();
            $table->decimal('fixed_amount', 15, 2)->nullable();
            $table->text('formula')->nullable();
            $table->integer('priority')->default(1);
            $table->timestamps();
        });

        // 4. Salary Structures
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

        // 5. Payroll Inputs
        Schema::create('payroll_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('source')->default('MANUAL');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 6. Update Payroll Details
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->foreignId('payroll_rule_id')->nullable()->constrained('payroll_rules')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->decimal('rate', 8, 4)->nullable();
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->string('source')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('calculation_snapshot')->nullable();
            $table->text('note')->nullable();
        });

        // 7. Update Payroll Runs (payrolls)
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->decimal('gross_earnings', 15, 2)->default(0)->after('employee_id');
            $table->decimal('total_benefit', 15, 2)->default(0)->after('gross_earnings');
            $table->timestamp('calculated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn(['gross_earnings', 'total_benefit', 'calculated_at']);
        });

        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropForeign(['payroll_rule_id']);
            $table->dropColumn([
                'payroll_rule_id', 'quantity', 'rate', 'base_amount', 'source', 
                'reference_type', 'reference_id', 'calculation_snapshot', 'note'
            ]);
        });

        Schema::dropIfExists('payroll_inputs');
        Schema::dropIfExists('employee_salary_structures');
        Schema::dropIfExists('salary_structure_components');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('payroll_rule_details');
        Schema::dropIfExists('payroll_rules');

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'calculation_source', 'calculation_method', 
                'default_value', 'formula', 'is_bpjs_basis', 'show_on_payslip'
            ]);
        });
    }
};
