<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Seed the product brands.
     */
    public function run(): void
    {
        $brands = [
            'Northwind Trading',
            'Acme Supply Co.',
            'Vertex Industrial',
            'Bluepeak Home',
            'Ironclad Goods',
            'Lumina Electronics',
            'Crestline Office',
            'Solstice Living',
            'Meridian Foods',
            'Oakhaus Furniture',
            'Riverstone Outdoors',
            'Zenith Sports',
            'Copperfield Kitchen',
            'Halcyon Wellness',
            'Pinnacle Tools',
        ];

        foreach ($brands as $name) {
            Brand::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => null, 'is_active' => true],
            );
        }
    }
}
