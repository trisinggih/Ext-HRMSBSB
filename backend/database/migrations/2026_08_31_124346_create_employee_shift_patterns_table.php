<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shift_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 0 = Minggu, 1 = Senin, dst (berdasarkan Carbon)
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete(); // Jika null, berarti libur
            $table->timestamps();

            $table->unique(['employee_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shift_patterns');
    }
};
