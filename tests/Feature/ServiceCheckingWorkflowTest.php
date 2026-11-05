<?php

namespace Tests\Feature;

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\PeriodTester;
use App\Models\Service;
use App\Models\ServiceCheckLog;
use App\Models\ServiceIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceCheckingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tester1;
    private User $tester2;
    private CheckingPeriod $period;
    private Service $service;
    private PeriodService $periodService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Admin Utama',
            'email'    => 'admin_workflow@test.com',
            'username' => 'admin_workflow',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
            'status'   => 'aktif',
        ]);

        $this->tester1 = User::create([
            'name'     => 'Tester Ditugaskan',
            'email'    => 'tester1_workflow@test.com',
            'username' => 'tester1_workflow',
            'password' => bcrypt('password123'),
            'role'     => 'tester',
            'status'   => 'aktif',
        ]);

        $this->tester2 = User::create([
            'name'     => 'Tester Lain',
            'email'    => 'tester2_workflow@test.com',
            'username' => 'tester2_workflow',
            'password' => bcrypt('password123'),
            'role'     => 'tester',
            'status'   => 'aktif',
        ]);

        $this->service = Service::create([
            'source_service_id' => 'SRV-WF-01',
            'service_name'      => 'Portal Layanan Publik Jatim',
            'category'          => 'Dinas Komunikasi dan Informatika',
            'level'             => 'Level 1',
            'service_url'       => 'https://majadigi.jatimprov.go.id',
            'status'            => 'aktif',
            'is_active'         => true,
            'synced_at'         => now(),
        ]);

        $this->period = CheckingPeriod::create([
            'date'          => now()->format('Y-m-d'),
            'deadline_date' => now()->addDays(7)->format('Y-m-d'),
            'status'        => 'proses',
            'created_by'    => $this->admin->id,
        ]);

        PeriodTester::create([
            'period_id' => $this->period->id,
            'tester_id' => $this->tester1->id,
        ]);

        $this->periodService = PeriodService::create([
            'period_id'  => $this->period->id,
            'service_id' => $this->service->id,
            'tester_id'  => $this->tester1->id,
            'status'     => 'pending',
        ]);
    }

    public function test_tester_must_provide_mandatory_description_even_for_normal_status(): void
    {
        $response = $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'normal',
                'description' => '',
            ]
        );

        $response->assertSessionHasErrors(['description']);
        $this->assertEquals('pending', $this->periodService->fresh()->status);
    }

    public function test_tester_can_submit_normal_check_with_description(): void
    {
        $response = $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'normal',
                'description' => 'Layanan berjalan normal dan seluruh menu dapat diakses.',
            ]
        );

        $response->assertSessionHasNoErrors();

        $fresh = $this->periodService->fresh();
        $this->assertEquals('normal', $fresh->status);
        $this->assertEquals('Layanan berjalan normal dan seluruh menu dapat diakses.', $fresh->description);
        $this->assertNotNull($fresh->checked_at);

        $this->assertDatabaseHas('service_check_logs', [
            'period_service_id' => $this->periodService->id,
            'tester_id'         => $this->tester1->id,
            'status'            => 'normal',
        ]);
    }

    public function test_tester_cannot_submit_gangguan_without_attachment(): void
    {
        $response = $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Halaman menampilkan error 502 Bad Gateway.',
            ]
        );

        $response->assertSessionHasErrors(['attachment']);
        $this->assertEquals('pending', $this->periodService->fresh()->status);
    }

    private function createFakePng(string $filename = 'bukti_error_500.png'): UploadedFile
    {
        // Minimal valid 1x1 PNG binary (does not require PHP GD extension)
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent($filename, $pngBytes);
    }

    public function test_full_issue_lifecycle_report_admin_followup_and_tester_recheck(): void
    {
        // 1. Tester melaporkan kendala beserta lampiran screenshot
        $fakeImage = $this->createFakePng('bukti_error_500.png');

        $reportResponse = $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Terjadi error 500 saat membuka halaman utama.',
                'attachment'  => $fakeImage,
            ]
        );

        $reportResponse->assertSessionHasNoErrors();

        $ps = PeriodService::find($this->periodService->id);
        $this->assertEquals('gangguan', $ps->status);
        $this->assertNotNull($ps->attachment);
        $this->assertNotEmpty($ps->attachment_data);

        $issue = ServiceIssue::where('period_service_id', $ps->id)->first();
        $this->assertNotNull($issue);
        $this->assertEquals('belum_ditindaklanjuti', $issue->handling_status);
        $this->assertFalse((bool) $issue->has_solved);

        // 2. Admin menindaklanjuti kendala (Tandai Selesai -> Perlu Pengecekan Ulang)
        $followupResponse = $this->actingAs($this->admin)->postJson(
            route('admin.services.issues.toggle_solved', $issue->id),
            [
                'has_solved' => true,
                'admin_note' => 'Server backend telah di-restart oleh tim infrastruktur, silakan cek ulang.',
            ]
        );

        $followupResponse->assertOk()
            ->assertJson([
                'success'         => true,
                'has_solved'      => true,
                'handling_status' => 'perlu_pengecekan_ulang',
            ]);

        $issue->refresh();
        $this->assertEquals('perlu_pengecekan_ulang', $issue->handling_status);
        $this->assertEquals(
            'Server backend telah di-restart oleh tim infrastruktur, silakan cek ulang.',
            $issue->admin_note
        );

        // 3. Tester melakukan Pengecekan Ulang (Recheck) dan menyatakan Normal
        $recheckResponse = $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $ps->id),
            [
                'status'      => 'normal',
                'description' => 'Sudah dicek ulang, halaman utama kembali normal dan responsif.',
            ]
        );

        $recheckResponse->assertSessionHasNoErrors();

        $ps->refresh();
        $issue->refresh();

        $this->assertEquals('normal', $ps->status);
        $this->assertEquals('selesai', $issue->handling_status);
        $this->assertTrue((bool) $issue->has_solved);

        // Pastikan terdapat 2 log pemeriksaan tester di tabel service_check_logs
        $this->assertEquals(
            2,
            ServiceCheckLog::where('period_service_id', $ps->id)->count()
        );

        // Pastikan timeline di halaman showPeriod memiliki 3 tahapan (Initial Check, Admin Handling, Recheck)
        $pageResponse = $this->actingAs($this->tester1)->get(
            route('tester.periods.services', $this->period->id)
        );

        $pageResponse->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tester/Services/Index')
                ->has('services.data', 1)
                ->has('services.data.0.timeline', 3)
            );
    }

    public function test_anti_idor_attachment_download_authorization(): void
    {
        $fakeImage = $this->createFakePng('bukti_idor.png');

        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Kendala pengujian otorisasi lampiran.',
                'attachment'  => $fakeImage,
            ]
        );

        $downloadUrl = route('periods.attachment.download', [
            'periodService' => $this->periodService->id,
            'inline'        => 1,
        ]);

        // Tester yang ditugaskan berhak mengunduh/melihat lampiran (200 OK)
        $this->actingAs($this->tester1)->get($downloadUrl)->assertOk();

        // Admin berhak mengunduh/melihat lampiran (200 OK)
        $this->actingAs($this->admin)->get($downloadUrl)->assertOk();

        // Tester lain yang tidak ditugaskan pada periode ini ditolak (403 Forbidden)
        $this->actingAs($this->tester2)->get($downloadUrl)->assertForbidden();
    }

    public function test_admin_account_cannot_be_modified_or_deactivated_via_tester_management(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('admin.users.update', $this->admin->id),
            [
                'name'   => 'Hacked Admin',
                'email'  => $this->admin->email,
                'status' => 'tidak aktif',
            ]
        );

        $response->assertRedirect(route('admin.users.index'));
        $this->assertEquals('aktif', $this->admin->fresh()->status);
        $this->assertEquals('Admin Utama', $this->admin->fresh()->name);
    }

    public function test_deactivated_tester_is_automatically_logged_out_on_next_request(): void
    {
        // Tester login dan mengakses dashboard
        $this->actingAs($this->tester1)->get(route('dashboard'))->assertOk();

        // Admin menonaktifkan akun tester
        $this->tester1->update(['status' => 'tidak aktif']);

        // Request berikutnya dari tester otomatis di-logout dan dialihkan ke halaman login
        $response = $this->actingAs($this->tester1)->get(route('dashboard'));
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_in_app_notifications_and_mark_read_for_admin_and_tester(): void
    {
        $fakeImage = $this->createFakePng('bukti_notif.png');

        // 1. Tester melaporkan kendala -> Admin menerima notifikasi belum dibaca
        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Kendala untuk pengujian lonceng notifikasi.',
                'attachment'  => $fakeImage,
            ]
        );

        $adminDash = $this->actingAs($this->admin)->get(route('dashboard'));
        $adminDash->assertOk();
        $adminNotifs = $adminDash->viewData('page')['props']['notifications'] ?? null;
        $this->assertNotNull($adminNotifs);
        $this->assertGreaterThanOrEqual(1, $adminNotifs['unread_count']);

        // 2. Admin klik Tandai Dibaca -> has_notif = true & unread_count menjadi 0
        $this->actingAs($this->admin)->post(route('notifications.mark_read'))->assertRedirect();
        $issue = ServiceIssue::where('period_service_id', $this->periodService->id)->firstOrFail();
        $this->assertTrue($issue->has_notif);

        $adminDashAfterRead = $this->actingAs($this->admin)->get(route('dashboard'));
        $this->assertEquals(0, $adminDashAfterRead->viewData('page')['props']['notifications']['unread_count']);

        // 3. Admin menindaklanjuti kendala -> Tester menerima notifikasi "Perlu Cek Ulang"
        $this->actingAs($this->admin)->post(
            route('admin.services.issues.toggle_solved', $issue->id),
            [
                'admin_note' => 'Silakan cek ulang layanan setelah restart server.',
            ]
        );

        $testerDash = $this->actingAs($this->tester1)->get(route('dashboard'));
        $testerDash->assertOk();
        $testerNotifs = $testerDash->viewData('page')['props']['notifications'] ?? null;
        $this->assertNotNull($testerNotifs);
        $this->assertGreaterThanOrEqual(1, $testerNotifs['unread_count']);
        $this->assertEquals('recheck_needed', $testerNotifs['items'][0]['type']);
    }

    public function test_admin_dashboard_provides_period_trends_and_top_troubled_services(): void
    {
        $fakeImage = $this->createFakePng('bukti_trend.png');

        // Laporkan kendala pada periode pertama
        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Kendala periode pertama untuk analitik tren.',
                'attachment'  => $fakeImage,
            ]
        );

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $this->assertArrayHasKey('periodTrends', $props);
        $this->assertArrayHasKey('topTroubledServices', $props);
        $this->assertCount(1, $props['periodTrends']);
        $this->assertEquals(1, $props['periodTrends'][0]['gangguan']);
        $this->assertCount(1, $props['topTroubledServices']);
        $this->assertEquals($this->service->id, $props['topTroubledServices'][0]['id']);
        $this->assertEquals(1, $props['topTroubledServices'][0]['total_issues']);
    }

    public function test_admin_can_bulk_solve_multiple_troubled_services_at_once(): void
    {
        $secondService = Service::create([
            'source_service_id' => 'SRV-WORKFLOW-02',
            'service_name'      => 'Layanan Kesehatan Jatim',
            'category'          => 'Dinas Kesehatan',
            'level'             => 'Level 2',
            'service_url'       => 'https://dinkes.jatimprov.go.id',
            'status'            => 'aktif',
            'is_active'         => true,
            'synced_at'         => now(),
        ]);

        $secondPs = PeriodService::create([
            'period_id'  => $this->period->id,
            'service_id' => $secondService->id,
            'tester_id'  => $this->tester1->id,
            'status'     => 'pending',
        ]);

        // Tester melaporkan kendala pada kedua layanan
        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Gangguan jaringan terpusat pada layanan 1.',
                'attachment'  => $this->createFakePng('bulk1.png'),
            ]
        );
        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $secondPs->id),
            [
                'status'      => 'gangguan',
                'description' => 'Gangguan jaringan terpusat pada layanan 2.',
                'attachment'  => $this->createFakePng('bulk2.png'),
            ]
        );

        // Admin menindaklanjuti kedua layanan sekaligus menggunakan Bulk Action
        $bulkNote = 'Server pusat Pemprov Jatim telah selesai maintenance, silakan cek ulang.';
        $response = $this->actingAs($this->admin)->postJson(
            route('admin.services.issues.bulk_toggle_solved'),
            [
                'period_service_ids' => [$this->periodService->id, $secondPs->id],
                'description'        => $bulkNote,
            ]
        );

        $response->assertOk()->assertJson([
            'success'         => true,
            'updated_count'   => 2,
            'handling_status' => 'perlu_pengecekan_ulang',
            'admin_note'      => $bulkNote,
        ]);

        $this->assertDatabaseHas('service_issues', [
            'period_service_id' => $this->periodService->id,
            'has_solved'        => true,
            'handling_status'   => 'perlu_pengecekan_ulang',
            'admin_note'        => $bulkNote,
        ]);
        $this->assertDatabaseHas('service_issues', [
            'period_service_id' => $secondPs->id,
            'has_solved'        => true,
            'handling_status'   => 'perlu_pengecekan_ulang',
            'admin_note'        => $bulkNote,
        ]);
    }

    public function test_admin_can_export_filtered_excel_and_pdf_reports(): void
    {
        $secondService = Service::create([
            'source_service_id' => 'SRV-WORKFLOW-03',
            'service_name'      => 'Portal Rumah Sakit Umum',
            'category'          => 'Dinas Kesehatan',
            'level'             => 'Level 2',
            'service_url'       => 'https://rsu.jatimprov.go.id',
            'status'            => 'aktif',
            'is_active'         => true,
            'synced_at'         => now(),
        ]);

        $secondPs = PeriodService::create([
            'period_id'  => $this->period->id,
            'service_id' => $secondService->id,
            'tester_id'  => $this->tester1->id,
            'status'     => 'pending',
        ]);

        // Layanan 1 -> Terkendala (Dinas Komunikasi dan Informatika, Level 1)
        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $this->periodService->id),
            [
                'status'      => 'gangguan',
                'description' => 'Kendala pada layanan Diskominfo.',
                'attachment'  => $this->createFakePng('exp1.png'),
            ]
        );

        // Layanan 2 -> Normal (Dinas Kesehatan, Level 2)
        $this->actingAs($this->tester1)->post(
            route('tester.submit_check', $secondPs->id),
            [
                'status'      => 'normal',
                'description' => 'Layanan Dinkes berjalan normal.',
                'attachment'  => $this->createFakePng('exp2.png'),
            ]
        );

        // 1. Uji Export Excel dengan filter category=Dinas Kesehatan
        $excelResponse = $this->actingAs($this->admin)->get(
            route('admin.periods.export.excel', [
                'period'   => $this->period->id,
                'category' => 'Dinas Kesehatan',
            ])
        );
        $excelResponse->assertOk();
        $excelContent = $excelResponse->streamedContent();

        // Jika response berupa XLSX (file ZIP OpenXML), baca sharedStrings untuk verifikasi konten teks
        if (str_starts_with($excelContent, "PK")) {
            $zip = new \ZipArchive();
            $tmp = tempnam(sys_get_temp_dir(), 'xlsx_test');
            file_put_contents($tmp, $excelContent);
            $zip->open($tmp);
            $stringContent = $zip->getFromName('xl/sharedStrings.xml') ?: '';
            $zip->close();
            @unlink($tmp);
            $this->assertStringContainsString('Filter Aktif', $stringContent);
            $this->assertStringContainsString('Portal Rumah Sakit Umum', $stringContent);
            $this->assertStringNotContainsString('Sistem Informasi Pengujian Jatim', $stringContent);
        } else {
            $this->assertStringContainsString('Filter Aktif', $excelContent);
            $this->assertStringContainsString('Portal Rumah Sakit Umum', $excelContent);
            $this->assertStringNotContainsString('Sistem Informasi Pengujian Jatim', $excelContent);
        }

        // 2. Uji Export PDF dengan filter status=gangguan
        $pdfResponse = $this->actingAs($this->admin)->get(
            route('admin.periods.export.pdf', [
                'period' => $this->period->id,
                'status' => 'gangguan',
            ])
        );
        $pdfResponse->assertOk();
        $pdfProps = $pdfResponse->viewData('page')['props'];
        $this->assertEquals(1, $pdfProps['report']['total_services']);
        $this->assertEquals('Terkendala', $pdfProps['report']['active_filters']['Status']);
        $this->assertCount(1, $pdfProps['issue_services']);
        $this->assertCount(0, $pdfProps['running_services']);
    }
}
