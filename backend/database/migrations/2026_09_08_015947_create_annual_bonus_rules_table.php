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
        Schema::create('annual_bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->integer('distribution_month')->comment('1-12');
            $table->boolean('is_prorated')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_bonus_rules');
    }
};
