<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consented_consultation_returns_structured_ai_guidance(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'triage' => 'Apoteker',
                            'reason' => 'Keluhan ringan, perlu klarifikasi.',
                            'questions' => ['Apakah ada alergi?'],
                            'options' => [[
                                'ingredient' => 'Contoh bahan aktif',
                                'why' => 'Informasi umum untuk dibahas.',
                                'caution' => 'Periksa kontraindikasi dengan apoteker.',
                            ]],
                            'red_flags' => ['Keluhan memburuk.'],
                            'pharmacist_checks' => ['Periksa alergi dan obat lain.'],
                        ]),
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->postJson('/api/consultations/triage', $this->consultationInput())
            ->assertOk()
            ->assertJsonPath('data.triage', 'Apoteker')
            ->assertJsonPath('data.options.0.ingredient', 'Contoh bahan aktif');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com')
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'sakit kepala'));
    }

    public function test_emergency_symptoms_are_escalated_without_calling_ai(): void
    {
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->create(['role' => 'admin_salam_sehat']))
            ->postJson('/api/consultations/triage', $this->consultationInput(['symptoms' => 'sesak napas']))
            ->assertOk()
            ->assertJsonPath('data.triage', 'Darurat');

        Http::assertNothingSent();
    }

    public function test_consultation_requires_consent_and_an_authorized_role(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $input = $this->consultationInput(['consent' => false]);

        $this->actingAs($cashier)->postJson('/api/consultations/triage', $input)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consent');

        $this->actingAs(User::factory()->create(['role' => 'warehouse_admin']))
            ->postJson('/api/consultations/triage', $this->consultationInput())
            ->assertForbidden();
    }

    public function test_missing_gemini_key_is_reported_instead_of_returning_fake_guidance(): void
    {
        config(['services.gemini.key' => null]);
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->postJson('/api/consultations/triage', $this->consultationInput())
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Layanan AI belum dikonfigurasi. Atur GEMINI_API_KEY pada backend terlebih dahulu.');

        Http::assertNothingSent();
    }

    private function consultationInput(array $overrides = []): array
    {
        return array_merge([
            'symptoms' => 'sakit kepala sejak pagi',
            'duration' => '1 hari',
            'severity' => 'Ringan',
            'age_group' => 'Dewasa',
            'allergies' => '',
            'medications' => '',
            'conditions' => '',
            'pregnancy_status' => 'Belum diketahui',
            'emergency_signs' => false,
            'consent' => true,
        ], $overrides);
    }
}
