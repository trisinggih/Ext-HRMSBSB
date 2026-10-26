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
        // Drop PTKP and old tax settings tables
        Schema::dropIfExists('tax_employee_settings');
        Schema::dropIfExists('ptkp_settings');
        Schema::dropIfExists('tax_brackets');

        // Add rate to tax_rules
        Schema::table('tax_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('tax_rules', 'rate')) {
                $table->decimal('rate', 8, 4)->default(0)->after('code');
            }
        });

        // Create pivot table for tax_rules and payroll_components
        Schema::create('tax_rule_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rule_id')->constrained('tax_rules')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tax_rule_id', 'payroll_component_id'], 'tax_comp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rule_components');

        Schema::table('tax_rules', function (Blueprint $table) {
            $table->dropColumn('rate');
        });

        // Recreate the old tables (simplified for rollback if needed)
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rule_id')->constrained('tax_rules')->cascadeOnDelete();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->decimal('rate', 8, 4);
            $table->timestamps();
        });

        Schema::create('tax_employee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('tax_rule_id')->nullable()->constrained('tax_rules')->nullOnDelete();
            $table->enum('status', ['tk0', 'k0', 'k1', 'k2', 'k3', 'kk0', 'kk1', 'kk2', 'kk3', 'other'])->default('tk0');
            $table->integer('npwp_certificate')->nullable();
            $table->unsignedInteger('dependents')->default(0);
            $table->timestamps();
        });

        Schema::create('ptkp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('status', 10)->unique();
            $table->string('name')->nullable();
            $table->decimal('ptkp_amount', 15, 2)->default(0);
            $table->date('effective_from')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
