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
        Schema::create('tax_ter_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['A', 'B', 'C'])->comment('Kategori TER berdasarkan PTKP');
            $table->decimal('min_bruto', 15, 2)->comment('Batas bawah penghasilan bruto');
            $table->decimal('max_bruto', 15, 2)->nullable()->comment('Batas atas penghasilan bruto, null jika tidak ada batas');
            $table->decimal('rate', 5, 2)->comment('Tarif pajak dalam persentase, misal 5.50');
            $table->date('effective_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_ter_rates');
    }
};
