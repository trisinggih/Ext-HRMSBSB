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
        Schema::table('employee_payroll_settings', function (Blueprint $table) {
            $table->decimal('max_loan_limit', 15, 2)->nullable()->after('bank_account_name')->comment('Batas maksimum pinjaman (kasbon)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payroll_settings', function (Blueprint $table) {
            $table->dropColumn('max_loan_limit');
        });
    }
};
