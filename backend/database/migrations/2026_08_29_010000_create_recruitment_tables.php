<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MODUL RMS — Recruitment Management System.
     * Tabel baru ditambahkan DI BAWAH table lama (tanpa migrate:fresh). Jangan diubah schema lama.
     */
    public function up(): void
    {
        // 1. recruitment_jobs — lowongan
        Schema::create('recruitment_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->unsignedInteger('vacancy_quota')->default(1);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->decimal('salary_min', 15, 2)->nullable();
            $table->decimal('salary_max', 15, 2)->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->date('application_deadline')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // admins.id
            $table->timestamps();
            $table->index(['status', 'code']);
        });

        // 2. tests — bank test
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_job_id')->nullable()->constrained('recruitment_jobs')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(15);
            $table->unsignedInteger('passing_score')->default(70); // 0-100
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 3. test_questions — soal pilihan ganda
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->text('question');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->enum('correct_answer', ['A', 'B', 'C', 'D']);
            $table->unsignedInteger('score')->default(1);
            $table->timestamps();
            $table->index('test_id');
        });

        // 4. candidates — pelamar
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->string('ktp_number')->nullable();
            $table->string('source')->nullable();
            $table->enum('status', [
                'applied', 'document_review', 'test_invited', 'test_done',
                'interview_invited', 'interview_done', 'shortlisted', 'offered',
                'hired', 'rejected', 'on_hold',
            ])->default('applied');
            $table->date('applied_date')->nullable();
            $table->timestamps();
            $table->index(['status', 'email']);
        });

        // 5. candidate_applications — lamaran per lowongan
        Schema::create('candidate_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('recruitment_job_id')->constrained('recruitment_jobs')->cascadeOnDelete();
            $table->enum('status', [
                'applied', 'document_review', 'test_invited', 'test_done',
                'interview_invited', 'interview_done', 'shortlisted', 'offered',
                'hired', 'rejected', 'on_hold',
            ])->default('applied');
            $table->timestamp('applied_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'recruitment_job_id']);
        });

        // 6. candidate_documents — dokumen pelamar
        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->enum('document_type', ['cv', 'kk', 'ktp', 'foto', 'skck', 'ijazah', 'lainnya']);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['candidate_id', 'document_type']);
        });

        // 7. test_attempts — hasil pengerjaan test
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('candidate_applications')->nullOnDelete();
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['in_progress', 'submitted'])->default('in_progress');
            $table->timestamps();
            $table->index(['test_id', 'candidate_id']);
        });

        // 8. test_answers — jawaban
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('test_questions')->cascadeOnDelete();
            $table->enum('chosen_answer', ['A', 'B', 'C', 'D'])->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['test_attempt_id', 'question_id']);
        });

        // 9. interviews — jadwal wawancara
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('candidate_applications')->cascadeOnDelete();
            $table->enum('mode', ['online', 'offline']);
            $table->text('location')->nullable();
            $table->text('meeting_link')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('interviewer_id')->nullable(); // admins.id
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled'])->default('scheduled');
            $table->text('result_notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->enum('decided', ['hire', 'reject', 'hold'])->nullable();
            $table->timestamps();
            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidate_applications');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('test_questions');
        Schema::dropIfExists('tests');
        Schema::dropIfExists('recruitment_jobs');
    }
};
