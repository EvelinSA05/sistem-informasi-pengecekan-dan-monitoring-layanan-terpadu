<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'   => 'Admin Test',
            'email'  => 'admin@test.com',
            'role'   => 'admin',
            'status' => 'aktif',
        ]);

        $this->tester = User::factory()->create([
            'name'   => 'Tester Test',
            'email'  => 'tester@test.com',
            'role'   => 'tester',
            'status' => 'aktif',
        ]);
    }

    /**
     * Test 1: User aktif dapat mengakses halaman terproteksi dan last_activity_time diperbarui.
     */
    public function test_active_user_can_access_protected_routes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $this->assertAuthenticated();
        $this->assertNotEmpty(session('last_activity_time'));
    }

    /**
     * Test 2: Admin yang idle lebih dari 1 jam (3600 detik) otomatis logout dan diarahkan ke login.
     */
    public function test_admin_session_expires_after_1_hour_of_inactivity(): void
    {
        // Login sebagai Admin
        $this->actingAs($this->admin);

        // Simulasikan aktivitas terakhir terjadi 3605 detik yang lalu (> 1 jam)
        session(['last_activity_time' => time() - 3605]);

        // Coba akses route terproteksi Admin
        $response = $this->get(route('admin.services.index'));

        // Harus diarahkan ke login dengan query timeout=1 dan pesan sesi berakhir
        $response->assertRedirect('/?timeout=1');
        $response->assertSessionHas('session_expired', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 1 jam. Silakan login kembali.');

        // User harus sudah logout
        $this->assertGuest();
    }

    /**
     * Test 3: Tester yang idle lebih dari 1 jam otomatis logout dan diarahkan ke login.
     */
    public function test_tester_session_expires_after_1_hour_of_inactivity(): void
    {
        // Login sebagai Tester
        $this->actingAs($this->tester);

        // Simulasikan aktivitas terakhir terjadi 3605 detik yang lalu (> 1 jam)
        session(['last_activity_time' => time() - 3605]);

        // Coba akses route terproteksi Tester
        $response = $this->get(route('tester.services.index'));

        // Harus diarahkan ke login
        $response->assertRedirect('/?timeout=1');
        $response->assertSessionHas('session_expired', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 1 jam. Silakan login kembali.');

        // Tester harus sudah logout
        $this->assertGuest();
    }

    /**
     * Test 4: Aktivitas user mereset timer idle (bukan fixed time sejak login).
     */
    public function test_user_activity_resets_idle_timer(): void
    {
        $this->actingAs($this->admin);

        // 1. Aktivitas pertama di set pada 30 menit yang lalu (1800 detik < 3600 detik)
        session(['last_activity_time' => time() - 1800]);

        // 2. User melakukan request -> masih aktif dan berhasil
        $response1 = $this->get(route('dashboard'));
        $response1->assertOk();
        $this->assertAuthenticated();

        // 3. Waktu aktivitas harus sudah di-refresh ke waktu sekarang
        $updatedTime = session('last_activity_time');
        $this->assertGreaterThanOrEqual(time() - 2, $updatedTime);

        // 4. Setelah 30 menit lagi (total 60 menit sejak awal, tapi hanya 30 menit sejak aktivitas terakhir)
        session(['last_activity_time' => time() - 1800]);
        $response2 = $this->get(route('admin.services.index'));
        $response2->assertOk();
        $this->assertAuthenticated();

        // 5. Jika kemudian diam selama lebih dari 1 jam (3650 detik > 3600 detik)
        session(['last_activity_time' => time() - 3650]);
        $response3 = $this->get(route('dashboard'));
        $response3->assertRedirect('/?timeout=1');
        $this->assertGuest();
    }

    /**
     * Test 5: User dapat login kembali setelah sesi expired dan menggunakan aplikasi secara normal.
     */
    public function test_user_can_login_again_after_session_expired(): void
    {
        // 1. Sesi expired
        $this->actingAs($this->admin);
        session(['last_activity_time' => time() - 3700]);
        $this->get(route('dashboard'));
        $this->assertGuest();

        // 2. Login kembali
        $loginResponse = $this->post(route('login'), [
            'email'    => $this->admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $loginResponse->assertRedirect(route('dashboard', absolute: false));

        // 3. Akses dashboard kembali normal
        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertOk();
    }
}
