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
        DB::table('payroll_components')
            ->where('code', 'kerajinan')
            ->update([
                'has_rules' => true,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'FORMULA',
                'formula' => 'if ({total_unpaid_leave_days} == 0) 500000 else if ({total_unpaid_leave_days} == 1) 300000 else if ({total_unpaid_leave_days} == 2) 200000 else if ({total_unpaid_leave_days} == 3) 100000 else 0'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payroll_components')
            ->where('code', 'kerajinan')
            ->update([
                'has_rules' => false,
                'formula' => null
            ]);
    }
};
