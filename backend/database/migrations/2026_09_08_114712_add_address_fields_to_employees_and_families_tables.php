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
        Schema::table('employees', function (Blueprint $table) {
            $table->text('domicile_address')->nullable()->after('address');
            $table->text('residential_address')->nullable()->after('domicile_address');
        });

        Schema::table('employee_families', function (Blueprint $table) {
            $table->text('domicile_address')->nullable()->after('occupation');
            $table->text('residential_address')->nullable()->after('domicile_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_and_families_tables', function (Blueprint $table) {
            //
        });
    }
};
