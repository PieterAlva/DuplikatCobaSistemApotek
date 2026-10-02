<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mutation_routes_write_concise_audit_events_without_secrets(): void
    {
        [$pharmacy] = $this->branches();
        $owner = User::factory()->create(['role' => 'owner']);
        PaymentMethod::create(['code' => 'cash', 'name' => 'Tunai', 'is_active' => true]);
        $supplier = Supplier::create(['name' => 'Supplier']);

        $productResponse = $this->actingAs($owner)->postJson('/api/products', [
            'pharmacy_id' => $pharmacy->id,
            'sku' => 'AUDIT-001',
            'name' => 'Audited item',
            'unit' => 'pcs',
            'purchase_price' => 100,
            'selling_price' => 500,
            'current_stock' => 5,
            'minimum_stock' => 1,
        ])->assertCreated();
        $product = Product::findOrFail($productResponse->json('data.id'));
        $this->putJson("/api/products/{$product->id}", [
            'pharmacy_id' => $pharmacy->id,
            'sku' => 'AUDIT-002',
            'name' => 'Audited item',
            'unit' => 'pcs',
            'purchase_price' => 100,
            'selling_price' => 500,
            'minimum_stock' => 1,
        ])->assertOk();
        $this->patchJson("/api/products/{$product->id}/stock", [
            'type' => 'inbound',
            'quantity' => 2,
            'reason' => 'Receive for audit',
        ])->assertOk();

        $customer = $this->postJson('/api/customers', [
            'pharmacy_id' => $pharmacy->id,
            'name' => 'Audit customer',
        ])->assertCreated()->json('data.id');
        $this->patchJson("/api/customers/{$customer}", ['notes' => 'Updated'])->assertOk();
        $this->postJson('/api/sales', [
            'pharmacy_id' => $pharmacy->id,
            'customer_id' => $customer,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 500,
        ])->assertCreated();
        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $purchase = $this->postJson('/api/purchases', [
            'pharmacy_id' => $pharmacy->id,
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity_ordered' => 4, 'unit_cost' => 100]],
        ])->assertCreated()->json('data');
        $purchaseId = $purchase['id'];
        $this->postJson("/api/purchases/{$purchaseId}/receive", [
            'items' => [[
                'purchase_item_id' => $purchase['items'][0]['id'],
                'quantity_received' => 2,
            ]],
        ])->assertOk();
        $cancelled = $this->postJson('/api/purchases', [
            'pharmacy_id' => $pharmacy->id,
            'items' => [['product_id' => $product->id, 'quantity_ordered' => 1, 'unit_cost' => 100]],
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/purchases/{$cancelled}/cancel")->assertOk();

        $newUser = $this->postJson('/api/users', [
            'name' => 'Audit user',
            'email' => 'audit-user@example.test',
            'password' => 'NeverLogThisSecret123!',
            'password_confirmation' => 'NeverLogThisSecret123!',
            'role' => 'cashier',
            'pharmacy_id' => $pharmacy->id,
        ])->assertCreated()->json('data.id');
        $this->putJson("/api/users/{$newUser}", ['name' => 'Updated audit user'])->assertOk();
        $this->deleteJson("/api/users/{$newUser}")->assertOk();

        $actions = AuditLog::query()->pluck('action')->all();
        foreach ([
            'sale.created',
            'purchase.created',
            'purchase.received',
            'purchase.cancelled',
            'product.stock_adjusted',
            'product.created',
            'product.updated',
            'product.deactivated',
            'user.created',
            'user.updated',
            'user.deleted',
            'customer.created',
            'customer.updated',
        ] as $action) {
            $this->assertContains($action, $actions);
        }
        $payload = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString('NeverLogThisSecret123!', $payload);
        $this->assertStringNotContainsString('password', strtolower($payload));
    }

    public function test_audit_list_is_paginated_branch_scoped_and_role_authorized(): void
    {
        [$salam, $badan] = $this->branches();
        $salamAdmin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $owner = User::factory()->create(['role' => 'owner']);
        $warehouse = User::factory()->create(['role' => 'warehouse_admin']);
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);

        AuditLog::create([
            'pharmacy_id' => $salam->id,
            'user_id' => $owner->id,
            'action' => 'sale.created',
            'auditable_type' => Product::class,
            'auditable_id' => 1,
            'new_values' => ['id' => 1, 'total' => 250],
        ]);
        AuditLog::create([
            'pharmacy_id' => $badan->id,
            'user_id' => $owner->id,
            'action' => 'product.stock_adjusted',
            'auditable_type' => Product::class,
            'auditable_id' => 2,
            'new_values' => ['id' => 2, 'quantity' => 2],
        ]);

        $this->actingAs($salamAdmin)->getJson('/api/audit-logs?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.pharmacy.id', $salam->id)
            ->assertJsonPath('data.data.0.user.name', $owner->name);
        $this->getJson('/api/audit-logs?pharmacy_id='.$badan->id)->assertNotFound();

        foreach ([$owner, $warehouse] as $crossBranchUser) {
            $this->actingAs($crossBranchUser)->getJson('/api/audit-logs?per_page=1')
                ->assertOk()->assertJsonPath('data.total', 2);
        }
        $this->actingAs($cashier)->getJson('/api/audit-logs')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'supplier']))->getJson('/api/audit-logs')->assertForbidden();
    }

    private function branches(): array
    {
        return [
            Pharmacy::create(['name' => 'Salam', 'slug' => 'apotek-salam-sehat']),
            Pharmacy::create(['name' => 'Badan', 'slug' => 'apotek-badan-sehat']),
        ];
    }
}
