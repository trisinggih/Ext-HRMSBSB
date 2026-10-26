<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_periods', function (Blueprint $table) {
            // Modify type to varchar(50) so daily, weekly, monthly, quarterly are all supported
            // Using DB statement for MySQL compatibility with enum alteration
        });

        DB::statement("ALTER TABLE kpi_periods MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'monthly'");

        Schema::table('kpi_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_periods', 'department_group')) {
                $table->string('department_group', 50)->nullable()->default('all')->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_periods', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_periods', 'department_group')) {
                $table->dropColumn('department_group');
            }
        });

        DB::statement("ALTER TABLE kpi_periods MODIFY COLUMN type ENUM('monthly','quarterly','semesterly','annual') NOT NULL DEFAULT 'monthly'");
    }
};
