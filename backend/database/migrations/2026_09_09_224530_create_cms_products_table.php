<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('categoryLabel')->nullable();
            $table->string('badge')->nullable();
            $table->text('highlight')->nullable();
            $table->string('specLabel')->nullable();
            $table->string('specValue')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->json('specs')->nullable();
            $table->json('compatibility')->nullable();
            $table->json('marketplaceLinks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_products');
    }
};

