<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default NIP setting
        DB::table('system_settings')->insert([
            [
                'key' => 'nip_format',
                'value' => 'EMP-[YYYY][MM]-[ID]',
                'description' => 'Format NIP Karyawan (Variabel: [YYYY], [YY], [MM], [DD], [ID])',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'nip_counter',
                'value' => '1',
                'description' => 'Nomor urut terakhir untuk NIP',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
