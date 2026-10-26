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
        if (!Schema::hasTable('payroll_deductions')) {
            Schema::create('payroll_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
                $table->string('type')->default('other');
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->date('deduction_date');
                $table->string('attachment')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['employee_id', 'deduction_date']);
                $table->index(['payroll_period_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_deductions');
    }
};
