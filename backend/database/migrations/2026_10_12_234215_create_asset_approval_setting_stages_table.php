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
        Schema::create('asset_approval_setting_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_approval_setting_id')
                ->constrained('asset_approval_settings')
                ->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->string('stage_name')->nullable(); // e.g. "Tahap 1: Mengetahui", "Tahap 2: Diperiksa", "Tahap Akhir: Disetujui"
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('role_title')->nullable(); // e.g. "Manager HR & GA", "Manager Finance", "Direktur"
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_approval_setting_stages');
    }
};
