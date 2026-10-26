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
        Schema::create('ptkp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('status', 10)->unique();
            $table->string('name')->nullable();
            $table->decimal('ptkp_amount', 15, 2)->default(0);
            $table->date('effective_from')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->decimal('rate', 8, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptkp_settings');
        Schema::dropIfExists('tax_brackets');
    }
};
