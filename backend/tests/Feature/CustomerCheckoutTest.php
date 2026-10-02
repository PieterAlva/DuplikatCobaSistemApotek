<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_crud_is_scoped_to_assigned_branch_and_denies_other_roles(): void
    {
        [$salam, $badan] = $this->branches();
        $salamAdmin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $customer = Customer::create([
            'pharmacy_id' => $salam->id,
            'name' => 'Pelanggan Salam',
            'phone' => '0812000001',
        ]);
        $otherCustomer = Customer::create([
            'pharmacy_id' => $badan->id,
            'name' => 'Pelanggan Badan',
        ]);

        $this->actingAs($salamAdmin)->getJson('/api/customers')
            ->assertOk()->assertJsonCount(1, 'data.data');
        $this->getJson("/api/customers/{$otherCustomer->id}")->assertNotFound();
        $this->getJson('/api/customers?pharmacy_id='.$badan->id)->assertNotFound();
        $this->patchJson("/api/customers/{$otherCustomer->id}", ['name' => 'Modifikasi'])->assertNotFound();

        $this->actingAs($cashier)->putJson("/api/customers/{$customer->id}", ['phone' => '0812999999'])
            ->assertOk()->assertJsonPath('data.phone', '0812999999');
        $this->postJson('/api/customers', ['name' => 'Walk-in'])->assertCreated()
            ->assertJsonPath('data.pharmacy_id', $salam->id);

        $this->actingAs(User::factory()->create(['role' => 'owner']))->getJson('/api/customers')
            ->assertOk()->assertJsonCount(3, 'data.data');
        foreach (['warehouse_admin', 'supplier'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->getJson('/api/customers')->assertForbidden();
            $this->postJson('/api/customers', ['name' => 'No access'])->assertForbidden();
            $this->getJson("/api/customers/{$customer->id}")->assertForbidden();
        }
    }

    public function test_checkout_accepts_customer_payment_method_and_writes_payment_atomically(): void
    {
        [$salam, $badan] = $this->branches();
        $this->paymentMethods();
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $customer = Customer::create(['pharmacy_id' => $salam->id, 'name' => 'Customer']);
        $product = $this->product($salam);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'customer_id' => $customer->id,
            'payment_method' => 'qris',
            'reference' => 'QR-REF-1',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 3000,
        ])->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.payment_method', 'qris');

        $this->assertDatabaseHas('payment_records', [
            'sale_id' => 1,
            'method_name' => 'QRIS',
            'amount' => 3000,
            'reference' => 'QR-REF-1',
        ]);
        $this->assertSame(8, $product->fresh()->current_stock);
        $this->assertSame(1, PaymentRecord::count());

        $crossBranchCustomer = Customer::create(['pharmacy_id' => $badan->id, 'name' => 'Cross Branch']);
        $this->postJson('/api/sales', [
            'customer_id' => $crossBranchCustomer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 1500,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_checkout_payment_validation_preserves_legacy_cash_and_requires_exact_noncash_amount(): void
    {
        [$salam] = $this->branches();
        $this->paymentMethods();
        $admin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $product = $this->product($salam);

        $this->actingAs($admin)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertCreated()->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.change_due', 500);
        $this->assertDatabaseHas('payment_records', ['sale_id' => 1, 'amount' => 1500]);

        $this->postJson('/api/sales', [
            'payment_method' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 2000,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(9, $product->fresh()->current_stock);

        PaymentMethod::where('code', 'transfer')->update(['is_active' => false]);
        $this->postJson('/api/sales', [
            'payment_method' => 'transfer',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 1500,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('payment_records', 1);
    }

    private function branches(): array
    {
        return [
            Pharmacy::create(['name' => 'Salam', 'slug' => 'apotek-salam-sehat']),
            Pharmacy::create(['name' => 'Badan', 'slug' => 'apotek-badan-sehat']),
        ];
    }

    private function paymentMethods(): void
    {
        foreach (['cash' => 'Tunai', 'card' => 'Kartu', 'transfer' => 'Transfer', 'qris' => 'QRIS'] as $code => $name) {
            PaymentMethod::create(['code' => $code, 'name' => $name, 'is_active' => true]);
        }
    }

    private function product(Pharmacy $pharmacy): Product
    {
        return Product::create([
            'pharmacy_id' => $pharmacy->id,
            'sku' => 'SALE-CUSTOMER',
            'name' => 'Sale Product',
            'unit' => 'pcs',
            'purchase_price' => 500,
            'selling_price' => 1500,
            'current_stock' => 10,
        ]);
    }
}
