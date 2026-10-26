<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\TaxTerRate;

class TaxTerRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample TER Categories for testing (A, B, C)
        $rates = [
            // Kategori A
            ['category' => 'A', 'min_bruto' => 0, 'max_bruto' => 5400000, 'rate' => 0],
            ['category' => 'A', 'min_bruto' => 5400000, 'max_bruto' => 5650000, 'rate' => 0.25],
            ['category' => 'A', 'min_bruto' => 5650000, 'max_bruto' => 5950000, 'rate' => 0.5],
            ['category' => 'A', 'min_bruto' => 5950000, 'max_bruto' => 6300000, 'rate' => 0.75],
            ['category' => 'A', 'min_bruto' => 6300000, 'max_bruto' => 6750000, 'rate' => 1.0],
            ['category' => 'A', 'min_bruto' => 6750000, 'max_bruto' => 7500000, 'rate' => 1.25],
            ['category' => 'A', 'min_bruto' => 7500000, 'max_bruto' => 8550000, 'rate' => 1.5],
            ['category' => 'A', 'min_bruto' => 8550000, 'max_bruto' => 9650000, 'rate' => 1.75],
            ['category' => 'A', 'min_bruto' => 9650000, 'max_bruto' => 10050000, 'rate' => 2.0],
            ['category' => 'A', 'min_bruto' => 10050000, 'max_bruto' => null, 'rate' => 2.5], // Catch-all for testing
            
            // Kategori B
            ['category' => 'B', 'min_bruto' => 0, 'max_bruto' => 6200000, 'rate' => 0],
            ['category' => 'B', 'min_bruto' => 6200000, 'max_bruto' => 6500000, 'rate' => 0.25],
            ['category' => 'B', 'min_bruto' => 6500000, 'max_bruto' => 6850000, 'rate' => 0.5],
            ['category' => 'B', 'min_bruto' => 6850000, 'max_bruto' => 7300000, 'rate' => 0.75],
            ['category' => 'B', 'min_bruto' => 7300000, 'max_bruto' => 9200000, 'rate' => 1.0],
            ['category' => 'B', 'min_bruto' => 9200000, 'max_bruto' => null, 'rate' => 1.5], // Catch-all for testing
            
            // Kategori C
            ['category' => 'C', 'min_bruto' => 0, 'max_bruto' => 6600000, 'rate' => 0],
            ['category' => 'C', 'min_bruto' => 6600000, 'max_bruto' => 6950000, 'rate' => 0.25],
            ['category' => 'C', 'min_bruto' => 6950000, 'max_bruto' => 7350000, 'rate' => 0.5],
            ['category' => 'C', 'min_bruto' => 7350000, 'max_bruto' => 7800000, 'rate' => 0.75],
            ['category' => 'C', 'min_bruto' => 7800000, 'max_bruto' => 8850000, 'rate' => 1.0],
            ['category' => 'C', 'min_bruto' => 8850000, 'max_bruto' => null, 'rate' => 1.5], // Catch-all for testing
        ];

        foreach ($rates as $rate) {
            TaxTerRate::create(array_merge($rate, ['effective_date' => '2024-01-01']));
        }
    }
}
