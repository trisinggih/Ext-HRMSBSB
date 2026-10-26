<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE permission_requests MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'personal'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE permission_requests MODIFY COLUMN type ENUM('personal','family','business','other') NOT NULL DEFAULT 'personal'");
    }
};
