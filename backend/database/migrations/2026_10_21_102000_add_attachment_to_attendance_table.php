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
        if (!Schema::hasColumn('attendance', 'attachment')) {
            Schema::table('attendance', function (Blueprint $table) {
                if (Schema::hasColumn('attendance', 'notes')) {
                    $table->string('attachment')->nullable()->after('notes');
                } else {
                    $table->string('attachment')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('attendance', 'attachment')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->dropColumn('attachment');
            });
        }
    }
};
