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
        Schema::table('employee_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_loans', 'loan_request_id')) {
                $table->foreignId('loan_request_id')->nullable()->after('employee_id')->constrained('loan_requests')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropForeign(['loan_request_id']);
            $table->dropColumn('loan_request_id');
        });
    }
};
