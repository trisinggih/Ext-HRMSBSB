<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === TASKS ===
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->enum('status', ['todo', 'in_progress', 'review', 'done', 'cancelled'])->default('todo');
            $table->string('attachment')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_date']);
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('role', ['assignee', 'reviewer'])->default('assignee');
            $table->unique(['task_id', 'employee_id', 'role']);
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('comment');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });

        // === REWARD ===
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['employee_of_month', 'performance', 'bonus', 'appreciation', 'other'])->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('reward_date');
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'reward_date']);
        });

        // === WARNING / SP ===
        Schema::create('employee_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('warning_type', ['sp1', 'sp2', 'sp3', 'warning'])->default('warning');
            $table->date('issue_date');
            $table->text('reason');
            $table->string('evidence')->nullable();
            $table->date('expired_date')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        // === TERMINATION / PHK ===
        Schema::create('employee_terminations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['resignation', 'contract_end', 'retirement', 'termination', 'phk', 'other'])->default('other');
            $table->date('termination_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['proposed', 'approved', 'completed', 'cancelled'])->default('proposed');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('termination_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termination_id')->constrained('employee_terminations')->cascadeOnDelete();
            $table->string('item'); // laptop, id card, asset, dsb
            $table->boolean('is_returned')->default(false);
            $table->string('note')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->index('termination_id');
        });

        // === PAYROLL ===
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['draft', 'processing', 'approved', 'paid', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['earning', 'deduction'])->default('earning');
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])->default('fixed');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('percentage', 8, 4)->nullable();
            $table->text('formula')->nullable(); // expression configurable
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('meal_allowance', 15, 2)->default(0);
            $table->decimal('position_allowance', 15, 2)->default(0);
            $table->decimal('communication_allowance', 15, 2)->default(0);
            $table->enum('pay_frequency', ['monthly', 'biweekly', 'weekly'])->default('monthly');
            $table->foreignId('bank_id')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_overtime', 15, 2)->default(0);
            $table->decimal('total_bonus', 15, 2)->default(0);
            $table->decimal('total_commission', 15, 2)->default(0);
            $table->decimal('total_reward', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('total_bpjs', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->json('breakdown')->nullable(); // detail per komponen
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
            $table->timestamps();
            $table->unique(['payroll_period_id', 'employee_id']);
        });

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->nullable()->constrained('payroll_components')->nullOnDelete();
            $table->string('label');
            $table->enum('type', ['earning', 'deduction', 'tax', 'bpjs']);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
            $table->index('payroll_run_id');
        });

        // === TAX ===
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('period', ['yearly', 'monthly'])->default('yearly');
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rule_id')->constrained('tax_rules')->cascadeOnDelete();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->decimal('rate', 8, 4); // persen
            $table->timestamps();
        });

        Schema::create('tax_employee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('tax_rule_id')->nullable()->constrained('tax_rules')->nullOnDelete();
            $table->enum('status', ['tk0', 'k0', 'k1', 'k2', 'k3', 'kk0', 'kk1', 'kk2', 'kk3', 'other'])->default('tk0'); // PTKP
            $table->integer('npwp_certificate')->nullable();
            $table->unsignedInteger('dependents')->default(0);
            $table->timestamps();
        });

        // === BPJS (settings — rate configurable) ===
        Schema::create('bpjs_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bpjs_type'); // kesehatan, jht, jkk, jkm, jp
            $table->string('name')->nullable();
            $table->decimal('employee_rate', 8, 4)->default(0); // persen
            $table->decimal('company_rate', 8, 4)->default(0);
            $table->decimal('max_limit', 15, 2)->nullable();
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // === APPROVAL ENGINE (configurable) ===
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('module'); // leave, permission, sick_leave, late, shift_swap, workday_swap, dsb
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->string('approver_type'); // supervisor, manager, hr, specific_admin
            $table->foreignId('approver_id')->nullable(); // admin_id / role
            $table->unsignedTinyInteger('order')->default(1);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->index(['workflow_id', 'order']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('employees')->cascadeOnDelete();
            $table->string('module'); // jenis pengajuan
            $table->unsignedBigInteger('module_id'); // id record pengajuan
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['workflow_id', 'status', 'module']);
        });

        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('approval_step_id')->nullable()->constrained('approval_steps')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('approver_name')->nullable();
            $table->enum('action', ['submit', 'approve', 'reject', 'escalate', 'revise']);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('approval_request_id');
        });

        // === NOTIFICATION & ANNOUNCEMENT ===
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->string('type'); // leave, sick_leave, permission, shift_change, shift_swap, late, payroll, task, warning, announcement
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'is_read']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('audience', ['all', 'department', 'branch', 'division'])->default('all');
            $table->unsignedBigInteger('audience_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // === LOG ===
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor'); // admin atau employee
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor');
            $table->string('event'); // created, updated, deleted, approved, dsb
            $table->string('module');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_workflows');
        Schema::dropIfExists('bpjs_settings');
        Schema::dropIfExists('tax_employee_settings');
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_payroll_settings');
        Schema::dropIfExists('payroll_components');
        Schema::dropIfExists('payroll_settings');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('termination_clearances');
        Schema::dropIfExists('employee_terminations');
        Schema::dropIfExists('employee_warnings');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
    }
};
