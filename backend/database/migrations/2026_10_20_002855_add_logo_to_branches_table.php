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
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'logo')) {
                $table->string('logo')->nullable()->after('email');
            }
        });

        // Copy logo from companies to their branches if company had logo
        $companies = DB::table('companies')->whereNotNull('logo')->where('logo', '!=', '')->get();
        foreach ($companies as $comp) {
            DB::table('branches')
                ->where('company_id', $comp->id)
                ->whereNull('logo')
                ->update(['logo' => $comp->logo]);
        }

        // If peby motor logo exists in public/images/peby-motor-logo.png, copy to storage and assign to Peby Motor branches
        $pebyPublic = public_path('images/peby-motor-logo.png');
        if (file_exists($pebyPublic)) {
            $targetRel = 'branch-logos/peby-motor-logo.png';
            $targetAbs = storage_path('app/public/' . $targetRel);
            if (!file_exists(dirname($targetAbs))) {
                @mkdir(dirname($targetAbs), 0755, true);
            }
            if (!file_exists($targetAbs)) {
                @copy($pebyPublic, $targetAbs);
            }
            $pebyCompIds = DB::table('companies')->where('name', 'like', '%Peby Motor%')->pluck('id');
            DB::table('branches')
                ->whereIn('company_id', $pebyCompIds)
                ->whereNull('logo')
                ->update(['logo' => $targetRel]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'logo')) {
                $table->dropColumn('logo');
            }
        });
    }
};
