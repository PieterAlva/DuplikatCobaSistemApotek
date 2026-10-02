<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salam = Pharmacy::updateOrCreate(
            ['slug' => 'apotek-salam-sehat'],
            ['name' => 'Apotek Salam Sehat', 'is_active' => true],
        );
        $badan = Pharmacy::updateOrCreate(
            ['slug' => 'apotek-badan-sehat'],
            ['name' => 'Apotek Badan Sehat', 'is_active' => true],
        );
        $supplier = Supplier::firstOrCreate(
            ['name' => 'Supplier Demo'],
            ['company_name' => 'Mitra Distribusi', 'email' => 'supplier@example.test', 'is_active' => true],
        );

        foreach ([$salam, $badan] as $pharmacy) {
            $product = Product::firstOrCreate(
                ['pharmacy_id' => $pharmacy->id, 'sku' => 'OBT-PAR-500'],
                [
                    'supplier_id' => $supplier->id,
                    'name' => 'Paracetamol 500 mg',
                    'generic_name' => 'Paracetamol',
                    'category' => 'Obat bebas',
                    'unit' => 'tablet',
                    'purchase_price' => 500,
                    'selling_price' => 1000,
                    'current_stock' => 24,
                    'minimum_stock' => 5,
                ],
            );

            if ($product->current_stock > 0) {
                StockMovement::firstOrCreate(
                    ['product_id' => $product->id, 'reason' => 'Stok awal data demo'],
                    [
                        'type' => 'inbound',
                        'quantity' => $product->current_stock,
                        'stock_before' => 0,
                        'stock_after' => $product->current_stock,
                    ],
                );
            }
        }
    }
}
