<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['province', 'postal_code', 'email']);
        });
    }
};
