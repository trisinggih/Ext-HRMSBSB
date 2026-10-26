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
            if (!Schema::hasColumn('employees', 'attendance_location_type')) {
                $table->string('attendance_location_type')->default('assigned_branch')->after('can_clock_in_anywhere');
            }
            if (!Schema::hasColumn('employees', 'allowed_branch_ids')) {
                $table->json('allowed_branch_ids')->nullable()->after('attendance_location_type');
            }
        });

        \Illuminate\Support\Facades\DB::table('employees')
            ->where('can_clock_in_anywhere', true)
            ->update(['attendance_location_type' => 'anywhere']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['attendance_location_type', 'allowed_branch_ids']);
        });
    }
};
