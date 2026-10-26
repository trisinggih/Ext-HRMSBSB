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
        Schema::table('payroll_components', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_components', 'has_rules')) {
                $table->boolean('has_rules')->default(false)->after('default_value');
            }
            if (!Schema::hasColumn('payroll_components', 'formula')) {
                $table->text('formula')->nullable()->after('has_rules');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn(['has_rules', 'formula']);
        });
    }
};
