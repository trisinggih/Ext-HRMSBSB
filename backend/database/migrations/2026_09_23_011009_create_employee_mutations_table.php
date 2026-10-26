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
        Schema::create('employee_mutations', function (Blueprint $table) {
            $table->id();
            $table->string('memo_number')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            $table->enum('type', ['mutasi', 'promosi'])->default('mutasi');
            
            // Old Assignment
            $table->foreignId('old_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('old_branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('old_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('old_division_id')->nullable()->constrained('divisions')->onDelete('set null');
            $table->foreignId('old_position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->foreignId('old_supervisor_id')->nullable()->constrained('employees')->onDelete('set null');
            
            // New Assignment
            $table->foreignId('new_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('new_branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('new_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('new_division_id')->nullable()->constrained('divisions')->onDelete('set null');
            $table->foreignId('new_position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->foreignId('new_supervisor_id')->nullable()->constrained('employees')->onDelete('set null');
            
            $table->date('effective_date');
            
            $table->string('status')->default('pending'); // pending, approved, rejected
            
            $table->text('reason')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_mutations');
    }
};
