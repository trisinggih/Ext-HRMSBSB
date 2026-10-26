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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('mobile_access_status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_active');
            $table->decimal('last_latitude', 10, 8)->nullable()->after('mobile_access_status');
            $table->decimal('last_longitude', 11, 8)->nullable()->after('last_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['mobile_access_status', 'last_latitude', 'last_longitude']);
        });
    }
};
