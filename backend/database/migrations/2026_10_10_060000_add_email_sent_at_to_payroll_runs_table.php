<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('calculated_at');
            }
            if (!Schema::hasColumn('payroll_runs', 'email_sent_by')) {
                $table->foreignId('email_sent_by')->nullable()->constrained('users')->onDelete('set null')->after('email_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn(['email_sent_at', 'email_sent_by']);
        });
    }
};
