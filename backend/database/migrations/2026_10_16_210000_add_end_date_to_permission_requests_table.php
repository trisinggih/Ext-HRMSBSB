<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('permission_requests', 'end_date')) {
                $table->date('end_date')->nullable()->after('date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permission_requests', function (Blueprint $table) {
            if (Schema::hasColumn('permission_requests', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
};
