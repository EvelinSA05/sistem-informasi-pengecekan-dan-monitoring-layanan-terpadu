<?php

namespace App\Http\Controllers;

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\Service;
use App\Models\ServiceCheckLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        CheckingPeriod::syncOverdueStatuses();

        $user = $request->user();

        // ─── ADMIN DASHBOARD ──────────────────────────────────────────────
        if ($user->role === 'admin') {

            // --- Tester Stats (1 query agregat) ---
            $testerAgg = User::where('role', 'tester')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif
                ")->first();
            $testerTotal = (int) ($testerAgg->total ?? 0);
            $testerAktif = (int) ($testerAgg->aktif ?? 0);
            $testerNonaktif = $testerTotal - $testerAktif;

            // --- Period Stats (1 query agregat) ---
            $periodAgg = CheckingPeriod::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
            ")->first();
            $periodTotal = (int) ($periodAgg->total ?? 0);
            $periodProses = (int) ($periodAgg->proses ?? 0);
            $periodSelesai = (int) ($periodAgg->selesai ?? 0);

            // --- Service Stats (1 query agregat untuk layanan tersinkronisasi dari Majadigi) ---
            $baseServiceQuery = Service::whereNotNull('synced_at');
            $serviceAgg = (clone $baseServiceQuery)->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'tidak_aktif' THEN 1 ELSE 0 END) as nonaktif,
                SUM(CASE WHEN status = 'butuh_konfirmasi' THEN 1 ELSE 0 END) as butuh_konfirmasi
            ")->first();
            $serviceTotal          = (int) ($serviceAgg->total ?? 0);
            $serviceAktif          = (int) ($serviceAgg->aktif ?? 0);
            $serviceNonaktif       = (int) ($serviceAgg->nonaktif ?? 0);
            $serviceBtuhKonfirmasi = (int) ($serviceAgg->butuh_konfirmasi ?? 0);

            // --- Distribusi Kategori Layanan ---
            $categoryRows = (clone $baseServiceQuery)->selectRaw('category, count(*) as total')
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
            $levelRows = (clone $baseServiceQuery)->select('level')
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
        $activePeriods = CheckingPeriod::where('status', 'proses')
            ->whereHas('periodTesters', function ($q) use ($user) {
                $q->where('tester_id', $user->id);
            })
            ->orderBy('date', 'desc')
            ->get();

        $activePeriod = null;
        foreach ($activePeriods as $period) {
            if ($period->deadline_date) {
                $deadlineCarbon = Carbon::parse($period->deadline_date)->endOfDay();
                if (!Carbon::now()->isAfter($deadlineCarbon)) {
                    $activePeriod = $period;
                    break;
                }
            } else {
                $activePeriod = $period;
                break;
            }
        }

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
                'is_overdue' => false, // We filtered out overdue periods, so it is never overdue on the dashboard
            ];

            // Base query for tester assignments in active period (1 query agregat tunggal)
            $agg = PeriodService::where('period_id', $activePeriod->id)
                ->where('tester_id', $user->id)
                ->selectRaw("
                    COUNT(*) as total_assigned,
                    SUM(CASE WHEN status = 'normal' THEN 1 ELSE 0 END) as normal_count,
                    SUM(CASE WHEN status = 'gangguan' THEN 1 ELSE 0 END) as issues_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'need_confirmation' THEN 1 ELSE 0 END) as need_confirm_count
                ")->first();

            $totalAssigned = (int) ($agg->total_assigned ?? 0);
            $normalCount   = (int) ($agg->normal_count ?? 0);
            $issuesCount   = (int) ($agg->issues_count ?? 0);
            $pendingCount  = (int) ($agg->pending_count ?? 0);
            $needConfirm   = (int) ($agg->need_confirm_count ?? 0);

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
                    'action_url' => route('tester.periods.services', [
                        'period' => $activePeriod->id,
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
                    'action_url' => route('tester.periods.services', [
                        'period' => $activePeriod->id,
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
                    'action_url' => route('tester.periods.services', [
                        'period' => $activePeriod->id,
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
                    'action_url' => route('tester.periods.services', [
                        'period' => $activePeriod->id,
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
                ->select([
                    'id',
                    'period_id',
                    'service_id',
                    'tester_id',
                    'status',
                    'description',
                    'attachment',
                    'checked_at',
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
                        'attachment' => $ps->attachment,
                        'attachment_name' => $ps->attachment ? basename($ps->attachment) : null,
                        'attachment_url' => $ps->attachment ? route('periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
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
            ->select([
                'id',
                'service_id',
                'tester_id',
                'status',
                'description',
                'attachment',
                'checked_at',
            ])
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
                    'attachment' => $ps->attachment,
                    'attachment_name' => $ps->attachment ? basename($ps->attachment) : null,
                    'attachment_url' => $ps->attachment ? route('periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
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
                'url' => $activePeriod ? route('tester.periods.services', [
                    'period' => $activePeriod->id,
                    'status' => 'pending',
                ]) : route('tester.services.index'),
                'color' => 'blue',
            ],
            [
                'title' => 'Layanan Terkendala',
                'subtitle' => 'Daftar layanan ada error',
                'count' => $stats['issues'],
                'url' => $activePeriod ? route('tester.periods.services', [
                    'period' => $activePeriod->id,
                    'status' => 'gangguan',
                ]) : route('tester.services.index'),
                'color' => 'rose',
            ],
            [
                'title' => 'Sudah Selesai',
                'subtitle' => 'Layanan berhasil diuji',
                'count' => $stats['checked'],
                'url' => $activePeriod ? route('tester.periods.services', [
                    'period' => $activePeriod->id,
                    'status' => 'normal',
                ]) : route('tester.services.index'),
                'color' => 'emerald',
            ],
            [
                'title' => 'Semua Penugasan',
                'subtitle' => 'Workspace seluruh layanan',
                'count' => $stats['total_assigned'],
                'url' => $activePeriod ? route('tester.periods.services', [
                    'period' => $activePeriod->id,
                ]) : route('tester.services.index'),
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

        if ($user->role !== 'admin' && $periodService->period?->status === 'selesai') {
            abort(403, 'Periode pengecekan sudah selesai dan dikunci.');
        }

        $validated = $request->validate([
            'status' => 'required|in:normal,gangguan',
            'description' => 'required_if:status,gangguan|nullable|string|max:5000',
        ], [
            'description.required_if' => 'Catatan / Keterangan kendala wajib diisi jika layanan berstatus kendala.',
            'description.max' => 'Catatan tidak boleh lebih dari 5000 karakter.',
        ]);

        try {
            $periodService->update([
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
                'checked_at' => now(),
            ]);

            ServiceCheckLog::create([
                'period_service_id' => $periodService->id,
                'tester_id' => $user->id,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]);

            return back()->with(
                'success',
                'Hasil pengecekan berhasil disimpan.'
            );
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan hasil pengecekan (Dashboard): ' . $e->getMessage(), [
                'period_service_id' => $periodService->id,
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan hasil pengecekan: ' . $e->getMessage());
        }
    }
}