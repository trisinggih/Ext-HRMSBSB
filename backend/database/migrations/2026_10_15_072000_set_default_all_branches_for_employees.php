<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('branches') || !Schema::hasTable('employees')) {
            return;
        }

        $allBranchIds = DB::table('branches')
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->values()
            ->all();

        if (!empty($allBranchIds)) {
            $json = json_encode($allBranchIds);

            // Set default allowed_branch_ids to all branches for employees that haven't been restricted
            DB::table('employees')
                ->where(function ($query) {
                    $query->whereNull('allowed_branch_ids')
                          ->orWhere('allowed_branch_ids', '[]')
                          ->orWhere('allowed_branch_ids', '');
                })
                ->update([
                    'allowed_branch_ids' => $json,
                    'attendance_location_type' => DB::raw("CASE WHEN can_clock_in_anywhere = 1 THEN 'anywhere' ELSE 'specific_branches' END"),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
