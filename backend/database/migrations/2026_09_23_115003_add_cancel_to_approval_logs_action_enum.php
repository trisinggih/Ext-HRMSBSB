<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE approval_logs MODIFY COLUMN action ENUM('submit', 'approve', 'reject', 'escalate', 'revise', 'cancel') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this might cause data loss if there are 'cancel' records, but here is the original structure:
        DB::statement("ALTER TABLE approval_logs MODIFY COLUMN action ENUM('submit', 'approve', 'reject', 'escalate', 'revise') NOT NULL");
    }
};
