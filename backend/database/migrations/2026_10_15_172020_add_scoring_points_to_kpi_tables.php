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
        Schema::table('kpi_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_indicators', 'scoring_points')) {
                $table->json('scoring_points')->nullable()->after('calculation_method');
            }
        });

        Schema::table('kpi_template_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_template_indicators', 'scoring_points')) {
                $table->json('scoring_points')->nullable()->after('calculation_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_indicators', 'scoring_points')) {
                $table->dropColumn('scoring_points');
            }
        });

        Schema::table('kpi_template_indicators', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_template_indicators', 'scoring_points')) {
                $table->dropColumn('scoring_points');
            }
        });
    }
};
