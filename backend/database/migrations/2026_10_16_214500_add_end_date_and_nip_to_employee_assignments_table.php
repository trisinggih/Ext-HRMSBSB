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
        Schema::table('employee_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_assignments', 'end_date')) {
                $table->date('end_date')->nullable()->after('join_date');
            }
            if (!Schema::hasColumn('employee_assignments', 'nip')) {
                $table->string('nip')->nullable()->after('employment_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('employee_assignments', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (Schema::hasColumn('employee_assignments', 'nip')) {
                $table->dropColumn('nip');
            }
        });
    }
};
