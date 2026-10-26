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
        Schema::create('asset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('requester_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('for_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('for_who')->nullable(); // Kebutuhan untuk siapa (teks/nama/unit jika bukan diri sendiri)
            $table->string('item_name'); // Nama aset/barang
            $table->foreignId('asset_type_id')->nullable()->constrained('asset_types')->nullOnDelete();
            $table->text('purpose'); // Kebutuhan untuk apa / keperluan
            $table->integer('quantity')->default(1);
            $table->decimal('estimated_price', 15, 2)->nullable();
            $table->text('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'purchased'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_requests');
    }
};
