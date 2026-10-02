<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_owner_setup_is_only_available_before_users_exist(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/setup-owner', [
            'name' => 'Owner Utama',
            'email' => 'owner@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertCreated()->assertJsonPath('user.role', 'owner');
        $this->assertDatabaseHas('pharmacies', ['slug' => 'apotek-salam-sehat']);
        $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/setup-owner', [
            'name' => 'Owner Kedua',
            'email' => 'owner2@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertConflict();
    }

    public function test_pharmacy_admin_can_only_see_products_from_their_pharmacy(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $admin = User::factory()->create([
            'role' => 'admin_salam_sehat',
            'pharmacy_id' => $salam->id,
        ]);
        $this->createProduct($salam, 'SALAM-01');
        $this->createProduct($badan, 'BADAN-01');

        $this->actingAs($admin)->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.sku', 'SALAM-01');
        $this->getJson('/api/products?pharmacy_id='.$badan->id)->assertNotFound();
        $this->getJson('/api/products?pharmacy_id='.$salam->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_pharmacy_admin_can_read_suppliers_for_purchase_orders_without_managing_them(): void
    {
        [$salam] = $this->createPharmacies();
        $admin = User::factory()->create([
            'role' => 'admin_salam_sehat',
            'pharmacy_id' => $salam->id,
        ]);
        Supplier::factory()->create(['name' => 'Pemasok Obat']);

        $this->actingAs($admin)->getJson('/api/suppliers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->postJson('/api/suppliers', ['name' => 'Pemasok Baru'])
            ->assertForbidden();
    }

    public function test_expiry_alerts_follow_the_same_branch_visibility_as_inventory(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $admin = User::factory()->create([
            'role' => 'admin_salam_sehat',
            'pharmacy_id' => $salam->id,
        ]);
        $owner = User::factory()->create(['role' => 'owner']);
        $salamProduct = $this->createProduct($salam, 'SALAM-EXPIRY');
        $badanProduct = $this->createProduct($badan, 'BADAN-EXPIRY');

        foreach ([[$salamProduct, 'SALAM-BATCH'], [$badanProduct, 'BADAN-BATCH']] as [$product, $batchNumber]) {
            InventoryBatch::create([
                'product_id' => $product->id,
                'batch_number' => $batchNumber,
                'expiry_date' => today()->addDays(12),
                'quantity_received' => 5,
                'quantity_remaining' => 5,
                'purchase_price' => 1000,
            ]);
        }

        $this->actingAs($admin)->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('expiry_alert_count', 1)
            ->assertJsonPath('expiry_alerts.0.pharmacy_id', $salam->id);
        $this->actingAs($owner)->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('expiry_alert_count', 2);
    }

    public function test_owner_and_warehouse_admin_can_see_both_pharmacies(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $this->createProduct($salam, 'SALAM-01');
        $this->createProduct($badan, 'BADAN-01');

        foreach (['owner', 'warehouse_admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->getJson('/api/products')
                ->assertOk()
                ->assertJsonCount(2, 'data.data');
            $this->getJson('/api/dashboard')
                ->assertOk()
                ->assertJsonPath('product_count', 2)
                ->assertJsonCount(2, 'pharmacies');
        }
    }

    public function test_stock_adjustment_is_atomic_and_cannot_make_stock_negative(): void
    {
        [$salam] = $this->createPharmacies();
        $warehouse = User::factory()->create(['role' => 'warehouse_admin']);
        $product = $this->createProduct($salam, 'STOCK-01', 5);

        $this->actingAs($warehouse)->patchJson("/api/products/{$product->id}/stock", [
            'type' => 'outbound',
            'quantity' => 6,
            'reason' => 'Pengeluaran stok',
        ])->assertUnprocessable();

        $this->assertSame(5, $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->patchJson("/api/products/{$product->id}/stock", [
            'type' => 'inbound',
            'quantity' => 3,
            'reason' => 'Penerimaan supplier',
        ])->assertOk()->assertJsonPath('data.current_stock', 8);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'inbound',
            'stock_before' => 5,
            'stock_after' => 8,
        ]);
    }

    public function test_checkout_decrements_stock_and_records_sale_as_one_transaction(): void
    {
        [$salam] = $this->createPharmacies();
        $cashier = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $product = $this->createProduct($salam, 'SALE-01', 6);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 5000,
        ])->assertCreated()
            ->assertJsonPath('data.total', 3000)
            ->assertJsonPath('data.change_due', 2000)
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertSame(4, $product->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'stock_before' => 6,
            'stock_after' => 4,
        ]);
    }

    public function test_checkout_rejects_insufficient_stock_without_partial_writes(): void
    {
        [$salam] = $this->createPharmacies();
        $cashier = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $product = $this->createProduct($salam, 'SALE-LOW', 1);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 5000,
        ])->assertUnprocessable();

        $this->assertSame(1, $product->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_sales_and_reports_never_mix_the_two_pharmacy_scopes(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $salamAdmin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $badanAdmin = User::factory()->create(['role' => 'admin_badan_sehat', 'pharmacy_id' => $badan->id]);
        $salamProduct = $this->createProduct($salam, 'REPORT-SALAM', 5);
        $badanProduct = $this->createProduct($badan, 'REPORT-BADAN', 5);

        $this->actingAs($salamAdmin)->postJson('/api/sales', [
            'items' => [['product_id' => $salamProduct->id, 'quantity' => 1]],
            'amount_paid' => 1500,
        ])->assertCreated();
        $this->actingAs($badanAdmin)->postJson('/api/sales', [
            'items' => [['product_id' => $badanProduct->id, 'quantity' => 1]],
            'amount_paid' => 1500,
        ])->assertCreated();

        $this->actingAs($salamAdmin)->getJson('/api/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.pharmacy_id', $salam->id);
        $this->getJson('/api/reports')
            ->assertOk()
            ->assertJsonPath('summary.transaction_count', 1)
            ->assertJsonPath('summary.sales_total', 1500)
            ->assertJsonPath('summary.items_sold', 1);
        $this->getJson('/api/stock-movements')->assertForbidden();
    }

    public function test_pharmacy_admin_cannot_read_another_pharmacy_user(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $admin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $otherUser = User::factory()->create(['role' => 'admin_badan_sehat', 'pharmacy_id' => $badan->id]);

        $this->actingAs($admin)->getJson("/api/users/{$otherUser->id}")->assertNotFound();
    }

    public function test_pharmacy_admin_cannot_move_a_user_to_another_pharmacy_by_editing_scope_fields(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $admin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $coworker = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $supplier = Supplier::create(['name' => 'Supplier bukan scope akun ini']);

        $this->actingAs($admin)->putJson("/api/users/{$coworker->id}", [
            'name' => 'Admin tetap di Salam',
            'pharmacy_id' => $badan->id,
            'supplier_id' => $supplier->id,
        ])->assertOk()->assertJsonPath('data.pharmacy_id', $salam->id);

        $this->assertSame($salam->id, $coworker->fresh()->pharmacy_id);
    }

    public function test_owner_can_create_pharmacy_user_with_server_assigned_scope(): void
    {
        [$salam, $badan] = $this->createPharmacies();
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)->postJson('/api/users', [
            'name' => 'Admin Badan',
            'email' => 'admin.badan@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'role' => 'admin_badan_sehat',
        ])->assertCreated()
            ->assertJsonPath('data.pharmacy_id', $badan->id);

        $this->assertNotSame($salam->id, $badan->id);
    }

    public function test_pharmacy_admin_cannot_assign_the_cross_pharmacy_warehouse_role(): void
    {
        [$salam] = $this->createPharmacies();
        $admin = User::factory()->create([
            'role' => 'admin_salam_sehat',
            'pharmacy_id' => $salam->id,
        ]);

        $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Gudang tidak sah',
            'email' => 'warehouse@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'role' => 'warehouse_admin',
        ])->assertForbidden();
    }

    public function test_supplier_can_only_see_their_linked_products_and_cannot_change_stock(): void
    {
        [$salam] = $this->createPharmacies();
        $supplier = Supplier::create(['name' => 'Supplier A']);
        $otherSupplier = Supplier::create(['name' => 'Supplier B']);
        $account = User::factory()->create([
            'role' => 'supplier',
            'supplier_id' => $supplier->id,
        ]);
        $product = $this->createProduct($salam, 'SUPPLIER-A', 8);
        $product->update(['supplier_id' => $supplier->id]);
        $this->createProduct($salam, 'SUPPLIER-B')->update(['supplier_id' => $otherSupplier->id]);

        $this->actingAs($account)->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.sku', 'SUPPLIER-A');

        $this->patchJson("/api/products/{$product->id}/stock", [
            'type' => 'inbound',
            'quantity' => 1,
            'reason' => 'Penyesuaian',
        ])->assertForbidden();
    }

    public function test_deactivated_user_cannot_use_protected_routes_but_can_end_session(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $this->actingAs($inactiveUser)->getJson('/api/dashboard')->assertForbidden();
        $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/logout')->assertOk();
    }

    private function createPharmacies(): array
    {
        PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['name' => 'Tunai', 'is_active' => true],
        );
        $salam = Pharmacy::create(['name' => 'Apotek Salam Sehat', 'slug' => 'apotek-salam-sehat']);
        $badan = Pharmacy::create(['name' => 'Apotek Badan Sehat', 'slug' => 'apotek-badan-sehat']);

        return [$salam, $badan];
    }

    private function createProduct(Pharmacy $pharmacy, string $sku, int $stock = 10): Product
    {
        return Product::create([
            'pharmacy_id' => $pharmacy->id,
            'sku' => $sku,
            'name' => "Obat {$sku}",
            'unit' => 'pcs',
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'current_stock' => $stock,
            'minimum_stock' => 2,
        ]);
    }
}
