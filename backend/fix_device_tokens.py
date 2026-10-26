import os

path = 'd:/Project/HRMS/backend/database/migrations/2026_08_28_130705_create_device_tokens_table.php'

content = '''<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint \) {
            \->id();
            \->foreignId('user_id')->constrained()->onDelete('cascade');
            \->string('token', 255)->index();
            \->string('device_type')->nullable();
            \->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
'''

with open(path, 'w') as f:
    f.write(content)
