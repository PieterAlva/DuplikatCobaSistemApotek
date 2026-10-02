<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class FoundationDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Obat bebas', 'slug' => 'obat-bebas'],
            ['name' => 'Obat bebas terbatas', 'slug' => 'obat-bebas-terbatas'],
            ['name' => 'Obat keras', 'slug' => 'obat-keras'],
            ['name' => 'Vitamin dan suplemen', 'slug' => 'vitamin-dan-suplemen'],
            ['name' => 'Alat kesehatan', 'slug' => 'alat-kesehatan'],
        ] as $category) {
            ProductCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }

        foreach ([
            ['name' => 'Pieces', 'code' => 'pcs'],
            ['name' => 'Tablet', 'code' => 'tablet'],
            ['name' => 'Kapsul', 'code' => 'kapsul'],
            ['name' => 'Botol', 'code' => 'botol'],
            ['name' => 'Strip', 'code' => 'strip'],
            ['name' => 'Box', 'code' => 'box'],
            ['name' => 'Tube', 'code' => 'tube'],
            ['name' => 'Sachet', 'code' => 'sachet'],
        ] as $unit) {
            ProductUnit::firstOrCreate(['code' => $unit['code']], $unit);
        }

        foreach ([
            ['code' => 'cash', 'name' => 'Tunai'],
            ['code' => 'card', 'name' => 'Kartu'],
            ['code' => 'transfer', 'name' => 'Transfer'],
            ['code' => 'qris', 'name' => 'QRIS'],
        ] as $method) {
            PaymentMethod::firstOrCreate(['code' => $method['code']], $method);
        }

        foreach ([
            'apotek-badan-sehat' => 'BS001',
            'apotek-salam-sehat' => 'SS001',
        ] as $slug => $code) {
            Pharmacy::query()
                ->where('slug', $slug)
                ->where(function ($query) {
                    $query->whereNull('branch_code')->orWhere('branch_code', '');
                })
                ->update(['branch_code' => $code]);
        }
    }
}
