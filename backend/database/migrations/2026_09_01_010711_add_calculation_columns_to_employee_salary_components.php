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
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])->default('fixed')->after('payroll_component_id');
            $table->text('formula')->nullable()->after('percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->dropColumn(['calculation_type', 'formula']);
        });
    }
};
