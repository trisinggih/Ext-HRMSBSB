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
        Schema::create('company_numberings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('type'); // e.g. employee, asset, department, branch
            $table->string('format'); // e.g. EMP-[YYYY]-[ID]
            $table->integer('counter')->default(1);
            $table->timestamps();
            
            $table->unique(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_numberings');
    }
};
