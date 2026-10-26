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
        Schema::table('employee_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_assignments', 'placement_company_id')) {
                $table->foreignId('placement_company_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropForeign(['placement_company_id']);
            $table->dropColumn('placement_company_id');
        });
    }
};
