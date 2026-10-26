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
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'quantity')) {
                $table->integer('quantity')->default(1)->after('name');
            }
            if (!Schema::hasColumn('assets', 'unit')) {
                $table->string('unit')->default('Unit')->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit']);
        });
    }
};
