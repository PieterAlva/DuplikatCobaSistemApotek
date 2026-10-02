<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_records_customer_request_and_admin_tracks_fulfillment(): void
    {
        [$salam, $badan] = $this->branches();
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $admin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $customer = Customer::create(['pharmacy_id' => $salam->id, 'name' => 'Dewi', 'phone' => '081234']);

        $requestId = $this->actingAs($cashier)->postJson('/api/medicine-requests', [
            'customer_id' => $customer->id,
            'customer_name' => 'Nama yang dikirim client',
            'customer_phone' => '000',
            'item_name' => 'Obat belum tersedia',
            'generic_name' => 'Paracetamol',
            'quantity' => 2,
            'unit' => 'strip',
            'notes' => 'Pelanggan meminta dihubungi.',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.pharmacy_id', $salam->id)
            ->assertJsonPath('data.customer_name', 'Dewi')
            ->assertJsonPath('data.customer_phone', '081234')
            ->json('data.id');

        $this->patchJson("/api/medicine-requests/{$requestId}", ['status' => 'sourcing'])->assertForbidden();
        $this->actingAs($admin)->patchJson("/api/medicine-requests/{$requestId}", ['status' => 'sourcing'])
            ->assertOk()->assertJsonPath('data.status', 'sourcing');
        $this->patchJson("/api/medicine-requests/{$requestId}", ['status' => 'ready'])
            ->assertOk()->assertJsonPath('data.status', 'ready');
        $this->patchJson("/api/medicine-requests/{$requestId}", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $this->patchJson("/api/medicine-requests/{$requestId}", ['status' => 'sourcing'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('medicine_requests', [
            'id' => $requestId,
            'pharmacy_id' => $salam->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('medicine_requests', ['pharmacy_id' => $badan->id]);
    }

    public function test_requests_are_branch_scoped_searchable_and_paginated_by_ten(): void
    {
        [$salam, $badan] = $this->branches();
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $admin = User::factory()->create(['role' => 'admin_salam_sehat', 'pharmacy_id' => $salam->id]);
        $otherAdmin = User::factory()->create(['role' => 'admin_badan_sehat', 'pharmacy_id' => $badan->id]);

        foreach (range(1, 11) as $number) {
            $this->actingAs($cashier)->postJson('/api/medicine-requests', [
                'customer_name' => 'Pelanggan '.$number,
                'item_name' => $number === 11 ? 'Obat Khusus' : 'Obat Umum',
                'quantity' => 1,
                'unit' => 'tablet',
            ])->assertCreated();
        }

        $this->actingAs($otherAdmin)->postJson('/api/medicine-requests', [
            'customer_name' => 'Pelanggan cabang lain',
            'item_name' => 'Obat Umum',
            'quantity' => 1,
            'unit' => 'tablet',
        ])->assertCreated();

        $this->actingAs($admin)->getJson('/api/medicine-requests?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data.data')
            ->assertJsonPath('data.total', 11)
            ->assertJsonPath('data.last_page', 2);
        $this->getJson('/api/medicine-requests?search=Khusus')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.item_name', 'Obat Khusus');
        $this->getJson('/api/medicine-requests')->assertJsonMissing(['customer_name' => 'Pelanggan cabang lain']);
    }

    public function test_customer_link_must_belong_to_cashiers_pharmacy(): void
    {
        [$salam, $badan] = $this->branches();
        $cashier = User::factory()->create(['role' => 'cashier', 'pharmacy_id' => $salam->id]);
        $customer = Customer::create(['pharmacy_id' => $badan->id, 'name' => 'Pelanggan Badan']);

        $this->actingAs($cashier)->postJson('/api/medicine-requests', [
            'customer_id' => $customer->id,
            'customer_name' => 'Pelanggan Badan',
            'item_name' => 'Obat',
            'quantity' => 1,
            'unit' => 'tablet',
        ])->assertNotFound();
        $this->assertDatabaseCount('medicine_requests', 0);
    }

    private function branches(): array
    {
        return [
            Pharmacy::create(['name' => 'Salam', 'slug' => 'apotek-salam-sehat']),
            Pharmacy::create(['name' => 'Badan', 'slug' => 'apotek-badan-sehat']),
        ];
    }
}
