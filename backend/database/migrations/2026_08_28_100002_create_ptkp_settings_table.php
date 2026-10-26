<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setting PTKP (Penghasilan Tidak Kena Pajak) per status, dengan tanggal efektif.
 * Contoh status: TK0, TK1, K0, K1, ... nilai diambil dari config tabel (bukan hardcode).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptkp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('status', 10)->unique(); // TK0, K0, K1, ...
            $table->string('name')->nullable(); // "Tidak Kawin Tanpa Tanggungan"
            $table->decimal('ptkp_amount', 15, 2)->default(0);
            $table->date('effective_from')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptkp_settings');
    }
};
