<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = Schema::getTableListing();
        $exclude = ['migrations', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs', 'sessions'];

        foreach ($tables as $table) {
            if (strpos($table, '.') !== false) continue;
            if (!in_array($table, $exclude)) {
                if (!Schema::hasColumn($table, 'deleted_at')) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->softDeletes();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        $tables = Schema::getTableListing();
        $exclude = ['migrations', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs', 'sessions'];

        foreach ($tables as $table) {
            if (strpos($table, '.') !== false) continue;
            if (!in_array($table, $exclude)) {
                if (Schema::hasColumn($table, 'deleted_at')) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->dropSoftDeletes();
                    });
                }
            }
        }
    }
};
