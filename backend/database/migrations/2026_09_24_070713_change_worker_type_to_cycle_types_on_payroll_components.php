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
        // First add the new JSON column
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->json('cycle_types')->nullable()->after('show_on_payslip');
        });

        // Migrate data
        $components = DB::table('payroll_components')->get();
        foreach ($components as $component) {
            $cycleTypes = [];
            if (isset($component->worker_type)) {
                if ($component->worker_type === 'monthly') {
                    $cycleTypes = ['monthly'];
                } elseif ($component->worker_type === 'daily') {
                    $cycleTypes = ['daily'];
                } elseif ($component->worker_type === 'both') {
                    $cycleTypes = ['monthly', 'daily'];
                }
            } else {
                $cycleTypes = ['monthly', 'daily'];
            }
            DB::table('payroll_components')
                ->where('id', $component->id)
                ->update(['cycle_types' => json_encode($cycleTypes)]);
        }

        // Drop the old column
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('worker_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->enum('worker_type', ['monthly', 'daily', 'both'])->default('both')->after('cycle_types');
        });

        // Best effort to migrate back
        $components = DB::table('payroll_components')->get();
        foreach ($components as $component) {
            $workerType = 'both';
            if ($component->cycle_types) {
                $types = json_decode($component->cycle_types, true);
                if (is_array($types)) {
                    if (in_array('monthly', $types) && !in_array('daily', $types)) {
                        $workerType = 'monthly';
                    } elseif (in_array('daily', $types) && !in_array('monthly', $types)) {
                        $workerType = 'daily';
                    }
                }
            }
            DB::table('payroll_components')
                ->where('id', $component->id)
                ->update(['worker_type' => $workerType]);
        }

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('cycle_types');
        });
    }
};
