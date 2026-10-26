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
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dateTime('actual_start_time')->nullable()->after('status');
            $table->dateTime('actual_end_time')->nullable()->after('actual_start_time');
            $table->text('result_note')->nullable()->after('actual_end_time');
            $table->json('result_photos')->nullable()->after('result_note');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropColumn(['actual_start_time', 'actual_end_time', 'result_note', 'result_photos']);
        });
    }
};
