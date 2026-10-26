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
        Schema::table('employee_mutations', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_mutations', 'new_nip')) {
                $table->string('new_nip')->nullable()->after('new_supervisor_id');
            }
            if (!Schema::hasColumn('employee_mutations', 'prorate_payroll')) {
                $table->boolean('prorate_payroll')->default(true)->after('effective_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table) {
            $table->dropColumn(['new_nip', 'prorate_payroll']);
        });
    }
};
