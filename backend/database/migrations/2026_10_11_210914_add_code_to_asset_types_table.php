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
        Schema::table('asset_types', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_types', 'code')) {
                $table->string('code', 50)->nullable()->after('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
