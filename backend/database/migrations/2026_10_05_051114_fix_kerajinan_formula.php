<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
                'has_rules' => false,
                'formula' => null
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration since it's hard to revert exactly,
        // or we could set it back to the broken formula.
    }
};
