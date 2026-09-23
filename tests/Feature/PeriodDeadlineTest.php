<?php

namespace Tests\Feature;

use App\Models\CheckingPeriod;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PeriodDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin_deadline@test.com',
            'username' => 'admin_deadline',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
            'status'   => 'aktif',
        ]);

        $this->tester = User::create([
            'name'     => 'Tester Test',
            'email'    => 'tester_deadline@test.com',
            'username' => 'tester_deadline',
            'password' => bcrypt('password123'),
            'role'     => 'tester',
            'status'   => 'aktif',
        ]);

        Service::create([
            'source_service_id' => 'SRV-01',
            'service_name'      => 'Layanan Pengujian',
            'category'          => 'Umum',
            'icon'              => 'globe',
            'status'            => 'aktif',
            'is_active'         => true,
        ]);
    }

    public function test_checking_period_deadline_accessor_and_mutator(): void
    {
        $period = CheckingPeriod::create([
            'date' => '2026-09-23',
            'deadline' => '2026-09-30',
            'status' => 'proses',
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals('2026-09-30', $period->deadline_date->format('Y-m-d'));
        $this->assertEquals('2026-09-30', $period->deadline);

        $period->deadline = '2026-10-05';
        $period->save();

        $this->assertEquals('2026-10-05', $period->fresh()->deadline_date->format('Y-m-d'));
    }

    public function test_period_index_returns_indonesian_formatted_created_and_deadline_dates(): void
    {
        $periodWithDeadline = CheckingPeriod::create([
            'date' => '2026-09-23',
            'deadline_date' => '2026-09-30',
            'status' => 'proses',
            'created_by' => $this->admin->id,
        ]);
        $periodWithDeadline->created_at = Carbon::parse('2026-09-21 10:00:00');
        $periodWithDeadline->save();

        $periodWithoutDeadline = CheckingPeriod::create([
            'date' => '2026-09-10',
            'deadline_date' => null,
            'status' => 'proses',
            'created_by' => $this->admin->id,
        ]);
        $periodWithoutDeadline->created_at = Carbon::parse('2026-09-08 09:00:00');
        $periodWithoutDeadline->save();

        $response = $this->actingAs($this->admin)->get(route('admin.periods.index'));

        $response->assertStatus(200);
        $response->assertInertia(function (Assert $page) {
            $page->component('Admin/Periods/Index')
                ->has('periods', 2)
                ->where('periods.0.period_title', '23 September 2026')
                ->where('periods.0.created_date_formatted', '21 September 2026')
                ->where('periods.0.deadline_formatted', '30 September 2026')
                ->where('periods.1.period_title', '10 September 2026')
                ->where('periods.1.created_date_formatted', '08 September 2026')
                ->where('periods.1.deadline_formatted', null);
        });
    }

    public function test_period_store_and_update_with_deadline(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'date'          => '2026-11-01',
            'deadline_date' => '2026-11-10',
            'tester_ids'    => [$this->tester->id],
        ]);

        $response->assertRedirect(route('admin.periods.index'));
        $this->assertDatabaseHas('checking_periods', [
            'date' => '2026-11-01',
            'deadline_date' => '2026-11-10',
        ]);

        $period = CheckingPeriod::where('date', '2026-11-01')->first();

        // Update deadline
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.periods.update', $period->id), [
            'date'          => '2026-11-01',
            'deadline_date' => '2026-11-15',
            'tester_ids'    => [$this->tester->id],
        ]);

        $updateResponse->assertRedirect(route('admin.periods.index'));
        $this->assertDatabaseHas('checking_periods', [
            'id' => $period->id,
            'deadline_date' => '2026-11-15',
        ]);
    }
}
