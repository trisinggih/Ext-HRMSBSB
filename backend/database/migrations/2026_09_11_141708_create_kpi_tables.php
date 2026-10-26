<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =============================================
        // KPI Skala Penilaian (dinamis)
        // =============================================
        Schema::create('kpi_rating_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // mis: "Skala 1-5", "Skala 1-10"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_rating_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_scale_id')->constrained('kpi_rating_scales')->cascadeOnDelete();
            $table->string('label'); // mis: "Sangat Baik", "Baik", "Cukup", "Kurang", "Sangat Kurang"
            $table->decimal('score', 8, 2); // nilai numerik mis: 5.0, 4.0, 3.0...
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // =============================================
        // KPI Indikator Master
        // =============================================
        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // mis: "Kinerja", "Perilaku", "Kompetensi"
            $table->enum('type', ['individual', 'division', 'both'])->default('both');
            $table->string('measurement_unit')->nullable(); // mis: "%", "jumlah", "waktu"
            $table->foreignId('rating_scale_id')->nullable()->constrained('kpi_rating_scales')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // =============================================
        // KPI Template (per Jabatan atau per Divisi)
        // =============================================
        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('scope', ['position', 'division'])->default('position');
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('rating_scale_id')->nullable()->constrained('kpi_rating_scales')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_template_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('kpi_templates')->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->decimal('weight', 8, 2)->default(1); // bobot bebas, akan dihitung proporsional
            $table->text('target')->nullable(); // target pencapaian
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // =============================================
        // KPI Periode Penilaian
        // =============================================
        Schema::create('kpi_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // mis: "Q1 2026", "Januari 2026"
            $table->enum('type', ['monthly', 'quarterly', 'semesterly', 'annual'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('review_start_date')->nullable(); // kapan penilaian bisa dimulai
            $table->date('review_end_date')->nullable();   // batas akhir penilaian
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // =============================================
        // KPI Penilaian (Assessment)
        // =============================================
        Schema::create('kpi_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('kpi_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('kpi_templates')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('employees')->nullOnDelete(); // supervisor/atasan
            $table->enum('status', ['pending', 'in_review', 'completed', 'approved'])->default('pending');
            $table->decimal('final_score', 8, 2)->nullable(); // hasil akhir (dihitung otomatis)
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['period_id', 'employee_id']);
        });

        Schema::create('kpi_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('kpi_assessments')->cascadeOnDelete();
            $table->foreignId('template_indicator_id')->constrained('kpi_template_indicators')->cascadeOnDelete();
            $table->foreignId('rating_level_id')->nullable()->constrained('kpi_rating_levels')->nullOnDelete();
            $table->decimal('raw_score', 8, 2)->nullable(); // nilai yang dipilih/diisi penilai
            $table->decimal('weighted_score', 8, 2)->nullable(); // = raw_score * weight_proportion
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'template_indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_assessment_scores');
        Schema::dropIfExists('kpi_assessments');
        Schema::dropIfExists('kpi_periods');
        Schema::dropIfExists('kpi_template_indicators');
        Schema::dropIfExists('kpi_templates');
        Schema::dropIfExists('kpi_indicators');
        Schema::dropIfExists('kpi_rating_levels');
        Schema::dropIfExists('kpi_rating_scales');
    }
};
