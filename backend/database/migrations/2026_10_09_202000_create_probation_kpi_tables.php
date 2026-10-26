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
        Schema::create('probation_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('evaluation_date');
            $table->date('probation_start_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('probation_period_text')->nullable(); // cth: "3 Bulan"
            
            // Rekap Absensi
            $table->integer('absent_days')->default(0);      // Alpa
            $table->integer('permit_days')->default(0);      // Ijin
            $table->integer('sick_days')->default(0);        // Surat Dokter
            $table->integer('half_days')->default(0);        // Pulang 1/2 hari
            $table->integer('late_days')->default(0);        // Pulang kesiangan / Telat

            // Catatan Pelanggaran
            $table->boolean('has_oral_warning')->default(false); // Peringatan Lisan
            $table->boolean('has_sp1')->default(false);          // SP-1
            $table->boolean('has_sp2')->default(false);          // SP-2

            // Scores (JSON breakdown per 10 aspek)
            $table->json('aspect_scores')->nullable();

            // Summary Score
            $table->decimal('total_weighted_score', 8, 2)->default(0); // Total Skor (max 500)
            $table->decimal('final_score_hundred', 8, 2)->default(0);   // Total / 5 (max 100)
            $table->string('predicate')->nullable();                   // Sangat Baik, Baik, Cukup, Kurang, Sangat Kurang

            // Komentar Atasan
            $table->text('strengths')->nullable();       // Kelebihan Karyawan
            $table->text('improvements')->nullable();    // Area yang perlu ditingkatkan

            // Keputusan Akhir
            $table->enum('final_decision', ['passed_permanent', 'extend_probation', 'terminated'])->default('passed_permanent');
            $table->text('decision_notes')->nullable();

            // Workflow status
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by_dept_manager')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by_hrd_manager')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('salary_increase_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('probation_evaluation_id')->constrained('probation_evaluations')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('proposal_date');
            $table->string('employment_duration_text')->nullable(); // Masa Kerja

            // Gaji Saat Ini
            $table->decimal('current_daily_salary', 15, 2)->default(0);
            $table->decimal('current_monthly_salary', 15, 2)->default(0);
            $table->decimal('current_attendance_bonus', 15, 2)->default(0);
            $table->decimal('current_position_allowance', 15, 2)->default(0);
            $table->decimal('current_leader_allowance', 15, 2)->default(0);

            // Usulan Kenaikan Gaji
            $table->decimal('proposed_daily_salary_increase', 15, 2)->default(0);
            $table->decimal('proposed_monthly_salary_increase', 15, 2)->default(0);
            $table->decimal('proposed_attendance_bonus_increase', 15, 2)->default(0);
            $table->decimal('proposed_position_allowance_increase', 15, 2)->default(0);
            $table->decimal('proposed_leader_allowance_increase', 15, 2)->default(0);

            // Gaji Baru
            $table->decimal('new_daily_salary', 15, 2)->default(0);
            $table->decimal('new_monthly_salary', 15, 2)->default(0);
            $table->decimal('new_attendance_bonus', 15, 2)->default(0);
            $table->decimal('new_position_allowance', 15, 2)->default(0);
            $table->decimal('new_leader_allowance', 15, 2)->default(0);

            // Alasan Kenaikan Gaji (JSON array line 1..5)
            $table->json('reasons')->nullable();

            // Status & Approval
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignId('proposed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('area_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('accounting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('hrd_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('director_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_increase_proposals');
        Schema::dropIfExists('probation_evaluations');
    }
};
