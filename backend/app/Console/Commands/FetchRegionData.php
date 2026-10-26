<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:fetch-region-data')]
#[Description('Command description')]
class FetchRegionData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching provinces...');
        $response = \Illuminate\Support\Facades\Http::get('https://wilayah.id/api/provinces.json');
        if ($response->successful()) {
            $provinces = $response->json('data');
            foreach ($provinces as $province) {
                \App\Models\Province::updateOrCreate(
                    ['code' => $province['code']],
                    ['name' => $province['name']]
                );
                
                $this->info("Fetching cities for province {$province['name']}...");
                $cityResponse = \Illuminate\Support\Facades\Http::get("https://wilayah.id/api/regencies/{$province['code']}.json");
                if ($cityResponse->successful()) {
                    $cities = $cityResponse->json('data');
                    foreach ($cities as $city) {
                        \App\Models\City::updateOrCreate(
                            ['code' => $city['code']],
                            [
                                'province_code' => $province['code'],
                                'name' => $city['name']
                            ]
                        );
                    }
                }
            }
            $this->info('Successfully fetched all region data!');
        } else {
            $this->error('Failed to fetch provinces.');
        }
    }
}
