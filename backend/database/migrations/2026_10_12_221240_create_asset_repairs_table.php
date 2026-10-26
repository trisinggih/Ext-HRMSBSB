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
        Schema::create('asset_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            // Header info
            $table->date('submission_date')->nullable();
            $table->string('submission_month')->nullable();

            // A. Detail Alat Kerja
            $table->string('item_name'); // Nama Alat
            $table->string('brand_model')->nullable(); // Merek / Tipe
            $table->string('asset_code')->nullable(); // Kode Aset (jika ada)
            $table->integer('quantity')->default(1); // Jumlah (unit)
            $table->string('location')->nullable(); // Lokasi Alat
            $table->string('condition')->default('rusak'); // rusak, tidak_berfungsi, lainnya
            $table->string('condition_other')->nullable();

            // B. Uraian Kerusakan dan Permohonan
            $table->text('damage_description'); // Uraian kerusakan / kendala
            $table->text('repair_description')->nullable(); // Perbaikan yang dibutuhkan / keterangan tambahan
            $table->string('pic_name')->nullable(); // PIC / pengguna alat
            $table->boolean('operational_disrupted')->default(false); // Operasional terganggu: ya / tidak

            // C. Estimasi dan Tindak Lanjut
            $table->decimal('estimated_cost', 15, 2)->nullable(); // Estimasi biaya
            $table->string('priority')->default('sedang'); // tinggi, sedang, rendah
            $table->string('bank_account_info')->nullable(); // Nama Bank & No Rekening
            $table->date('repair_date')->nullable(); // Tanggal perbaikan

            // Status & Approval
            $table->enum('status', ['pending', 'approved', 'in_repair', 'completed', 'rejected'])->default('pending');
            $table->foreignId('acknowledged_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_repairs');
    }
};
