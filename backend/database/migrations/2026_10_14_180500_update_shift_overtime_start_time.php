<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('shifts')
            ->where('max_overtime_request_time', '20:30:00')
            ->update(['max_overtime_request_time' => '20:00:00']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('shifts')
            ->where('max_overtime_request_time', '20:00:00')
            ->update(['max_overtime_request_time' => '20:30:00']);
    }
};
