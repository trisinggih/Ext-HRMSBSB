<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('employee_approval_delegations');
        Schema::dropIfExists('employee_approval_lines');

        // Alur persetujuan bertingkat per karyawan per modul
        Schema::create('employee_approval_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('module'); // leave, sick_leave, permission, reimbursement, late, outside_location
            $table->unsignedTinyInteger('step_order')->default(1); // Tingkat 1, 2, 3, ...
            $table->foreignId('approver_id')->constrained('employees')->cascadeOnDelete(); // Siapa yang approve
            $table->boolean('is_optional')->default(false); // Opsional atau wajib
            $table->timestamps();
            $table->unique(['employee_id', 'module', 'step_order']);
            $table->index(['employee_id', 'module']);
        });

        // Delegasi wewenang ketika pejabat approver sedang tidak ada
        Schema::create('employee_approval_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id')->constrained('employees')->cascadeOnDelete(); // Yang mendelegasikan
            $table->foreignId('delegate_id')->constrained('employees')->cascadeOnDelete(); // Yang menerima delegasi
            $table->string('module')->nullable(); // Null = berlaku untuk semua modul
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['delegator_id', 'start_date', 'end_date'], 'ead_delegator_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_approval_delegations');
        Schema::dropIfExists('employee_approval_lines');
    }
};
