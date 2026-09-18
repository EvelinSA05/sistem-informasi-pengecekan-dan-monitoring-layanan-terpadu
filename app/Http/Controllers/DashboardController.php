<?php

namespace App\Http\Controllers;

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Nama-nama bulan dalam bahasa Indonesia.
     */
    private array $indonesianMonths = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    private function formatIndonesianDate(Carbon $date): string
    {
        $day   = $date->format('d');
        $month = $this->indonesianMonths[(int) $date->format('n')] ?? $date->format('F');
        $year  = $date->format('Y');
        return "{$day} {$month} {$year}";
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // ─── ADMIN DASHBOARD ──────────────────────────────────────────────
        if ($user->role === 'admin') {

            // --- Tester Stats ---
            $testerTotal    = User::where('role', 'tester')->count();
            $testerAktif    = User::where('role', 'tester')->where('status', 'aktif')->count();
            $testerNonaktif = $testerTotal - $testerAktif;

            // --- Period Stats ---
            $periodTotal   = CheckingPeriod::count();
            $periodProses  = CheckingPeriod::where('status', 'proses')->count();
            $periodSelesai = CheckingPeriod::where('status', 'selesai')->count();

            // --- Service Stats ---
            $serviceTotal    = Service::count();
            $serviceAktif    = Service::where('is_active', true)->count();
            $serviceNonaktif = Service::where('is_active', false)->count();

            // --- Distribusi Kategori Layanan (GROUP BY category) ---
            $categoryRows = Service::selectRaw('category, count(*) as total')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->groupBy('category')
                ->orderByDesc('total')
                ->get(['category', 'total']);

            $categoryDistribution = $categoryRows->map(fn($row) => [
                'category' => $row->category,
                'total'    => (int) $row->total,
            ])->values();

            // --- Distribusi Level Layanan (Level 1–3 selalu tampil) ---
            $levelRows = Service::select('level')
                ->selectRaw('count(*) as total')
                ->whereNotNull('level')
                ->where('level', '!=', '')
                ->groupBy('level')
                ->get();

            $levelMap = [];
            foreach ($levelRows as $row) {
                $lvlKey = trim((string) $row->level);
                $levelMap[$lvlKey] = (int) $row->total;
            }

            $targetLevels = ['Level 1', 'Level 2', 'Level 3'];

            $levelDistribution = collect($targetLevels)->map(function ($lvlName, $index) use ($levelMap) {
                $num = (string) ($index + 1);
                $count = $levelMap[$lvlName]
                    ?? $levelMap[strtolower($lvlName)]
                    ?? $levelMap[$num]
                    ?? 0;

                return [
                    'level' => $lvlName,
                    'total' => (int) $count,
                ];
            })->values();

            // --- Recent Periods dengan Progress (8 terbaru) ---
            $recentPeriods = CheckingPeriod::with([
                'periodTesters:period_id,tester_id',
                'periodServices:id,period_id,status,checked_at',
            ])
            ->orderBy('date', 'desc')
            ->take(8)
            ->get()
            ->map(function ($period) {
                $carbonDate    = Carbon::parse($period->date);
                $totalServices = $period->periodServices->count();
                $checkedServices = $period->periodServices->filter(function ($ps) {
                    return $ps->status !== 'pending' && !empty($ps->checked_at);
                })->count();

                $progressPercent = $totalServices > 0
                    ? round(($checkedServices / $totalServices) * 100)
                    : 0;

                return [
                    'id'              => $period->id,
                    'date_label'      => $this->formatIndonesianDate($carbonDate),
                    'status'          => $period->status,
                    'total_testers'   => $period->periodTesters->count(),
                    'checked'         => $checkedServices,
                    'total_services'  => $totalServices,
                    'progress_percent'=> $progressPercent,
                ];
            })->values();

            return Inertia::render('Dashboard', [
                // Tester
                'testerStats' => [
                    'total'    => $testerTotal,
                    'aktif'    => $testerAktif,
                    'nonaktif' => $testerNonaktif,
                ],
                // Period
                'periodStats' => [
                    'total'   => $periodTotal,
                    'proses'  => $periodProses,
                    'selesai' => $periodSelesai,
                ],
                // Service
                'serviceStats' => [
                    'total'    => $serviceTotal,
                    'aktif'    => $serviceAktif,
                    'nonaktif' => $serviceNonaktif,
                ],
                // Charts
                'categoryDistribution' => $categoryDistribution,
                'levelDistribution'    => $levelDistribution,
                'recentPeriods'        => $recentPeriods,
            ]);
        }

        // ─── TESTER DASHBOARD ─────────────────────────────────────────────
        $activePeriod = CheckingPeriod::where('status', 'proses')->orderBy('date', 'desc')->first();

        $assignedServices = [];
        $stats = [
            'checked'          => 0,
            'pending'          => 0,
            'issues'           => 0,
            'need_confirmation'=> 0,
        ];

        if ($activePeriod) {
            $services = PeriodService::with('service')
                ->where('period_id', $activePeriod->id)
                ->where('tester_id', $user->id)
                ->get();

            $stats['checked'] = $services->where('status', 'normal')->count()
                + $services->where('status', 'gangguan')->count();
            $stats['pending'] = $services->where('status', 'pending')->count();
            $stats['issues']  = $services->where('status', 'gangguan')->count();

            $assignedServices = $services->map(function ($ps) {
                return [
                    'id'               => $ps->id,
                    'service_id'       => $ps->service->id,
                    'name'             => $ps->service->service_name,
                    'category'         => $ps->service->category ?? 'Umum',
                    'url'              => $ps->service->service_url,
                    'status'           => $ps->status,
                    'deskripsi_layanan'=> $ps->service->deskripsi_layanan,
                    'tester_instruction'=> $ps->service->tester_instruction,
                    'last_description' => $ps->description,
                ];
            })->values();
        }

        return Inertia::render('Dashboard', [
            'activePeriod'    => $activePeriod
                ? Carbon::parse($activePeriod->date)->format('F Y')
                : null,
            'assignedServices'=> $assignedServices,
            'stats'           => $stats,
        ]);
    }

    public function submitCheck(Request $request, PeriodService $periodService)
    {
        $request->validate([
            'status'      => 'required|in:normal,gangguan',
            'description' => 'nullable|string',
        ]);

        $periodService->update([
            'status'      => $request->status,
            'description' => $request->description,
            'checked_at'  => now(),
        ]);

        return back()->with('success', 'Hasil pengecekan berhasil disimpan.');
    }
}
