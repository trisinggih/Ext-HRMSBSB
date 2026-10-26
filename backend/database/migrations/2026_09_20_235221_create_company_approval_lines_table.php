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
        Schema::create('company_approval_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('module')->index(); // e.g., 'leave', 'loan', 'attendance'
            $table->integer('level'); // 1, 2, 3
            $table->string('approver_type'); // 'supervisor', 'manager', 'specific_user'
            $table->foreignId('specific_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_approval_lines');
    }
};
