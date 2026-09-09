<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\PeriodTester;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PeriodController extends Controller
{
    /**
     * Nama-nama bulan dalam bahasa Indonesia.
     */
    private array $indonesianMonths = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    /**
     * Menampilkan daftar periode pengecekan (Screenshot 1).
     */
    public function index()
    {
        $periods = CheckingPeriod::with([
            'creator:id,name',
            'periodTesters.tester:id,name,bidang,email',
            'periodServices:id,period_id,service_id,tester_id,status,checked_at'
        ])
        ->orderBy('date', 'desc')
        ->get()
        ->map(function ($period) {
            $carbonDate = Carbon::parse($period->date);
            $monthNum = (int) $carbonDate->format('n');
            $year = $carbonDate->format('Y');
            $monthName = $this->indonesianMonths[$monthNum] ?? $carbonDate->format('F');

            $totalServices = $period->periodServices->count();
            $checkedServices = $period->periodServices->filter(function ($ps) {
                return $ps->status !== 'pending' && !empty($ps->checked_at);
            })->count();

            // Progress percentage
            $progressPercent = $totalServices > 0 ? round(($checkedServices / $totalServices) * 100) : 0;

            // Map testers with initials and color
            $testers = $period->periodTesters->map(function ($pt) {
                $tester = $pt->tester;
                if (!$tester) return null;

                $parts = explode(' ', trim($tester->name));
                $initials = count($parts) >= 2
                    ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                    : strtoupper(substr($parts[0] ?? 'T', 0, 2));

                return [
                    'id' => $tester->id,
                    'name' => $tester->name,
                    'bidang' => $tester->bidang,
                    'initials' => $initials,
                ];
            })->filter()->values();

            return [
                'id' => $period->id,
                'date' => $period->date,
                'period_title' => "{$monthName} {$year}",
                'created_at_label' => "Dibuat " . Carbon::parse($period->created_at)->format('Y-m-d'),
                'status' => $period->status,
                'testers' => $testers,
                'checked_services' => $checkedServices,
                'total_services' => $totalServices,
                'progress_percent' => $progressPercent,
            ];
        });

        return Inertia::render('Admin/Periods/Index', [
            'periods' => $periods,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ]
        ]);
    }

    /**
     * Halaman form pembuatan periode baru (Screenshot 2 & 3).
     */
    public function create()
    {
        // Ambil tester yang berstatus aktif
        $testers = User::where('role', 'tester')
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nip', 'bidang'])
            ->map(function ($tester) {
                $parts = explode(' ', trim($tester->name));
                $initials = count($parts) >= 2
                    ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                    : strtoupper(substr($parts[0] ?? 'T', 0, 2));

                return [
                    'id' => $tester->id,
                    'name' => $tester->name,
                    'email' => $tester->email,
                    'nip' => $tester->nip,
                    'bidang' => $tester->bidang ?? 'Layanan Publik',
                    'initials' => $initials,
                ];
            });

        // Ambil semua layanan aktif
        $services = Service::where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'source_service_id', 'service_name', 'category', 'icon', 'service_url']);

        // Format bulan untuk dropdown
        $monthOptions = [];
        foreach ($this->indonesianMonths as $num => $name) {
            $monthOptions[] = [
                'value' => $num,
                'label' => $name,
            ];
        }

        return Inertia::render('Admin/Periods/Create', [
            'testers' => $testers,
            'services' => $services,
            'monthOptions' => $monthOptions,
            'currentMonth' => (int) date('n'),
            'currentYear' => (int) date('Y'),
        ]);
    }

    /**
     * Menyimpan periode baru dan melakukan distribusi layanan otomatis.
     */
    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2099',
            'tester_ids' => 'required|array|min:1',
            'tester_ids.*' => 'exists:users,id',
        ], [
            'tester_ids.required' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.min' => 'Pilih minimal satu tester untuk periode pengecekan.',
        ]);

        $date = sprintf('%04d-%02d-01', $request->year, $request->month);

        // Cek apakah periode dengan bulan dan tahun yang sama sudah ada
        if (CheckingPeriod::where('date', $date)->exists()) {
            return back()->withErrors([
                'month' => 'Periode untuk ' . ($this->indonesianMonths[$request->month] ?? $request->month) . ' ' . $request->year . ' sudah ada.',
            ]);
        }

        DB::transaction(function () use ($date, $request) {
            // 1. Buat record periode
            $period = CheckingPeriod::create([
                'date' => $date,
                'status' => 'proses',
                'created_by' => $request->user()->id,
            ]);

            // 2. Kaitkan tester terpilih
            $testerIds = array_values(array_unique($request->tester_ids));
            foreach ($testerIds as $testerId) {
                PeriodTester::create([
                    'period_id' => $period->id,
                    'tester_id' => $testerId,
                ]);
            }

            // 3. Ambil seluruh layanan aktif
            $services = Service::where('is_active', true)->orderBy('id')->get();
            $testerCount = count($testerIds);

            // 4. Distribusi Layanan Otomatis secara merata (Round-Robin)
            foreach ($services as $index => $service) {
                $assignedTesterId = $testerIds[$index % $testerCount];
                PeriodService::create([
                    'period_id' => $period->id,
                    'service_id' => $service->id,
                    'tester_id' => $assignedTesterId,
                    'status' => 'pending',
                    'description' => null,
                    'checked_at' => null,
                ]);
            }
        });

        return redirect()->route('admin.periods.index')->with('success', 'Periode pengecekan berhasil dibuat dan layanan didistribusikan.');
    }

    /**
     * Menampilkan detail periode (Show / Detail).
     */
    public function show(CheckingPeriod $period)
    {
        $period->load([
            'creator:id,name,email',
            'periodTesters.tester:id,name,email,bidang,nip',
            'periodServices.service:id,source_service_id,service_name,category,icon,service_url',
            'periodServices.tester:id,name,email,bidang',
        ]);

        $carbonDate = Carbon::parse($period->date);
        $monthNum = (int) $carbonDate->format('n');
        $monthName = $this->indonesianMonths[$monthNum] ?? $carbonDate->format('F');
        $year = $carbonDate->format('Y');

        $totalServices = $period->periodServices->count();
        $checkedServices = $period->periodServices->filter(fn($ps) => $ps->status !== 'pending' && !empty($ps->checked_at))->count();
        $pendingServices = $totalServices - $checkedServices;

        // Group services by tester
        $distribution = [];
        foreach ($period->periodTesters as $pt) {
            $tester = $pt->tester;
            if (!$tester) continue;

            $parts = explode(' ', trim($tester->name));
            $initials = count($parts) >= 2
                ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                : strtoupper(substr($parts[0] ?? 'T', 0, 2));

            $assignedServices = $period->periodServices->where('tester_id', $tester->id)->map(function ($ps) {
                return [
                    'id' => $ps->id,
                    'service_id' => $ps->service_id,
                    'service_name' => $ps->service?->service_name ?? 'Layanan',
                    'category' => $ps->service?->category ?? 'Umum',
                    'icon' => $ps->service?->icon ?? 'globe',
                    'service_url' => $ps->service?->service_url,
                    'status' => $ps->status,
                    'description' => $ps->description,
                    'checked_at' => $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d M Y H:i') : null,
                ];
            })->values();

            $distribution[] = [
                'tester_id' => $tester->id,
                'name' => $tester->name,
                'bidang' => $tester->bidang ?? 'Layanan Publik',
                'initials' => $initials,
                'total_services' => $assignedServices->count(),
                'checked_services' => $assignedServices->filter(fn($s) => $s['status'] !== 'pending')->count(),
                'services' => $assignedServices,
            ];
        }

        return Inertia::render('Admin/Periods/Show', [
            'period' => [
                'id' => $period->id,
                'title' => "{$monthName} {$year}",
                'date' => $period->date,
                'status' => $period->status,
                'created_at_label' => Carbon::parse($period->created_at)->format('d F Y H:i'),
                'creator_name' => $period->creator?->name ?? 'Admin',
                'total_services' => $totalServices,
                'checked_services' => $checkedServices,
                'pending_services' => $pendingServices,
                'progress_percent' => $totalServices > 0 ? round(($checkedServices / $totalServices) * 100) : 0,
            ],
            'distribution' => $distribution,
        ]);
    }

    /**
     * Memperbarui status periode (Edit).
     */
    public function update(Request $request, CheckingPeriod $period)
    {
        $request->validate([
            'status' => 'required|in:proses,selesai',
        ]);

        $period->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Status periode berhasil diperbarui.');
    }

    /**
     * Menghapus periode pengecekan (Hapus).
     */
    public function destroy(CheckingPeriod $period)
    {
        $period->delete();

        return redirect()->route('admin.periods.index')->with('success', 'Periode pengecekan berhasil dihapus.');
    }
}
