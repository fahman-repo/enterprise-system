<?php

namespace Database\Seeders;

use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class WorkLocationSeeder extends Seeder
{
    /**
     * Seed the work locations.
     */
    public function run(): void
    {
        $locations = [
            [
                'code' => 'HQ-JKT',
                'name' => 'Head Office Jakarta',
                'address' => 'Jl. Jenderal Sudirman Kav. 52-53',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'postal_code' => '12190',
                'phone' => '021-5150000',
            ],
            [
                'code' => 'BR-BDG',
                'name' => 'Bandung Branch',
                'address' => 'Jl. Asia Afrika No. 133',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40112',
                'phone' => '022-4230000',
            ],
            [
                'code' => 'WH-CKR',
                'name' => 'Cikarang Warehouse',
                'address' => 'Kawasan Industri Jababeka Blok C',
                'city' => 'Cikarang',
                'province' => 'Jawa Barat',
                'postal_code' => '17530',
                'phone' => '021-8934000',
            ],
            [
                'code' => 'PL-SBY',
                'name' => 'Surabaya Plant',
                'address' => 'Jl. Rungkut Industri III No. 60',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'postal_code' => '60293',
                'phone' => '031-8410000',
            ],
        ];

        foreach ($locations as $attributes) {
            WorkLocation::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
