<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Apakah employee boleh login ke web HRMS
            $table->boolean('web_access_enabled')->default(false)->after('mobile_access_status');

            // Role web karyawan (null = belum diberi akses)
            $table->enum('web_role', ['super_admin', 'staff_hr', 'spv'])->nullable()->after('web_access_enabled');

            // Untuk SPV: array ID divisi yang dikelola (null = tidak ada)
            $table->json('managed_division_ids')->nullable()->after('web_role');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['web_access_enabled', 'web_role', 'managed_division_ids']);
        });
    }
};
