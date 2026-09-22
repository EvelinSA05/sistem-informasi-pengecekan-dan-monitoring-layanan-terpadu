<?php

namespace Tests\Feature;

use App\Models\CheckingPeriod;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecuritySqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->tester = User::factory()->create([
            'name' => 'Tester Satu',
            'email' => 'tester1@test.com',
            'role' => 'tester',
            'status' => 'aktif',
        ]);
    }

    /**
     * Test 1: Normal input with apostrophe should be created and updated normally.
     */
    public function test_normal_input_with_apostrophes_is_allowed_and_stored_safely(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'service_name'       => "Dinas Perhubungan & O'Connor Services",
            'category'           => "Layanan Publik",
            'level'              => "Level 1",
            'service_url'        => "https://dishub.go.id",
            'status'             => "aktif",
            'notes'              => "Catatan dengan tanda petik: 'layanan utama' dan 'prioritas'",
            'deskripsi_layanan'  => "Deskripsi layanan perizinan terpadu kota.",
            'tester_instruction' => "Gunakan user test@dishub.go.id",
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'service_name' => "Dinas Perhubungan & O'Connor Services",
            'status'       => 'aktif',
        ]);
    }

    /**
     * Test 2: SQL Injection payloads on Search do not break query or leak data.
     */
    public function test_sql_injection_payloads_on_service_search(): void
    {
        Service::create([
            'source_service_id' => 'SRV-001',
            'service_name' => 'Layanan Kependudukan',
            'category' => 'Dukcapil',
            'level' => 'Level 1',
            'status' => 'aktif',
            'is_active' => true,
        ]);

        $payloads = [
            "' OR '1'='1",
            "\" OR \"1\"=\"1",
            "' UNION SELECT null, null, null, null--",
            "'; DROP TABLE services; --",
            "1' AND (SELECT 1 FROM (SELECT SLEEP(1))a)--",
            "admin' --",
            "' OR 'x'='x",
            "\\'; DROP TABLE services; --",
        ];

        foreach ($payloads as $payload) {
            $response = $this->actingAs($this->admin)->get(route('admin.services.index', [
                'search' => $payload,
            ]));

            $response->assertOk();
            $this->assertDatabaseCount('services', 1);
        }
    }

    /**
     * Test 3: SQL Injection payloads on Service Create and Update are treated strictly as string data.
     */
    public function test_sql_injection_payloads_on_service_create_and_update(): void
    {
        $payload = "Layanan'; DROP TABLE services; -- ' OR '1'='1";

        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'service_name' => $payload,
            'category'     => "Kategori ' OR 1=1--",
            'level'        => "Level 1",
            'service_url'  => "https://example.com",
            'status'       => "aktif",
            'notes'        => "Notes ' UNION SELECT * FROM users--",
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'service_name' => $payload,
        ]);

        $service = Service::where('service_name', $payload)->first();
        $this->assertNotNull($service);

        // Update test with SQL injection payload
        $updatePayload = "Layanan Updated ' OR '2'='2";
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.services.update', $service->id), [
            'service_name' => $updatePayload,
            'category'     => "Web App",
            'level'        => "Level 2",
            'status'       => "aktif",
        ]);

        $updateResponse->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'id'           => $service->id,
            'service_name' => $updatePayload,
        ]);
    }

    /**
     * Test 4: SQL Injection on User / Tester create and update.
     */
    public function test_sql_injection_on_user_create_and_update(): void
    {
        $payloadName = "Tester O'Connor ' OR '1'='1";
        $email = "secure_tester@example.com";

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name'                  => $payloadName,
            'email'                 => $email,
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status'                => 'aktif',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'name'  => $payloadName,
            'email' => $email,
        ]);

        $user = User::where('email', $email)->first();

        // Update user
        $updateName = "Tester New Name ' OR ''='";
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.users.update', $user->id), [
            'name'   => $updateName,
            'email'  => $email,
            'status' => 'aktif',
        ]);

        $updateResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => $updateName,
        ]);
    }

    /**
     * Test 5: SQL Injection on Period Create and Update.
     */
    public function test_sql_injection_on_period_management(): void
    {
        Service::create([
            'source_service_id' => 'SRV-TEST',
            'service_name'      => 'Service Test',
            'status'            => 'aktif',
            'is_active'         => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'date'          => '2026-10-15',
            'deadline_date' => '2026-10-20',
            'tester_ids'    => [$this->tester->id],
        ]);

        $response->assertRedirect(route('admin.periods.index'));
        $this->assertDatabaseHas('checking_periods', [
            'date' => '2026-10-15',
        ]);

        $period = CheckingPeriod::where('date', '2026-10-15')->first();

        // Test malicious input in period update status
        $statusPayload = "selesai' OR '1'='1";
        $statusResponse = $this->actingAs($this->admin)->put(route('admin.periods.update', $period->id), [
            'status' => $statusPayload,
        ]);

        $statusResponse->assertSessionHasErrors(['status']);
    }

    /**
     * Test 6: Authentication cannot be bypassed via SQL Injection.
     */
    public function test_login_cannot_be_bypassed_via_sql_injection(): void
    {
        $payloads = [
            "' OR '1'='1",
            "admin@test.com' --",
            "\" OR \"1\"=\"1",
            "' OR 1=1 #",
            "' UNION SELECT '1', 'admin@test.com', 'hash'--",
        ];

        foreach ($payloads as $payload) {
            $response = $this->post(route('login'), [
                'email'    => $payload,
                'password' => "anything' OR '1'='1",
            ]);

            $this->assertGuest();
        }
    }
}
