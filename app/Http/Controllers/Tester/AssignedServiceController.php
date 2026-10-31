<?php

namespace App\Http\Controllers\Tester;

use App\Http\Controllers\Controller;
use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\ServiceCheckLog;
use App\Models\ServiceIssue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        CheckingPeriod::syncOverdueStatuses();

        $user = $request->user();

        // Jika ada parameter period_id, langsung alihkan atau tampilkan periode terkait
        if ($request->filled('period_id')) {
            $period = CheckingPeriod::findOrFail($request->input('period_id'));
            return $this->showPeriod($request, $period);
        }

        $periods = CheckingPeriod::with([
            'periodTesters.tester:id,name,bidang,email',
            'periodServices' => function ($q) use ($user) {
                if ($user->role !== 'admin') {
                    $q->where('tester_id', $user->id);
                }
            }
        ])
        ->where(function ($q) use ($user) {
            if ($user->role === 'admin') {
                return;
            }
            $q->whereHas('periodTesters', function ($sq) use ($user) {
                $sq->where('tester_id', $user->id);
            })->orWhereHas('periodServices', function ($sq) use ($user) {
                $sq->where('tester_id', $user->id);
            });
        })
        ->orderBy('date', 'desc')
        ->get()
        ->map(function ($period) use ($user) {
            $carbonDate = Carbon::parse($period->date);
            $periodTitle = $this->formatIndonesianDate($carbonDate);

            // Layanan tester pada periode ini
            $myServices = $period->periodServices;
            $myTotal = $myServices->count();
            $myChecked = $myServices->filter(function ($ps) {
                return $ps->status !== 'pending' && !empty($ps->checked_at);
            })->count();
            $myProgress = $myTotal > 0 ? round(($myChecked / $myTotal) * 100) : 0;

            // Deadline info
            $daysRemaining = null;
            $isOverdue = false;
            $deadlineDate = null;
            if ($period->deadline_date) {
                $deadlineCarbon = Carbon::parse($period->deadline_date)->endOfDay();
                $now = Carbon::now();
                $deadlineDate = $deadlineCarbon->format('Y-m-d');
                $daysRemaining = (int) $now->startOfDay()->diffInDays($deadlineCarbon->startOfDay(), false);
                $isOverdue = $now->isAfter($deadlineCarbon) && $period->status === 'proses';
            }

            // Tester avatars
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
                'date' => $carbonDate->format('Y-m-d'),
                'period_title' => $periodTitle,
                'created_at_label' => "Dibuat " . Carbon::parse($period->created_at)->format('Y-m-d'),
                'status' => $period->status,
                'deadline_date' => $deadlineDate,
                'days_remaining' => $daysRemaining,
                'is_overdue' => $isOverdue,
                'testers' => $testers,
                'my_total_services' => $myTotal,
                'my_checked_services' => $myChecked,
                'my_progress_percent' => $myProgress,
            ];
        });

        return Inertia::render('Tester/Periods/Index', [
            'periods' => $periods,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function showPeriod(Request $request, CheckingPeriod $period)
    {
        $user = $request->user();

        // Validasi hak akses penugasan periode jika tester
        if ($user->role !== 'admin') {
            $isAssigned = $period->periodTesters()->where('tester_id', $user->id)->exists()
                || $period->periodServices()->where('tester_id', $user->id)->exists();

            if (!$isAssigned) {
                abort(403, 'Anda tidak ditugaskan pada periode pengecekan ini.');
            }
        }

        $periodCarbon = Carbon::parse($period->date);
        
        $daysRemaining = null;
        $isOverdue = false;
        $deadlineDate = null;
        
        if ($period->deadline_date) {
            $deadlineCarbon = Carbon::parse($period->deadline_date)->endOfDay();
            $now = Carbon::now();
            $deadlineDate = $deadlineCarbon->format('Y-m-d');
            $daysRemaining = (int) $now->startOfDay()->diffInDays($deadlineCarbon->startOfDay(), false);
            $isOverdue = $now->isAfter($deadlineCarbon) && $period->status === 'proses';
        }

        $periodInfo = [
            'id' => $period->id,
            'period_title' => $this->formatIndonesianDate($periodCarbon),
            'month_year' => (
                $this->indonesianMonths[
                    (int) $periodCarbon->format('n')
                ] ?? $periodCarbon->format('F')
            ) . ' ' . $periodCarbon->format('Y'),
            'date' => $periodCarbon->format('Y-m-d'),
            'status' => $period->status,
            'deadline_date' => $deadlineDate,
            'days_remaining' => $daysRemaining,
            'is_overdue' => $isOverdue,
        ];

        // Base query for tester assignments in this period
        $baseTesterQuery = PeriodService::where('period_id', $period->id);
        if ($user->role !== 'admin') {
            $baseTesterQuery->where('tester_id', $user->id);
        }

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
            ? (int) round(($checkedCount / $totalAssigned) * 100)
            : 0;

        $stats = [
            'total_assigned' => $totalAssigned,
            'checked' => $checkedCount,
            'pending' => $pendingCount,
            'issues' => $issuesCount,
            'normal' => $normalCount,
            'progress_percent' => $progressPct,
        ];

        $levels = ['Level 1', 'Level 2', 'Level 3'];

        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');
        $levelFilter = $request->input('level', '');
        $categoryFilter = $request->input('category', '');

        // Categories available in tester's assigned services in this period
        $categoriesQuery = PeriodService::where('period_id', $period->id)
            ->join('services', 'period_services.service_id', '=', 'services.id')
            ->whereNotNull('services.category')
            ->where('services.category', '!=', '');

        if ($user->role !== 'admin') {
            $categoriesQuery->where('period_services.tester_id', $user->id);
        }

        $categories = $categoriesQuery
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
            ->where('period_id', $period->id);

        if ($user->role !== 'admin') {
            $servicesQuery->where('tester_id', $user->id);
        }

        if (!empty($search)) {
            $servicesQuery->whereHas('service', function ($q) use ($search) {
                $q->where('service_name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('service_url', 'like', "%{$search}%");
            });
        }

        if (!empty($statusFilter)) {
            $servicesQuery->where('status', $statusFilter);
        }

        if (!empty($levelFilter)) {
            $servicesQuery->whereHas('service', function ($q) use ($levelFilter) {
                $q->where('level', $levelFilter);
            });
        }

        if (!empty($categoryFilter)) {
            $servicesQuery->whereHas('service', function ($q) use ($categoryFilter) {
                $q->where('category', $categoryFilter);
            });
        }

        $paginatedServices = $servicesQuery
            ->orderBy('id', 'asc')
            ->paginate($request->input('per_page', 5))
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
            });

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

        // Jangan izinkan pengecekan jika periode dinonaktifkan (hanya untuk tester, admin bebas jika perlu)
        if ($user->role !== 'admin' && $periodService->period->status !== 'proses') {
            abort(403, 'Periode pengecekan sedang dinonaktifkan.');
        }

        // Jangan izinkan jika periode sudah melewati deadline
        if ($user->role !== 'admin' && $periodService->period->deadline_date) {
            $deadlineCarbon = Carbon::parse($periodService->period->deadline_date)->endOfDay();
            if (Carbon::now()->isAfter($deadlineCarbon)) {
                return back()->with('error', 'Periode pengecekan sudah melewati batas waktu (deadline).');
            }
        }

        $validated = $request->validate([
            'status' => 'required|in:normal,gangguan',
            'description' => 'required_if:status,gangguan|nullable|string|max:5000',
        ], [
            'description.required_if' => 'Catatan / Keterangan kendala wajib diisi jika layanan berstatus kendala.',
            'description.max' => 'Catatan tidak boleh lebih dari 5000 karakter.'
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

            // Catat atau perbarui di tabel service_issues (layanan_terkendala)
            if ($validated['status'] === 'gangguan') {
                ServiceIssue::updateOrCreate(
                    ['period_service_id' => $periodService->id],
                    [
                        'layanan_id'  => $periodService->service_id,
                        'periode_id'  => $periodService->period_id,
                        'has_notif'   => false,
                        'has_solved'  => false,
                    ]
                );
            } else if ($validated['status'] === 'normal') {
                // Jika sebelumnya ada catatan kendala pada penugasan ini, tandai sudah selesai/teratasi
                ServiceIssue::where('period_service_id', $periodService->id)->update([
                    'has_solved' => true,
                ]);
            }

            // Auto-complete: jika seluruh layanan pada periode ini sudah selesai diperiksa (0 pending), ubah status periode menjadi 'selesai'
            $hasPending = PeriodService::where('period_id', $periodService->period_id)
                ->where('status', 'pending')
                ->exists();

            if (!$hasPending) {
                $periodService->period()->update([
                    'status' => 'selesai',
                ]);
            }

            return back()->with(
                'success',
                'Hasil pengecekan berhasil disimpan.'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal menyimpan hasil pengecekan (Tester): ' . $e->getMessage(), [
                'period_service_id' => $periodService->id,
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan hasil pengecekan.');
        }
    }
}
