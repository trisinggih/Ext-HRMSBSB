<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penambahan kolom untuk mendukung Payroll Phase 8 (konfigurasi berbasis tanggal efektif).
 * - payroll_periods: period (YYYY-MM), processed_by, processed_at
 * - payroll_components: is_taxable, effective_from
 * - bpjs_settings: is_tax_deductible (flag untuk bobot PPh21)
 *
 * Ini hanya menambah kolom TIDAK mengubah kolom lama / enum yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'period')) {
                $table->string('period', 7)->nullable()->after('name')->index(); // YYYY-MM
            }
            if (!Schema::hasColumn('payroll_periods', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->after('status')->constrained('admins')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_periods', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('processed_by');
            }
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_components', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('payroll_components', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('is_taxable')->index();
            }
        });

        Schema::table('bpjs_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('bpjs_settings', 'is_tax_deductible')) {
                $table->boolean('is_tax_deductible')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processed_by');
            if (Schema::hasColumn('payroll_periods', 'processed_at')) {
                $table->dropColumn('processed_at');
            }
            if (Schema::hasColumn('payroll_periods', 'period')) {
                $table->dropColumn('period');
            }
        });
        Schema::table('payroll_components', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_components', 'is_taxable')) {
                $table->dropColumn('is_taxable');
            }
            if (Schema::hasColumn('payroll_components', 'effective_from')) {
                $table->dropColumn('effective_from');
            }
        });
        Schema::table('bpjs_settings', function (Blueprint $table) {
            if (Schema::hasColumn('bpjs_settings', 'is_tax_deductible')) {
                $table->dropColumn('is_tax_deductible');
            }
        });
    }
};
