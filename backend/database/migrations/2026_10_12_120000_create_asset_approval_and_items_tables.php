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
        // 1. Modifikasi asset_requests untuk mendukung tipe pengajuan, departemen, dan alur approval
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->string('request_type')->default('kebutuhan_rutin')->after('branch_id'); 
            // 'kebutuhan_karyawan_baru', 'kebutuhan_rutin', 'kebutuhan_perbaikan'
            $table->foreignId('department_id')->nullable()->after('branch_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->after('department_id')->constrained('employees')->nullOnDelete();
            $table->string('submission_month')->nullable()->after('manager_id'); // e.g. 'Oktober 2026'
            
            // Kolom ukuran dan penempatan untuk item utama / legacy single item
            $table->string('item_size')->nullable()->after('item_name'); // keterangan ukuran (misal: kabel 10m, lampu 18W)
            $table->string('placement')->nullable()->after('item_size');  // penempatan (misal: Ruang Front Office, Bengkel)

            // Approval signatures tracking
            $table->foreignId('acknowledged_by')->nullable()->after('status')->constrained('employees')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');
            $table->foreignId('approved_by')->nullable()->after('acknowledged_at')->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // 2. Tabel asset_request_items untuk mendukung multi-item sesuai tabel PDF
        Schema::create('asset_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_request_id')->constrained('asset_requests')->cascadeOnDelete();
            $table->string('item_name');
            $table->string('item_size')->nullable(); // Keterangan ukuran (kabel, lampu, meja, dll)
            $table->integer('quantity')->default(1);
            $table->string('placement')->nullable(); // Penempatan lokasi / orang
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('estimated_price', 15, 2)->default(0); // Estimasi harga total per item
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Tabel konfigurasi approval asset terpisah (asset_approval_settings)
        Schema::create('asset_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Konfigurasi Approval Aset');
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->string('request_type')->nullable(); // null = semua, atau 'kebutuhan_karyawan_baru', 'kebutuhan_rutin', 'kebutuhan_perbaikan'

            // Mengetahui (Level 1 / Manager)
            $table->string('mengetahui_type')->default('department_manager'); // 'department_manager', 'direct_supervisor', 'specific_employee'
            $table->foreignId('mengetahui_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            // Disetujui (Level 2 / Final Approver)
            $table->string('disetujui_type')->default('director'); // 'director', 'finance_manager', 'ga_manager', 'specific_employee'
            $table->foreignId('disetujui_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Tabel log riwayat persetujuan aset (asset_request_approvals)
        Schema::create('asset_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_request_id')->constrained('asset_requests')->cascadeOnDelete();
            $table->integer('level')->default(1); // 1 = Mengetahui, 2 = Disetujui
            $table->string('role_name')->default('Mengetahui'); // 'Mengetahui' atau 'Disetujui'
            $table->foreignId('approver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('action_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_request_approvals');
        Schema::dropIfExists('asset_approval_settings');
        Schema::dropIfExists('asset_request_items');

        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['acknowledged_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'request_type',
                'department_id',
                'manager_id',
                'submission_month',
                'item_size',
                'placement',
                'acknowledged_by',
                'acknowledged_at',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
