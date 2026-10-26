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
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->enum('category', ['monthly', 'annual', 'loan'])->default('monthly')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
