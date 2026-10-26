<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            // Status perangkat: 'approved' = diizinkan login, 'pending' = menunggu persetujuan admin, 'rejected' = dicabut
            $table->enum('status', ['approved', 'pending', 'rejected'])
                  ->default('approved')
                  ->after('is_active');

            // Kapan status berubah
            $table->timestamp('approved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};
