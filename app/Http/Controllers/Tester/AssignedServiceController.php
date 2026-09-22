<?php

namespace App\Http\Controllers\Tester;

use App\Http\Controllers\Controller;
use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\ServiceCheckLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssignedServiceController extends Controller
{
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
        12 => 'Desember',
    ];

    private function formatIndonesianDate(Carbon $date): string
    {
        $day = $date->format('d');
        $month = $this->indonesianMonths[(int) $date->format('n')]
            ?? $date->format('F');
        $year = $date->format('Y');

        return "{$day} {$month} {$year}";
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // ─── Active Checking Period ─────────────────────────────────────────
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
            'progress_percent' => 0,
        ];

        $categories = [];
        $levels = ['Level 1', 'Level 2', 'Level 3'];

        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');
        $levelFilter = $request->input('level', '');
        $categoryFilter = $request->input('category', '');

        $paginatedServices = collect([]);

        if ($activePeriod) {
            $periodCarbon = Carbon::parse($activePeriod->date);
            
            $daysRemaining = null;
            $isOverdue = false;
            $deadlineDate = null;
            
            if ($activePeriod->deadline_date) {
                $deadlineCarbon = Carbon::parse($activePeriod->deadline_date)->endOfDay();
                $now = Carbon::now();
                $deadlineDate = $deadlineCarbon->format('Y-m-d');
                
                // Jika deadline besok malam, dan sekarang hari ini, maka sisa 1 hari
                // Jika sudah lewat, akan negatif
                $daysRemaining = (int) $now->startOfDay()->diffInDays($deadlineCarbon->startOfDay(), false);
                $isOverdue = $now->isAfter($deadlineCarbon) && $activePeriod->status === 'proses';
            }

            $periodInfo = [
                'id' => $activePeriod->id,
                'period_title' => $this->formatIndonesianDate($periodCarbon),
                'month_year' => (
                    $this->indonesianMonths[
                        (int) $periodCarbon->format('n')
                    ] ?? $periodCarbon->format('F')
                ) . ' ' . $periodCarbon->format('Y'),
                'date' => $periodCarbon->format('Y-m-d'),
                'status' => $activePeriod->status,
                'deadline_date' => $deadlineDate,
                'days_remaining' => $daysRemaining,
                'is_overdue' => $isOverdue,
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

            $checkedCount = $normalCount + $issuesCount;

            $progressPct = $totalAssigned > 0
                ? (int) round(
                    ($checkedCount / $totalAssigned) * 100
                )
                : 0;

            $stats = [
                'total_assigned' => $totalAssigned,
                'checked' => $checkedCount,
                'pending' => $pendingCount,
                'issues' => $issuesCount,
                'normal' => $normalCount,
                'progress_percent' => $progressPct,
            ];

            // Categories available in tester's assigned services
            $categories = PeriodService::where(
                'period_id',
                $activePeriod->id
            )
                ->where('tester_id', $user->id)
                ->join(
                    'services',
                    'period_services.service_id',
                    '=',
                    'services.id'
                )
                ->whereNotNull('services.category')
                ->where('services.category', '!=', '')
                ->distinct()
                ->orderBy('services.category')
                ->pluck('services.category')
                ->values()
                ->all();

            // Filtered and paginated assigned services
            $servicesQuery = PeriodService::with([
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
                },
            ])
                ->where('period_id', $activePeriod->id)
                ->where('tester_id', $user->id);

            if (!empty($search)) {
                $servicesQuery->whereHas(
                    'service',
                    function ($q) use ($search) {
                        $q->where(
                            'service_name',
                            'like',
                            "%{$search}%"
                        )
                            ->orWhere(
                                'category',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'service_url',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            }

            if (!empty($statusFilter)) {
                $servicesQuery->where(
                    'status',
                    $statusFilter
                );
            }

            if (!empty($levelFilter)) {
                $servicesQuery->whereHas(
                    'service',
                    function ($q) use ($levelFilter) {
                        $q->where('level', $levelFilter);
                    }
                );
            }

            if (!empty($categoryFilter)) {
                $servicesQuery->whereHas(
                    'service',
                    function ($q) use ($categoryFilter) {
                        $q->where(
                            'category',
                            $categoryFilter
                        );
                    }
                );
            }

            $paginatedServices = $servicesQuery
                ->orderBy('id', 'asc')
                ->paginate(10)
                ->withQueryString()
                ->through(function ($ps) {
                    return [
                        'id' => $ps->id,
                        'service_id' => $ps->service?->id,
                        'name' => $ps->service?->service_name ?? '-',
                        'category' => $ps->service?->category ?? 'Umum',
                        'level' => $ps->service?->level ?? '-',
                        'icon' => $ps->service?->icon,
                        'url' => $ps->service?->service_url,
                        'status' => $ps->status,
                        'deskripsi_layanan' =>
                            $ps->service?->deskripsi_layanan,
                        'tester_instruction' =>
                            $ps->service?->tester_instruction,
                        'last_description' => $ps->description,
                        'checked_at' => $ps->checked_at
                            ? Carbon::parse(
                                $ps->checked_at
                            )->format('d M Y, H:i')
                            : null,
                        'checked_at_human' => $ps->checked_at
                            ? Carbon::parse(
                                $ps->checked_at
                            )->diffForHumans()
                            : null,
                    ];
                });
        }

        return Inertia::render('Tester/Services/Index', [
            'periodInfo' => $periodInfo,
            'services' => $paginatedServices,
            'stats' => $stats,
            'categories' => $categories,
            'levels' => $levels,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'level' => $levelFilter,
                'category' => $categoryFilter,
            ],
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

        // Jangan izinkan pengecekan jika periode sudah selesai (hanya untuk tester, admin bebas jika perlu)
        if ($user->role !== 'admin' && $periodService->period->status === 'selesai') {
            abort(403, 'Periode pengecekan sudah selesai dan dikunci.');
        }

        $request->validate([
            'status' => 'required|in:normal,gangguan',
            'description' => 'required_if:status,gangguan|nullable|string|max:5000',
        ], [
            'description.required_if' => 'Catatan / Keterangan kendala wajib diisi jika layanan berstatus kendala.',
            'description.max' => 'Catatan tidak boleh lebih dari 5000 karakter.'
        ]);

        $periodService->update([
            'status' => $request->status,
            'description' => $request->description,
            'checked_at' => now(),
        ]);

        ServiceCheckLog::create([
            'period_service_id' => $periodService->id,
            'tester_id' => $user->id,
            'status' => $request->status,
            'description' => $request->description,
        ]);

        return back()->with(
            'success',
            'Hasil pengecekan berhasil disimpan.'
        );
    }
}
