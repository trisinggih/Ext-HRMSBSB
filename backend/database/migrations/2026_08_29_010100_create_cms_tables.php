<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MODUL CMS — Company Profile.
     */
    public function up(): void
    {
        // cms_settings — pengaturan umum
        Schema::create('cms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // cms_pages — halaman statis
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // cms_sections — seksi konten
        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('cms_pages')->cascadeOnDelete();
            $table->string('key');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['key', 'is_active', 'sort_order']);
        });

        // cms_media — galeri/upload
        Schema::create('cms_media', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_sections');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('cms_settings');
        Schema::dropIfExists('cms_media');
    }
};
