<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_sell_only_products_in_their_assigned_pharmacy(): void
    {
        [$salam, $badan] = $this->createBranches();
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $salamProduct = $this->createProduct($salam, 'SALAM-01');
        $badanProduct = $this->createProduct($badan, 'BADAN-01');

        $this->actingAs($cashier)->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.sku', 'SALAM-01');
        $this->getJson("/api/products/{$badanProduct->id}")->assertNotFound();
        $this->getJson('/api/products?pharmacy_id='.$badan->id)->assertNotFound();

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $badanProduct->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertUnprocessable();
        $this->postJson('/api/sales', [
            'pharmacy_id' => $badan->id,
            'items' => [['product_id' => $salamProduct->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertNotFound();

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $salamProduct->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertCreated()->assertJsonPath('data.pharmacy_id', $salam->id);
        $this->assertSame(9, $salamProduct->fresh()->current_stock);
        $this->assertSame(10, $badanProduct->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_cashier_sales_lists_reports_and_dashboard_are_branch_isolated(): void
    {
        [$salam, $badan] = $this->createBranches();
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $salamAdmin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $badanAdmin = User::factory()->create(['role' => 'admin_badan_sehat', 'pharmacy_id' => $badan->id]);
        $salamProduct = $this->createProduct($salam, 'SALAM-02');
        $badanProduct = $this->createProduct($badan, 'BADAN-02');

        $this->actingAs($salamAdmin)->postJson('/api/sales', [
            'items' => [['product_id' => $salamProduct->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertCreated();
        $otherSale = $this->actingAs($badanAdmin)->postJson('/api/sales', [
            'items' => [['product_id' => $badanProduct->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertCreated()->json('data.id');

        $this->actingAs($cashier)->getJson('/api/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.pharmacy_id', $salam->id);
        $this->getJson("/api/sales/{$otherSale}")->assertNotFound();
        $this->getJson('/api/reports')
            ->assertOk()
            ->assertJsonPath('summary.transaction_count', 1)
            ->assertJsonPath('summary.sales_total', 2000)
            ->assertJsonPath('summary.items_sold', 1);
        $this->getJson('/api/reports?pharmacy_id='.$badan->id)->assertNotFound();
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('product_count', 1)
            ->assertJsonPath('user_count', 2);

        $this->getJson('/api/stock-movements')->assertForbidden();
        $this->getJson('/api/suppliers')->assertForbidden();
    }

    public function test_pharmacy_admin_can_create_a_cashier_bound_to_their_branch(): void
    {
        [$salam, $badan] = $this->createBranches();
        $admin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);

        $this->actingAs($admin)->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['roles' => ['admin_salam_sehat', 'cashier']]);
        $response = $this->postJson('/api/users', [
            'name' => 'Kasir Salam',
            'email' => 'cashier@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'role' => 'cashier',
        ])->assertCreated()->assertJsonPath('data.role', 'cashier')
            ->assertJsonPath('data.pharmacy_id', $salam->id);

        $this->assertDatabaseHas('users', [
            'id' => $response->json('data.id'),
            'pharmacy_id' => $salam->id,
        ]);
        $this->postJson('/api/users', [
            'name' => 'Kasir Cross Branch',
            'email' => 'cashier-other@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'role' => 'cashier',
            'pharmacy_id' => $badan->id,
        ])->assertForbidden();
    }

    private function createBranches(): array
    {
        PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['name' => 'Tunai', 'is_active' => true],
        );

        return [
            Pharmacy::create(['name' => 'Apotek Salam Sehat', 'slug' => 'apotek-salam-sehat']),
            Pharmacy::create(['name' => 'Apotek Badan Sehat', 'slug' => 'apotek-badan-sehat']),
        ];
    }

    private function createProduct(Pharmacy $pharmacy, string $sku): Product
    {
        return Product::create([
            'pharmacy_id' => $pharmacy->id,
            'sku' => $sku,
            'name' => $sku,
            'unit' => 'pcs',
            'selling_price' => 2000,
            'current_stock' => 10,
        ]);
    }
}
