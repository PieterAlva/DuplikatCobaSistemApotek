<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchAwareCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_consumes_unexpired_batches_in_fefo_order_and_links_movements(): void
    {
        [$pharmacy, $cashier, $product] = $this->checkoutFixture(8);
        $expired = $this->batch($product, 'EXPIRED', '2025-01-01', 4);
        $later = $this->batch($product, 'LATER', '2027-01-01', 3);
        $sooner = $this->batch($product, 'SOONER', '2026-10-01', 3);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'amount_paid' => 5000,
        ])->assertCreated()
            ->assertJsonCount(2, 'data.items');

        $items = SaleItem::query()->orderBy('id')->get();
        $this->assertSame([$sooner->id, $later->id], $items->pluck('inventory_batch_id')->all());
        $this->assertSame([3, 2], $items->pluck('quantity')->all());
        $this->assertSame(0, $sooner->fresh()->quantity_remaining);
        $this->assertSame(1, $later->fresh()->quantity_remaining);
        $this->assertSame(4, $expired->fresh()->quantity_remaining);
        $this->assertSame(3, $product->fresh()->current_stock);

        $movements = StockMovement::query()->where('type', 'sale')->orderBy('id')->get();
        $this->assertSame([$sooner->id, $later->id], $movements->pluck('inventory_batch_id')->all());
        $this->assertSame([8, 5], $movements->pluck('stock_before')->all());
        $this->assertSame([5, 3], $movements->pluck('stock_after')->all());
    }

    public function test_checkout_rejects_when_only_aggregate_stock_is_expired_batches(): void
    {
        [$pharmacy, $cashier, $product] = $this->checkoutFixture(4);
        $expired = $this->batch($product, 'EXPIRED-ONLY', '2025-01-01', 4);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 1000,
        ])->assertUnprocessable();

        $this->assertSame(4, $product->fresh()->current_stock);
        $this->assertSame(4, $expired->fresh()->quantity_remaining);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('payment_records', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_checkout_consumes_batches_then_legacy_unbatched_remainder(): void
    {
        [$pharmacy, $cashier, $product] = $this->checkoutFixture(5);
        $tracked = $this->batch($product, 'TRACKED', '2027-05-01', 2);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
            'amount_paid' => 4000,
        ])->assertCreated()->assertJsonCount(2, 'data.items');

        $items = SaleItem::query()->orderBy('id')->get();
        $this->assertSame([$tracked->id, null], $items->pluck('inventory_batch_id')->all());
        $this->assertSame([2, 2], $items->pluck('quantity')->all());
        $this->assertSame(0, $tracked->fresh()->quantity_remaining);
        $this->assertSame(1, $product->fresh()->current_stock);

        $movements = StockMovement::query()->where('type', 'sale')->orderBy('id')->get();
        $this->assertSame([$tracked->id, null], $movements->pluck('inventory_batch_id')->all());
        $this->assertSame([5, 3], $movements->pluck('stock_before')->all());
        $this->assertSame([3, 1], $movements->pluck('stock_after')->all());
    }

    private function checkoutFixture(int $stock): array
    {
        $pharmacy = Pharmacy::create(['name' => 'Salam', 'slug' => 'apotek-salam-sehat']);
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $pharmacy->id]);
        PaymentMethod::create(['code' => 'cash', 'name' => 'Tunai', 'is_active' => true]);
        $product = Product::create([
            'pharmacy_id' => $pharmacy->id,
            'sku' => 'BATCH-'.uniqid(),
            'name' => 'Batch product',
            'unit' => 'pcs',
            'purchase_price' => 100,
            'selling_price' => 1000,
            'current_stock' => $stock,
        ]);

        return [$pharmacy, $cashier, $product];
    }

    private function batch(Product $product, string $number, string $expiry, int $quantity): InventoryBatch
    {
        return InventoryBatch::create([
            'product_id' => $product->id,
            'batch_number' => $number,
            'expiry_date' => $expiry,
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'purchase_price' => 100,
        ]);
    }
}
