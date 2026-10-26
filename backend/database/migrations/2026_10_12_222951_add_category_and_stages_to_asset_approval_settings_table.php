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
        Schema::table('asset_approval_settings', function (Blueprint $table) {
            $table->string('approval_category')->default('pengajuan_perlengkapan')->after('name');
            // 'pengajuan_perlengkapan', 'permohonan_alat', 'perbaikan_alat'

            $table->string('mengetahui_title')->nullable()->after('mengetahui_employee_id');
            // e.g. 'Manager HR & GA' atau 'Manager Departemen'

            // Stage 2 mengetahui (digunakan di perbaikan alat / form multi-tahap)
            $table->string('mengetahui_2_type')->nullable()->after('mengetahui_title'); // 'finance_manager', 'specific_employee'
            $table->foreignId('mengetahui_2_employee_id')->nullable()->after('mengetahui_2_type')->constrained('employees')->nullOnDelete();
            $table->string('mengetahui_2_title')->nullable()->after('mengetahui_2_employee_id'); // e.g. 'Manager Finance & Accounting'

            $table->string('disetujui_title')->nullable()->after('disetujui_employee_id');
            // e.g. 'Direktur'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_approval_settings', function (Blueprint $table) {
            $table->dropForeign(['mengetahui_2_employee_id']);
            $table->dropColumn([
                'approval_category',
                'mengetahui_title',
                'mengetahui_2_type',
                'mengetahui_2_employee_id',
                'mengetahui_2_title',
                'disetujui_title',
            ]);
        });
    }
};
