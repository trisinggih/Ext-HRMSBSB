<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'daily' and 'weekly' to employee_payroll_settings.pay_frequency
        DB::statement("ALTER TABLE employee_payroll_settings MODIFY COLUMN pay_frequency ENUM('monthly', 'biweekly', 'weekly', 'daily') DEFAULT 'monthly'");

        // Add 'daily' and 'weekly' to payroll_components.category
        DB::statement("ALTER TABLE payroll_components MODIFY COLUMN category ENUM('monthly', 'annual', 'loan', 'weekly', 'daily') DEFAULT 'monthly'");

        // Add 'type' to payroll_periods if it doesn't exist
        DB::statement("ALTER TABLE payroll_periods ADD COLUMN type ENUM('monthly', 'weekly', 'daily') DEFAULT 'monthly' AFTER name");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting enums in MySQL can be risky if data exists, but for the sake of completeness:
        DB::statement("ALTER TABLE employee_payroll_settings MODIFY COLUMN pay_frequency ENUM('monthly', 'biweekly', 'weekly') DEFAULT 'monthly'");
        DB::statement("ALTER TABLE payroll_components MODIFY COLUMN category ENUM('monthly', 'annual', 'loan') DEFAULT 'monthly'");
        DB::statement("ALTER TABLE payroll_periods DROP COLUMN type");
    }
};
