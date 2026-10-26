<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('overtime_threshold_minutes');
            $table->time('max_overtime_request_time')->nullable()->after('late_tolerance_minutes');
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->timestamp('overtime_clock_in_at')->nullable()->after('clock_out_lng');
            $table->timestamp('overtime_clock_out_at')->nullable()->after('overtime_clock_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['overtime_clock_in_at', 'overtime_clock_out_at']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('max_overtime_request_time');
            $table->unsignedInteger('overtime_threshold_minutes')->default(0)->after('late_tolerance_minutes');
        });
    }
};
