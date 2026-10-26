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
        try {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->dropForeign('payroll_periods_processed_by_foreign');
            });
        } catch (\Throwable $e) {
            // Foreign key does not exist or already dropped
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->foreign('processed_by')->references('id')->on('admins')->nullOnDelete();
        });
    }
};
