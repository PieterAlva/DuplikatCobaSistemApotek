<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PharmacyDataFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_tables_and_additive_compatibility_columns_exist(): void
    {
        foreach ([
            ['pharmacies', 'branch_code'],
            ['products', 'category_id'],
            ['products', 'unit_id'],
            ['products', 'expiry_date'],
            ['stock_movements', 'inventory_batch_id'],
            ['sale_items', 'inventory_batch_id'],
            ['sales', 'customer_id'],
        ] as [$table, $column]) {
            $this->assertTrue(Schema::hasColumn($table, $column), "{$table}.{$column} should exist");
        }

        foreach ([
            'product_categories', 'product_units', 'inventory_batches', 'customers',
            'purchases', 'purchase_items', 'payment_methods', 'payment_records', 'audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} should exist");
        }

        $this->assertTrue(Schema::hasColumn('products', 'category'));
        $this->assertTrue(Schema::hasColumn('products', 'unit'));
        $this->assertTrue(Schema::hasColumn('products', 'barcode'));
    }

    public function test_product_category_unit_and_expiry_batch_relations_work(): void
    {
        $pharmacy = Pharmacy::create([
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'branch_code' => 'TS001',
        ]);
        $category = ProductCategory::create(['name' => 'Obat', 'slug' => 'obat']);
        $unit = ProductUnit::create(['name' => 'Tablet', 'code' => 'tablet']);
        $product = Product::create([
            'pharmacy_id' => $pharmacy->id,
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'category' => 'Obat',
            'category_id' => $category->id,
            'unit' => 'tablet',
            'unit_id' => $unit->id,
            'selling_price' => 1000,
        ]);
        $batch = InventoryBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'LOT-001',
            'expiry_date' => '2027-12-31',
            'quantity_received' => 20,
            'quantity_remaining' => 20,
        ]);

        $this->assertSame($category->id, $product->categoryRecord->id);
        $this->assertSame($unit->id, $product->unitRecord->id);
        $this->assertSame($product->id, $batch->product->id);
        $this->assertSame('2027-12-31', $batch->expiry_date->toDateString());
    }

    public function test_branch_codes_and_product_lot_identifiers_are_unique(): void
    {
        $firstBranch = Pharmacy::create([
            'name' => 'First',
            'slug' => 'first',
            'branch_code' => 'BR001',
        ]);
        $secondBranch = Pharmacy::create(['name' => 'Second', 'slug' => 'second']);
        $product = Product::create([
            'pharmacy_id' => $firstBranch->id,
            'sku' => 'UNIQUE-001',
            'name' => 'Unique Product',
            'selling_price' => 1000,
        ]);

        try {
            $secondBranch->update(['branch_code' => 'BR001']);
            $this->fail('Duplicate branch codes should be rejected.');
        } catch (QueryException) {
            $this->assertNull($secondBranch->fresh()->branch_code);
        }

        InventoryBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'LOT-001',
            'quantity_received' => 1,
        ]);

        $this->expectException(QueryException::class);
        InventoryBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'LOT-001',
            'quantity_received' => 1,
        ]);
    }
}
