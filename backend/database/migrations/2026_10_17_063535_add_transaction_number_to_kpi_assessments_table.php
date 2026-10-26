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
        Schema::table('kpi_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_assessments', 'transaction_number')) {
                $table->string('transaction_number', 60)->nullable()->unique()->after('id');
            }
        });

        // Backfill existing rows with format KPI-YYYY-MM-PP-XXXX
        $assessments = \Illuminate\Support\Facades\DB::table('kpi_assessments')->get();
        foreach ($assessments as $a) {
            $created = $a->created_at ? new \DateTime($a->created_at) : new \DateTime();
            $year = $created->format('Y');
            $month = $created->format('m');
            $periodId = str_pad((string)($a->period_id ?? 1), 2, '0', STR_PAD_LEFT);
            $seq = str_pad((string)$a->id, 4, '0', STR_PAD_LEFT);
            $tx = "KPI-{$year}-{$month}-{$periodId}-{$seq}";
            
            \Illuminate\Support\Facades\DB::table('kpi_assessments')
                ->where('id', $a->id)
                ->update(['transaction_number' => $tx]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_assessments', 'transaction_number')) {
                $table->dropColumn('transaction_number');
            }
        });
    }
};
