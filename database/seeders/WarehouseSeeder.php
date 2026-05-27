<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Gudang Jakarta', 'location' => 'Jakarta', 'city' => 'Jakarta Pusat', 'latitude' => -6.2088, 'longitude' => 106.8456],
            ['name' => 'Gudang Surabaya', 'location' => 'Surabaya', 'city' => 'Surabaya', 'latitude' => -7.2575, 'longitude' => 112.7521],
            ['name' => 'Gudang Bandung', 'location' => 'Bandung', 'city' => 'Bandung', 'latitude' => -6.9175, 'longitude' => 107.6191],
            ['name' => 'Gudang Medan', 'location' => 'Medan', 'city' => 'Medan', 'latitude' => 3.5952, 'longitude' => 98.6722],
            ['name' => 'Gudang Makassar', 'location' => 'Makassar', 'city' => 'Makassar', 'latitude' => -5.1476, 'longitude' => 119.4327],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
