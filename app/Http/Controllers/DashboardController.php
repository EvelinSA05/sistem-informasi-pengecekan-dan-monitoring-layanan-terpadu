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
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    private function formatIndonesianDate(Carbon $date): string
    {
        $day = $date->format('d');
        $month = $this->indonesianMonths[(int) $date->format('n')] ?? $date->format('F');
        $year = $date->format('Y');

        return "{$day} {$month} {$year}";
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // ─── ADMIN DASHBOARD ──────────────────────────────────────────────
        if ($user->role === 'admin') {

            // --- Tester Stats ---
            $testerTotal = User::where('role', 'tester')->count();
            $testerAktif = User::where('role', 'tester')
                ->where('status', 'aktif')
                ->count();
            $testerNonaktif = $testerTotal - $testerAktif;

            // --- Period Stats ---
            $periodTotal = CheckingPeriod::count();
            $periodProses = CheckingPeriod::where('status', 'proses')->count();
            $periodSelesai = CheckingPeriod::where('status', 'selesai')->count();

            // --- Service Stats ---
            $serviceTotal         = Service::count();
            $serviceAktif         = Service::where('status', 'aktif')->count();
            $serviceNonaktif      = Service::where('status', 'tidak_aktif')->count();
            $serviceBtuhKonfirmasi = Service::where('status', 'butuh_konfirmasi')->count();

            // --- Distribusi Kategori Layanan ---
            $categoryRows = Service::selectRaw('category, count(*) as total')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->groupBy('category')
                ->orderByDesc('total')
                ->get(['category', 'total']);

            $categoryDistribution = $categoryRows->map(fn ($row) => [
                'category' => $row->category,
                'total' => (int) $row->total,
            ])->values();

            // --- Distribusi Level Layanan ---
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

            $levelDistribution = collect($targetLevels)
                ->map(function ($lvlName, $index) use ($levelMap) {
                    $num = (string) ($index + 1);

                    $count = $levelMap[$lvlName]
                        ?? $levelMap[strtolower($lvlName)]
                        ?? $levelMap[$num]
                        ?? 0;

                    return [
                        'level' => $lvlName,
                        'total' => (int) $count,
                    ];
                })
                ->values();

            // --- Recent Periods dengan Progress ---
            $recentPeriods = CheckingPeriod::with([
                'periodTesters:period_id,tester_id',
                'periodServices:id,period_id,status,checked_at',
            ])
                ->orderBy('date', 'desc')
                ->take(8)
                ->get()
                ->map(function ($period) {
                    $carbonDate = Carbon::parse($period->date);
                    $totalServices = $period->periodServices->count();

                    $checkedServices = $period->periodServices
                        ->filter(function ($ps) {
                            return $ps->status !== 'pending'
                                && !empty($ps->checked_at);
                        })
                        ->count();

                    $progressPercent = $totalServices > 0
                        ? round(($checkedServices / $totalServices) * 100)
                        : 0;

                    return [
                        'id' => $period->id,
                        'date_label' => $this->formatIndonesianDate($carbonDate),
                        'status' => $period->status,
                        'total_testers' => $period->periodTesters->count(),
                        'checked' => $checkedServices,
                        'total_services' => $totalServices,
                        'progress_percent' => $progressPercent,
                    ];
                })
                ->values();

            return Inertia::render('Dashboard', [
                'testerStats' => [
                    'total' => $testerTotal,
                    'aktif' => $testerAktif,
                    'nonaktif' => $testerNonaktif,
                ],

                'periodStats' => [
                    'total' => $periodTotal,
                    'proses' => $periodProses,
                    'selesai' => $periodSelesai,
                ],

                'serviceStats' => [
                    'total'             => $serviceTotal,
                    'aktif'             => $serviceAktif,
                    'nonaktif'          => $serviceNonaktif,
                    'butuhkonfirmasi'   => $serviceBtuhKonfirmasi,
                ],

                'categoryDistribution' => $categoryDistribution,
                'levelDistribution' => $levelDistribution,
                'recentPeriods' => $recentPeriods,
            ]);
        }

        // ─── TESTER DASHBOARD (MONITORING CENTER) ──────────────────────────
        $activePeriod = CheckingPeriod::where('status', 'proses')
            ->whereHas('periodTesters', function ($q) use ($user) {
                $q->where('tester_id', $user->id);
            })
            ->orderBy('date', 'desc')
            ->first();

        $periodInfo = null;

        $stats = [
            'total_assigned' => 0,
            'checked' => 0,
            'pending' => 0,
            'issues' => 0,
            'normal' => 0,
            'need_confirmation' => 0,
            'progress_percent' => 0,
            'remaining_count' => 0,
        ];

        $statusDistribution = [];
        $actionRequired = [];
        $priorityTasks = collect([]);
        $recentActivities = collect([]);

        if ($activePeriod) {
            $periodCarbon = Carbon::parse($activePeriod->date);
            $now = Carbon::now()->startOfDay();
            $targetDate = $periodCarbon->copy()->startOfDay();
            $daysRemaining = (int) $now->diffInDays($targetDate, false);

            $periodInfo = [
                'id' => $activePeriod->id,
                'period_title' => $this->formatIndonesianDate($periodCarbon),
                'month_year' => (
                    $this->indonesianMonths[(int) $periodCarbon->format('n')]
                    ?? $periodCarbon->format('F')
                ) . ' ' . $periodCarbon->format('Y'),
                'date' => $periodCarbon->format('Y-m-d'),
                'status' => $activePeriod->status,
                'days_remaining' => $daysRemaining,
                'is_overdue' => $daysRemaining < 0
                    && $activePeriod->status === 'proses',
            ];

            // Base query for tester assignments in active period
            $baseTesterQuery = PeriodService::where(
                'period_id',
                $activePeriod->id
            )->where(
                'tester_id',
                $user->id
            );

            $totalAssigned = (clone $baseTesterQuery)->count();

            $normalCount = (clone $baseTesterQuery)
                ->where('status', 'normal')
                ->count();

            $issuesCount = (clone $baseTesterQuery)
                ->where('status', 'gangguan')
                ->count();

            $pendingCount = (clone $baseTesterQuery)
                ->where('status', 'pending')
                ->count();

            $needConfirm = (clone $baseTesterQuery)
                ->where('status', 'need_confirmation')
                ->count();

            $checkedCount = $normalCount + $issuesCount;

            $progressPct = $totalAssigned > 0
                ? (int) round(($checkedCount / $totalAssigned) * 100)
                : 0;

            $remainingCount = max(0, $totalAssigned - $checkedCount);

            $stats = [
                'total_assigned' => $totalAssigned,
                'checked' => $checkedCount,
                'pending' => $pendingCount,
                'issues' => $issuesCount,
                'normal' => $normalCount,
                'need_confirmation' => $needConfirm,
                'progress_percent' => $progressPct,
                'remaining_count' => $remainingCount,
            ];

            // Status Distribution for Donut Chart
            $statusDistribution = [
                [
                    'status' => 'Normal',
                    'total' => $normalCount,
                    'color' => '#059669',
                ],
                [
                    'status' => 'Pending',
                    'total' => $pendingCount,
                    'color' => '#3b82f6',
                ],
                [
                    'status' => 'Terkendala',
                    'total' => $issuesCount,
                    'color' => '#dc2626',
                ],
            ];

            if ($needConfirm > 0) {
                $statusDistribution[] = [
                    'status' => 'Need Confirmation',
                    'total' => $needConfirm,
                    'color' => '#d97706',
                ];
            }

            // Real-Time Action Required / Alerts
            if ($pendingCount > 0) {
                $actionRequired[] = [
                    'type' => 'pending',
                    'badge' => 'Perlu Diuji',
                    'count' => $pendingCount,
                    'title' => "{$pendingCount} layanan belum diuji",
                    'description' => 'Layanan masih menunggu verifikasi dan pengecekan aksesibilitas.',
                    'action_label' => 'Uji Sekarang',
                    'action_url' => route('tester.services.index', [
                        'status' => 'pending',
                    ]),
                    'severity' => 'warning',
                ];
            }

            if ($issuesCount > 0) {
                $actionRequired[] = [
                    'type' => 'gangguan',
                    'badge' => 'Terkendala',
                    'count' => $issuesCount,
                    'title' => "{$issuesCount} pengujian memiliki kendala",
                    'description' => 'Terdapat kendala/error yang telah dilaporkan dan memerlukan perhatian.',
                    'action_label' => 'Lihat Kendala',
                    'action_url' => route('tester.services.index', [
                        'status' => 'gangguan',
                    ]),
                    'severity' => 'danger',
                ];
            }

            if ($needConfirm > 0) {
                $actionRequired[] = [
                    'type' => 'confirmation',
                    'badge' => 'Konfirmasi',
                    'count' => $needConfirm,
                    'title' => "{$needConfirm} hasil membutuhkan konfirmasi",
                    'description' => 'Pengujian menunggu konfirmasi lebih lanjut dari admin.',
                    'action_label' => 'Lihat Hasil',
                    'action_url' => route('tester.services.index', [
                        'status' => 'need_confirmation',
                    ]),
                    'severity' => 'info',
                ];
            }

            if (
                $daysRemaining <= 3
                && $daysRemaining >= 0
                && $pendingCount > 0
            ) {
                $actionRequired[] = [
                    'type' => 'deadline',
                    'badge' => 'Batas Waktu',
                    'count' => $daysRemaining,
                    'title' => $daysRemaining === 0
                        ? 'Hari ini batas akhir periode pengujian'
                        : "Tersisa {$daysRemaining} hari sebelum periode berakhir",
                    'description' => "Segera tuntaskan {$pendingCount} penugasan yang belum selesai diuji.",
                    'action_label' => 'Selesaikan Tugas',
                    'action_url' => route('tester.services.index', [
                        'status' => 'pending',
                    ]),
                    'severity' => 'urgent',
                ];
            }

            // 5 Priority Tasks
            $priorityTasks = PeriodService::with([
                'service' => function ($q) {
                    $q->select(
                        'id',
                        'service_name',
                        'category',
                        'level',
                        'icon',
                        'service_url',
                        'deskripsi_layanan',
                        'tester_instruction',
                        'is_active'
                    );
                }
            ])
                ->where('period_id', $activePeriod->id)
                ->where('tester_id', $user->id)
                ->orderByRaw(
                    "CASE
                        WHEN status = 'gangguan' THEN 1
                        WHEN status = 'pending' THEN 2
                        ELSE 3
                    END"
                )
                ->orderBy('id', 'asc')
                ->take(5)
                ->get()
                ->map(function ($ps) {
                    return [
                        'id' => $ps->id,
                        'service_id' => $ps->service?->id,
                        'name' => $ps->service?->service_name ?? '-',
                        'category' => $ps->service?->category ?? 'Umum',
                        'level' => $ps->service?->level ?? '-',
                        'icon' => $ps->service?->icon,
                        'url' => $ps->service?->service_url,
                        'status' => $ps->status,
                        'deskripsi_layanan' => $ps->service?->deskripsi_layanan,
                        'tester_instruction' => $ps->service?->tester_instruction,
                        'last_description' => $ps->description,
                        'checked_at' => $ps->checked_at
                            ? Carbon::parse($ps->checked_at)->format('d M Y, H:i')
                            : null,
                        'checked_at_human' => $ps->checked_at
                            ? Carbon::parse($ps->checked_at)->diffForHumans()
                            : null,
                    ];
                })
                ->values();
        }

        // Recent 5 testing activities for this tester
        $recentActivities = PeriodService::with(
            'service:id,service_name,category,level'
        )
            ->where('tester_id', $user->id)
            ->whereNotNull('checked_at')
            ->orderBy('checked_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($ps) {
                return [
                    'id' => $ps->id,
                    'service_name' => $ps->service?->service_name ?? '-',
                    'category' => $ps->service?->category ?? 'Umum',
                    'level' => $ps->service?->level ?? '-',
                    'status' => $ps->status,
                    'description' => $ps->description,
                    'checked_at' => $ps->checked_at
                        ? Carbon::parse($ps->checked_at)->format('d M Y, H:i')
                        : null,
                    'checked_at_human' => $ps->checked_at
                        ? Carbon::parse($ps->checked_at)->diffForHumans()
                        : null,
                ];
            })
            ->values();

        // 4 Quick Actions for shortcuts
        $quickActions = [
            [
                'title' => 'Mulai Pengujian',
                'subtitle' => 'Kerjakan layanan belum dicek',
                'count' => $stats['pending'],
                'url' => route('tester.services.index', [
                    'status' => 'pending',
                ]),
                'color' => 'blue',
            ],
            [
                'title' => 'Layanan Terkendala',
                'subtitle' => 'Daftar layanan ada error',
                'count' => $stats['issues'],
                'url' => route('tester.services.index', [
                    'status' => 'gangguan',
                ]),
                'color' => 'rose',
            ],
            [
                'title' => 'Sudah Selesai',
                'subtitle' => 'Layanan berhasil diuji',
                'count' => $stats['checked'],
                'url' => route('tester.services.index', [
                    'status' => 'normal',
                ]),
                'color' => 'emerald',
            ],
            [
                'title' => 'Semua Penugasan',
                'subtitle' => 'Workspace seluruh layanan',
                'count' => $stats['total_assigned'],
                'url' => route('tester.services.index'),
                'color' => 'indigo',
            ],
        ];

        return Inertia::render('Dashboard', [
            'activePeriod' => $periodInfo
                ? $periodInfo['month_year']
                : null,

            'periodInfo' => $periodInfo,
            'stats' => $stats,
            'statusDistribution' => $statusDistribution,
            'actionRequired' => $actionRequired,
            'priorityTasks' => $priorityTasks,
            'recentActivities' => $recentActivities,
            'quickActions' => $quickActions,

            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function submitCheck(
        Request $request,
        PeriodService $periodService
    ) {
        $user = $request->user();

        if (
            $user->role !== 'admin'
            && $periodService->tester_id !== $user->id
        ) {
            abort(
                403,
                'Anda tidak memiliki akses untuk mengubah pengujian layanan ini.'
            );
        }

        $request->validate([
            'status' => 'required|in:normal,gangguan',
            'description' => 'nullable|string',
        ]);

        $periodService->update([
            'status' => $request->status,
            'description' => $request->description,
            'checked_at' => now(),
        ]);

        return back()->with(
            'success',
            'Hasil pengecekan berhasil disimpan.'
        );
    }
}