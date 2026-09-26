<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Seed the site hierarchy: the company, its buildings and factory,
     * then the branches, warehouses and workshops beneath them. Rows are
     * upserted by code so re-runs stay idempotent.
     */
    public function run(): void
    {
        $sites = collect();

        foreach ($this->hierarchy() as $attributes) {
            $parentCode = $attributes['parent'] ?? null;
            unset($attributes['parent']);

            $attributes['parent_id'] = $parentCode === null
                ? null
                : $sites->get($parentCode)?->id;

            $site = Site::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );

            $sites->put($attributes['code'], $site);
        }
    }

    /**
     * The seeded hierarchy as code => [type, parent code, name, city,
     * description, address, province, postal code, phone, email, active],
     * ordered so every parent is seeded before its children.
     *
     * @return list<array<string, mixed>>
     */
    protected function hierarchy(): array
    {
        $rows = [
            ['CMP-NUSANTARA', 'company', null, 'PT Nusantara Industri', 'Jakarta Selatan', 'Head office of the manufacturing group.', 'Jl. Jenderal Sudirman Kav. 52-53', 'DKI Jakarta', '12190', '021-5150000', 'info@nusantaraindustri.co.id', true],
            ['BLD-JKT-TOWER', 'building', 'CMP-NUSANTARA', 'Jakarta Tower', 'Jakarta Selatan', 'Head office building housing the corporate functions.', 'Jl. Jenderal Sudirman Kav. 52-53', 'DKI Jakarta', '12190', '021-5150000', 'jkt-tower@nusantaraindustri.co.id', true],
            ['BLD-BDG', 'building', 'CMP-NUSANTARA', 'Bandung Office Building', 'Bandung', 'Regional office building.', 'Jl. Asia Afrika No. 133', 'Jawa Barat', '40112', '022-4230000', 'bandung@nusantaraindustri.co.id', true],
            ['FAC-SBY', 'factory', 'CMP-NUSANTARA', 'Surabaya Plant', 'Surabaya', 'Main assembly plant.', 'Jl. Rungkut Industri III No. 60', 'Jawa Timur', '60293', '031-8410000', 'plant.sby@nusantaraindustri.co.id', true],
            ['BR-JKT', 'branch', 'BLD-JKT-TOWER', 'Jakarta Branch', 'Jakarta Selatan', 'Sales and service branch.', 'Jl. Jenderal Sudirman Kav. 52-53 Lt. 5', 'DKI Jakarta', '12190', '021-5150100', 'branch.jkt@nusantaraindustri.co.id', true],
            ['BR-BDG', 'branch', 'BLD-BDG', 'Bandung Branch', 'Bandung', 'Regional sales and service branch.', 'Jl. Asia Afrika No. 133 Lt. 3', 'Jawa Barat', '40112', '022-4230000', 'branch.bdg@nusantaraindustri.co.id', true],
            ['BR-MKS', 'branch', 'CMP-NUSANTARA', 'Makassar Branch', 'Makassar', 'Eastern Indonesia branch.', 'Jl. A. P. Pettarani No. 18', 'Sulawesi Selatan', '90222', '0411-885000', 'branch.mks@nusantaraindustri.co.id', true],
            ['BR-SBY', 'branch', 'FAC-SBY', 'Surabaya Branch', 'Surabaya', 'Branch co-located with the plant.', 'Jl. Rungkut Industri III No. 60', 'Jawa Timur', '60293', '031-8410000', 'branch.sby@nusantaraindustri.co.id', true],
            ['BR-YOG', 'branch', 'CMP-NUSANTARA', 'Yogyakarta Branch', 'Yogyakarta', 'Closed branch kept for reporting history.', 'Jl. P. Mangkubumi No. 42', 'DI Yogyakarta', '55233', '0274-512000', null, false],
            ['WH-CKR', 'warehouse', 'CMP-NUSANTARA', 'Cikarang Warehouse', 'Cikarang', 'Distribution centre for raw materials and finished goods.', 'Kawasan Industri Jababeka Blok C', 'Jawa Barat', '17530', '021-8934000', 'wh.ckr@nusantaraindustri.co.id', true],
            ['WH-SBY', 'warehouse', 'FAC-SBY', 'Surabaya Warehouse', 'Surabaya', 'Plant warehouse serving the eastern region.', 'Jl. Rungkut Industri IV No. 12', 'Jawa Timur', '60293', '031-8410300', 'wh.sby@nusantaraindustri.co.id', true],
            ['WS-SBY', 'workshop', 'FAC-SBY', 'Surabaya Workshop', 'Surabaya', 'Maintenance and finishing workshop.', 'Jl. Rungkut Industri III Blok B', 'Jawa Timur', '60293', '031-8410200', 'ws.sby@nusantaraindustri.co.id', true],
            ['WS-CKR', 'workshop', 'BR-JKT', 'Cikarang Workshop', 'Cikarang', 'Field service workshop.', 'Kawasan Industri Jababeka Blok D', 'Jawa Barat', '17530', '021-8935000', 'ws.ckr@nusantaraindustri.co.id', true],
        ];

        return array_map(fn (array $row): array => [
            'code' => $row[0],
            'type' => $row[1],
            'parent' => $row[2],
            'name' => $row[3],
            'city' => $row[4],
            'description' => $row[5],
            'address' => $row[6],
            'province' => $row[7],
            'postal_code' => $row[8],
            'phone' => $row[9],
            'email' => $row[10],
            'notes' => null,
            'is_active' => $row[11],
        ], $rows);
    }
}
