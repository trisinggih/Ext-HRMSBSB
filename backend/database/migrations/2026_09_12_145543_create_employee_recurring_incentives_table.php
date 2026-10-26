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
        Schema::create('employee_recurring_incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // bonus, performance, kuota, etc
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            
            // Aturan tanggal pencairan (1-31, atau null jika mengikuti gaji utama)
            $table->integer('payout_day_of_month')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_recurring_incentives');
    }
};
