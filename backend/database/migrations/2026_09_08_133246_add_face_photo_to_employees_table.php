<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Path ke foto wajah referensi, disimpan di storage/app/public/faces/
            $table->string('face_photo')->nullable()->after('photo');
            // Kapan wajah terakhir di-register
            $table->timestamp('face_registered_at')->nullable()->after('face_photo');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['face_photo', 'face_registered_at']);
        });
    }
};
