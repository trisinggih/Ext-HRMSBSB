<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_requests', 'employee_assignment_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignId('employee_assignment_id')->nullable()->constrained('employee_assignments')->nullOnDelete();
            });
        }
        
        if (!Schema::hasColumn('attendance', 'employee_assignment_id')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->foreignId('employee_assignment_id')->nullable()->constrained('employee_assignments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_requests', 'employee_assignment_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropForeign(['employee_assignment_id']);
                $table->dropColumn('employee_assignment_id');
            });
        }
        
        if (Schema::hasColumn('attendance', 'employee_assignment_id')) {
            Schema::table('attendance', function (Blueprint $table) {
                $table->dropForeign(['employee_assignment_id']);
                $table->dropColumn('employee_assignment_id');
            });
        }
    }
};
