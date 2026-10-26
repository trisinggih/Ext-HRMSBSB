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
        DB::statement("ALTER TABLE overtime_requests MODIFY status ENUM('pending', 'supervisor_approved', 'hr_approved', 'approved', 'rejected', 'cancelled', 'in_progress', 'done') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE overtime_requests MODIFY status ENUM('pending', 'supervisor_approved', 'hr_approved', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'");
    }
};
