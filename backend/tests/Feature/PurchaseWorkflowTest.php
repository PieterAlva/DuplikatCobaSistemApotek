<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_totals_are_server_computed_and_products_are_branch_validated(): void
    {
        [$salam, $badan] = $this->branches();
        $owner = User::factory()->create(['role' => 'owner']);
        $salamProduct = $this->product($salam, 'SALAM-PO');
        $badanProduct = $this->product($badan, 'BADAN-PO');
        $supplier = Supplier::create(['name' => 'PO Supplier']);

        $this->actingAs($owner)->postJson('/api/purchases', [
            'pharmacy_id' => $salam->id,
            'supplier_id' => $supplier->id,
            'subtotal' => 1,
            'total' => 1,
            'items' => [
                ['product_id' => $salamProduct->id, 'quantity_ordered' => 3, 'unit_cost' => 400],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'ordered')
            ->assertJsonPath('data.subtotal', 1200)
            ->assertJsonPath('data.total', 1200)
            ->assertJsonPath('data.items.0.line_total', 1200)
            ->assertJsonPath('data.items.0.quantity_received', 0);

        $this->postJson('/api/purchases', [
            'pharmacy_id' => $salam->id,
            'items' => [
                ['product_id' => $badanProduct->id, 'quantity_ordered' => 1, 'unit_cost' => 400],
            ],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_receiving_tracks_new_quantities_per_batch_and_replays_are_stock_idempotent(): void
    {
        [$salam] = $this->branches();
        $owner = User::factory()->create(['role' => 'owner']);
        $product = $this->product($salam, 'RECEIVE-01', 4);
        $purchase = $this->createPurchase($salam, $product, 5, 250);
        $item = $purchase->items()->first();

        $this->actingAs($owner)->postJson("/api/purchases/{$purchase->id}/receive", [
            'items' => [[
                'purchase_item_id' => $item->id,
                'quantity_received' => 2,
                'batch_number' => 'LOT-2026-A',
                'expiry_date' => '2027-12-31',
            ]],
        ])->assertOk()->assertJsonPath('data.status', 'partially_received');
        $this->postJson("/api/purchases/{$purchase->id}/receive", [
            'items' => [[
                'purchase_item_id' => $item->id,
                'quantity_received' => 2,
                'batch_number' => 'LOT-2026-A',
                'expiry_date' => '2027-12-31',
            ]],
        ])->assertOk()->assertJsonPath('data.status', 'partially_received');

        $this->assertSame(6, $product->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_batches', 1);
        $this->assertDatabaseHas('inventory_batches', [
            'product_id' => $product->id,
            'batch_number' => 'LOT-2026-A',
            'quantity_received' => 2,
            'quantity_remaining' => 2,
        ]);
        $this->assertSame('2027-12-31', InventoryBatch::first()->expiry_date->toDateString());
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['quantity' => 2, 'stock_before' => 4, 'stock_after' => 6]);

        $this->postJson("/api/purchases/{$purchase->id}/receive", [
            'items' => [[
                'purchase_item_id' => $item->id,
                'quantity_received' => 5,
                'batch_number' => 'LOT-2026-A',
                'expiry_date' => '2027-12-31',
            ]],
        ])->assertOk()->assertJsonPath('data.status', 'received');

        $this->assertSame(9, $product->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_batches', [
            'product_id' => $product->id,
            'batch_number' => 'LOT-2026-A',
            'quantity_received' => 5,
            'quantity_remaining' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', ['quantity' => 3, 'stock_before' => 6, 'stock_after' => 9]);
    }

    public function test_receiving_rejects_over_receipt_foreign_purchase_items_and_cancelled_orders(): void
    {
        [$salam, $badan] = $this->branches();
        $owner = User::factory()->create(['role' => 'owner']);
        $product = $this->product($salam, 'RECEIVE-02');
        $otherProduct = $this->product($badan, 'RECEIVE-03');
        $purchase = $this->createPurchase($salam, $product, 3, 100);
        $foreignPurchase = $this->createPurchase($badan, $otherProduct, 3, 100);
        $purchaseItem = $purchase->items()->first();
        $foreignItem = $foreignPurchase->items()->first();

        $this->actingAs($owner)->postJson("/api/purchases/{$purchase->id}/receive", [
            'items' => [['purchase_item_id' => $purchaseItem->id, 'quantity_received' => 4]],
        ])->assertUnprocessable();
        $this->postJson("/api/purchases/{$purchase->id}/receive", [
            'items' => [['purchase_item_id' => $foreignItem->id, 'quantity_received' => 1]],
        ])->assertUnprocessable();
        $this->assertSame(0, $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->postJson("/api/purchases/{$purchase->id}/cancel")->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->postJson("/api/purchases/{$purchase->id}/receive", [
            'items' => [['purchase_item_id' => $purchaseItem->id, 'quantity_received' => 1]],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('inventory_batches', 0);
    }

    public function test_purchase_endpoints_enforce_role_and_branch_visibility(): void
    {
        [$salam, $badan] = $this->branches();
        $salamPurchase = $this->createPurchase($salam, $this->product($salam, 'SCOPE-01'), 2, 100);
        $badanPurchase = $this->createPurchase($badan, $this->product($badan, 'SCOPE-02'), 2, 100);
        $salamAdmin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);

        $this->actingAs($salamAdmin)->getJson('/api/purchases')->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.pharmacy_id', $salam->id);
        $this->getJson("/api/purchases/{$badanPurchase->id}")->assertNotFound();
        $this->postJson("/api/purchases/{$badanPurchase->id}/receive", [
            'items' => [['purchase_item_id' => $badanPurchase->items()->first()->id, 'quantity_received' => 1]],
        ])->assertNotFound();
        $this->getJson('/api/purchases?pharmacy_id='.$badan->id)->assertNotFound();

        foreach (['cashier', 'supplier'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'pharmacy_id' => $role === 'cashier' ? $salam->id : null,
                'supplier_id' => null,
            ]);
            $this->actingAs($user)->getJson('/api/purchases')->assertForbidden();
            $this->postJson('/api/purchases', [])->assertForbidden();
            $this->getJson("/api/purchases/{$salamPurchase->id}")->assertForbidden();
        }

        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->getJson('/api/purchases')->assertOk()
            ->assertJsonCount(2, 'data.data');

        $warehouse = User::factory()->create(['role' => 'warehouse_admin']);
        $this->actingAs($warehouse)->getJson('/api/purchases')->assertForbidden();
        $this->postJson('/api/purchases', [])->assertForbidden();
        $this->getJson("/api/purchases/{$salamPurchase->id}")->assertForbidden();
    }

    private function branches(): array
    {
        return [
            Pharmacy::create(['name' => 'Salam', 'slug' => 'apotek-salam-sehat']),
            Pharmacy::create(['name' => 'Badan', 'slug' => 'apotek-badan-sehat']),
        ];
    }

    private function product(Pharmacy $pharmacy, string $sku, int $stock = 0): Product
    {
        return Product::create([
            'pharmacy_id' => $pharmacy->id,
            'sku' => $sku,
            'name' => $sku,
            'unit' => 'pcs',
            'purchase_price' => 250,
            'selling_price' => 500,
            'current_stock' => $stock,
        ]);
    }

    private function createPurchase(Pharmacy $pharmacy, Product $product, int $quantity, int $cost): Purchase
    {
        $purchase = Purchase::create([
            'pharmacy_id' => $pharmacy->id,
            'purchase_number' => 'TEST-PO-'.uniqid(),
            'status' => 'ordered',
            'subtotal' => $quantity * $cost,
            'total' => $quantity * $cost,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity_ordered' => $quantity,
            'quantity_received' => 0,
            'unit_cost' => $cost,
            'line_total' => $quantity * $cost,
        ]);

        return $purchase;
    }
}
