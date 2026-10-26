<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_indicators', 'frequency')) {
                $table->string('frequency', 50)->nullable()->after('category');
            }
            if (!Schema::hasColumn('kpi_indicators', 'calculation_method')) {
                $table->text('calculation_method')->nullable()->after('frequency');
            }
        });

        Schema::table('kpi_template_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_template_indicators', 'frequency')) {
                $table->string('frequency', 50)->nullable()->after('target');
            }
            if (!Schema::hasColumn('kpi_template_indicators', 'calculation_method')) {
                $table->text('calculation_method')->nullable()->after('frequency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'calculation_method']);
        });

        Schema::table('kpi_template_indicators', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'calculation_method']);
        });
    }
};
