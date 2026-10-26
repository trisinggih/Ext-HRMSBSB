<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing employment data
        DB::statement('
            INSERT INTO employee_assignments 
            (employee_id, company_id, branch_id, department_id, division_id, position_id, supervisor_id, join_date, employment_status, is_primary, created_at, updated_at)
            SELECT id, company_id, branch_id, department_id, division_id, position_id, supervisor_id, join_date, employment_status, 1, created_at, updated_at 
            FROM employees 
            WHERE company_id IS NOT NULL
        ');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['division_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['supervisor_id']);
            
            $table->dropColumn([
                'company_id', 'branch_id', 'department_id', 'division_id', 
                'position_id', 'supervisor_id', 'join_date', 'employment_status'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('join_date')->nullable();
            $table->string('employment_status')->nullable();
        });
    }
};
