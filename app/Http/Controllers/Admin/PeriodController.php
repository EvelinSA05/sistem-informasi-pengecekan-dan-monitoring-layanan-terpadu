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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Format tanggal Carbon ke format bahasa Indonesia, contoh: "16 September 2026"
     */
    private function formatIndonesianDate(Carbon $carbonDate): string
    {
        $day = $carbonDate->format('d');
        $month = $this->indonesianMonths[(int) $carbonDate->format('n')] ?? $carbonDate->format('F');
        $year = $carbonDate->format('Y');

        return "{$day} {$month} {$year}";
    }

    /**
     * Menampilkan daftar periode pengecekan (Screenshot 1).
     */
    public function index()
    {
        CheckingPeriod::syncOverdueStatuses();

        $periods = CheckingPeriod::with([
            'creator:id,name',
            'periodTesters.tester:id,name,bidang,email',
            'periodServices:id,period_id,service_id,tester_id,status,checked_at'
        ])
        ->orderBy('date', 'desc')
        ->get()
        ->map(function ($period) {
            $carbonDate = Carbon::parse($period->date);
            $periodTitle = $this->formatIndonesianDate($carbonDate);

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

            $createdDateFormatted = $period->created_at ? $this->formatIndonesianDate(Carbon::parse($period->created_at)) : null;
            $deadlineDateFormatted = $period->deadline_date ? $this->formatIndonesianDate(Carbon::parse($period->deadline_date)) : null;

            return [
                'id' => $period->id,
                'date' => $carbonDate->format('Y-m-d'),
                'period_title' => $periodTitle,
                'created_at_label' => "Dibuat " . ($createdDateFormatted ?? Carbon::parse($period->created_at)->format('Y-m-d')),
                'created_date_formatted' => $createdDateFormatted,
                'deadline_date' => $period->deadline_date ? Carbon::parse($period->deadline_date)->format('Y-m-d') : null,
                'deadline_date_formatted' => $deadlineDateFormatted,
                'deadline' => $period->deadline_date ? Carbon::parse($period->deadline_date)->format('Y-m-d') : null,
                'deadline_formatted' => $deadlineDateFormatted,
                'status' => $period->status,
                'is_overdue' => $period->is_overdue,
                'testers' => $testers,
                'checked_services' => $checkedServices,
                'total_services' => $totalServices,
                'progress_percent' => $progressPercent,
                'has_results' => $checkedServices > 0,
                'has_testers' => $testers->count() > 0,
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
     * Helper untuk mengambil data tester dan layanan
     */
    private function getFormData($excludePeriodId = null)
    {
        $testers = User::where('role', 'tester')
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nip', 'bidang'])
            ->map(function ($tester) use ($excludePeriodId) {
                $parts = explode(' ', trim($tester->name));
                $initials = count($parts) >= 2
                    ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                    : strtoupper(substr($parts[0] ?? 'T', 0, 2));

                // Ambil semua tugas pending pada periode yang sedang berstatus 'proses'
                $pendingQuery = PeriodService::where('tester_id', $tester->id)
                    ->where('status', 'pending')
                    ->whereHas('period', function ($q) use ($excludePeriodId) {
                        $q->where('status', 'proses');
                        if ($excludePeriodId) {
                            $q->where('checking_periods.id', '!=', $excludePeriodId);
                        }
                    });

                $pendingTasksCount = (clone $pendingQuery)->count();
                $maxDeadline = $pendingQuery->with('period')->get()->max(function ($ps) {
                    return $ps->period->deadline_date;
                });

                return [
                    'id' => $tester->id,
                    'name' => $tester->name,
                    'email' => $tester->email,
                    'nip' => $tester->nip,
                    'bidang' => $tester->bidang ?? 'Layanan Publik',
                    'initials' => $initials,
                    'is_available' => $pendingTasksCount === 0,
                    'pending_tasks_count' => $pendingTasksCount,
                    'max_pending_deadline' => $maxDeadline,
                ];
            });

        $services = Service::where('is_active', true)
            ->whereNotNull('synced_at')
            ->orderBy('id')
            ->get(['id', 'source_service_id', 'service_name', 'category', 'icon', 'service_url']);

        return [$testers, $services];
    }

    /**
     * Halaman form pembuatan periode baru (Screenshot 2 & 3).
     */
    public function create()
    {
        [$testers, $services] = $this->getFormData();

        return Inertia::render('Admin/Periods/Form', [
            'isEdit' => false,
            'period' => null,
            'testers' => $testers,
            'services' => $services,
            'initialDate' => Carbon::now()->format('Y-m-d'),
            'initialTesterIds' => [],
        ]);
    }

    /**
     * Halaman form edit periode (Screenshot 5).
     */
    public function edit(CheckingPeriod $period)
    {
        CheckingPeriod::syncOverdueStatuses();
        $period->refresh();

        [$testers, $services] = $this->getFormData($period->id);

        $carbonDate = Carbon::parse($period->date);
        $formattedDate = $carbonDate->format('Y-m-d');
        $title = $this->formatIndonesianDate($carbonDate);

        $assignedTesterIds = $period->periodTesters()->pluck('tester_id')->toArray();

        return Inertia::render('Admin/Periods/Form', [
            'isEdit' => true,
            'period' => [
                'id' => $period->id,
                'title' => $title,
                'date' => $formattedDate,
                'deadline_date' => $period->deadline_date ? Carbon::parse($period->deadline_date)->format('Y-m-d') : null,
                'status' => $period->status,
                'is_overdue' => $period->is_overdue,
            ],
            'testers' => $testers,
            'services' => $services,
            'initialDate' => $formattedDate,
            'initialTesterIds' => $assignedTesterIds,
        ]);
    }

    /**
     * Menyimpan periode baru dan melakukan distribusi layanan otomatis.
     */
    public function store(Request $request)
    {
        if ($request->has('tanggal_periode') && !$request->has('date')) {
            $request->merge(['date' => $request->tanggal_periode]);
        }
        if ($request->has('deadline') && !$request->has('deadline_date')) {
            $request->merge(['deadline_date' => $request->deadline]);
        }

        $request->validate([
            'date' => 'required|date',
            'deadline_date' => 'nullable|date|after_or_equal:date',
            'tester_ids' => 'required|array|min:1',
            'tester_ids.*' => 'exists:users,id',
        ], [
            'date.required' => 'Tanggal pengecekan wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'deadline_date.date' => 'Format tenggat waktu tidak valid.',
            'deadline_date.after_or_equal' => 'Tenggat waktu tidak boleh lebih awal dari tanggal periode.',
            'tester_ids.required' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.min' => 'Pilih minimal satu tester untuk periode pengecekan.',
        ]);

        $date = Carbon::parse($request->date)->format('Y-m-d');

        if (CheckingPeriod::where('date', $date)->exists()) {
            return back()->withErrors([
                'date' => 'Periode untuk tanggal ' . $this->formatIndonesianDate(Carbon::parse($date)) . ' sudah ada.',
            ]);
        }

        $testerIds = array_values(array_unique($request->tester_ids));
        $targetDateObj = \Carbon\Carbon::parse($date)->toDateString();
        
        $busyTesters = \App\Models\User::whereIn('id', $testerIds)
            ->whereHas('periodServices', function ($q) use ($targetDateObj) {
                $q->where('status', 'pending')
                  ->whereHas('period', function ($pq) use ($targetDateObj) {
                      $pq->where('status', 'proses')
                         ->where('date', '<=', $targetDateObj)
                         ->where(function ($query) use ($targetDateObj) {
                             $query->whereNull('deadline_date')
                                   ->orWhere('deadline_date', '>=', $targetDateObj);
                         });
                  });
            })
            ->get();
            
        Log::info('PeriodController Store validation', [
            'tester_ids' => $testerIds,
            'busy' => $busyTesters->pluck('name')
        ]);

        if ($busyTesters->isNotEmpty()) {
            $names = $busyTesters->pluck('name')->implode(', ');
            return back()->withErrors([
                'tester_ids' => "Pembuatan dibatalkan! Tester berikut masih memiliki tugas pengecekan yang belum selesai (pending) di periode aktif: {$names}."
            ]);
        }

        DB::transaction(function () use ($date, $request) {
            $period = CheckingPeriod::create([
                'date' => $date,
                'deadline_date' => $request->deadline_date ? Carbon::parse($request->deadline_date)->format('Y-m-d') : null,
                'status' => 'proses',
                'created_by' => $request->user()->id,
            ]);

            $testerIds = array_values(array_unique($request->tester_ids));
            foreach ($testerIds as $testerId) {
                PeriodTester::create([
                    'period_id' => $period->id,
                    'tester_id' => $testerId,
                ]);
            }

            $services = Service::where('is_active', true)->whereNotNull('synced_at')->orderBy('id')->get();
            $testerCount = count($testerIds);

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
        CheckingPeriod::syncOverdueStatuses();
        $period->refresh();

        // Selalu sinkronkan layanan periode jika berstatus 'proses' atau terdapat layanan yang tidak tersinkronisasi
        $hasInvalidServices = PeriodService::where('period_id', $period->id)
            ->where(function ($q) {
                $q->whereNull('service_id')
                  ->orWhereHas('service', fn($sq) => $sq->whereNull('synced_at'));
            })
            ->exists();

        if ($period->status === 'proses' || $hasInvalidServices) {
            $period->syncWithMasterServices();
            $period->refresh();
        }

        $period->load([
            'creator:id,name,email',
            'periodTesters.tester:id,name,email,bidang,nip',
            'periodServices' => fn($q) => $q->withoutAttachmentData(),
            'periodServices.service:id,source_service_id,service_name,category,level,icon,service_url,deskripsi_layanan,tester_instruction',
            'periodServices.tester:id,name,email,bidang',
            'periodServices.serviceIssue',
        ]);

        $carbonDate = Carbon::parse($period->date);
        $periodTitle = $this->formatIndonesianDate($carbonDate);

        $totalServices = $period->periodServices->count();
        $checkedServices = $period->periodServices->filter(fn($ps) => $ps->status !== 'pending' && !empty($ps->checked_at))->count();
        $normalServices = $period->periodServices->where('status', 'normal')->count();
        $issuesServices = $period->periodServices->where('status', 'gangguan')->count();
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

            $assignedServices = $period->periodServices->where('tester_id', $tester->id)->map(function ($ps) use ($tester) {
                return [
                    'id' => $ps->id,
                    'service_id' => $ps->service_id,
                    'service_name' => $ps->service?->service_name ?? 'Layanan',
                    'category' => $ps->service?->category ?? 'Umum',
                    'level' => $ps->service?->level ?? '-',
                    'icon' => $ps->service?->icon ?? 'globe',
                    'service_url' => $ps->service?->service_url,
                    'deskripsi_layanan' => $ps->service?->deskripsi_layanan,
                    'tester_instruction' => $ps->service?->tester_instruction,
                    'status' => $ps->status,
                    'description' => $ps->description,
                    'last_description' => $ps->description,
                    'admin_note' => $ps->serviceIssue?->admin_note ?? $ps->admin_note,
                    'handling_status' => $ps->serviceIssue?->handling_status ?? 'belum_ditindaklanjuti',
                    'attachment' => $ps->attachment,
                    'attachment_name' => $ps->attachment ? basename($ps->attachment) : null,
                    'attachment_url' => $ps->attachment ? route('admin.periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                    'checked_at' => $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d M Y, H:i') : null,
                    'tester_name' => $ps->tester?->name ?? ($tester->name ?? 'Tester'),
                    'tester_bidang' => $ps->tester?->bidang ?? ($tester->bidang ?? '-'),
                ];
            })->values();

            $distribution[] = [
                'tester_id' => $tester->id,
                'name' => $tester->name,
                'email' => $tester->email,
                'initials' => $initials,
                'total_services' => $assignedServices->count(),
                'checked_services' => $assignedServices->filter(fn($s) => $s['status'] !== 'pending')->count(),
                'services' => $assignedServices,
            ];
        }

        // Ambil daftar layanan yang terkendala pada periode ini dengan fallback aman
        $troubledServices = [];
        try {
            $troubledServices = PeriodService::with([
                'service',
                'tester',
                'serviceIssue.admin',
                'checkLogs.tester',
            ])
            ->where('period_id', $period->id)
            ->whereIn('status', ['gangguan', 'kendala', 'tidak_aktif', 'error'])
            ->orderByDesc('checked_at')
                ->get()
                ->map(function ($ps) use ($period) {
                    $issue = $ps->serviceIssue;
                    if (!$issue) {
                        try {
                            $issue = \App\Models\ServiceIssue::firstOrCreate(
                                ['period_service_id' => $ps->id],
                                [
                                    'layanan_id' => $ps->service_id,
                                    'periode_id' => $period->id,
                                    'has_notif'  => false,
                                    'has_solved' => false,
                                ]
                            );
                        } catch (\Throwable $e) {
                            $issue = null;
                        }
                    }

                    $timeline = $this->buildTimeline($ps);

                    return [
                        'id'               => $ps->id,
                        'issue_id'         => $issue?->id,
                        'service_id'       => $ps->service_id,
                        'service_name'     => $ps->service?->service_name ?? 'Layanan',
                        'service_url'      => $ps->service?->service_url,
                        'category'         => $ps->service?->category ?? 'Umum',
                        'level'            => $ps->service?->level ?? '-',
                        'icon'             => $ps->service?->icon ?? 'globe',
                        'tester_name'      => $ps->tester?->name ?? 'Tester',
                        'tester_bidang'    => $ps->tester?->bidang ?? '-',
                        'description'      => $ps->description ?: 'Kendala dilaporkan oleh tester',
                        'admin_note'       => $issue?->admin_note ?? $ps->admin_note,
                        'handling_status'  => $issue?->handling_status ?? 'belum_ditindaklanjuti',
                        'admin_handled_at' => $issue?->admin_handled_at ? Carbon::parse($issue->admin_handled_at)->format('d M Y, H:i') : null,
                        'attachment'       => $ps->attachment,
                        'attachment_name'  => $ps->attachment ? basename($ps->attachment) : null,
                        'attachment_url'   => $ps->attachment ? route('admin.periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                        'checked_at'       => $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d M Y, H:i') : '-',
                        'has_solved'       => (bool) ($issue?->has_solved ?? false),
                        'has_solved_label' => match($issue?->handling_status) {
                            'perlu_pengecekan_ulang' => 'perlu pengecekan ulang',
                            'selesai' => 'sudah tindak lanjuti',
                            default => ($issue && $issue->has_solved) ? 'sudah tindak lanjuti' : 'belum tindak lanjuti',
                        },
                        'has_notif'        => (bool) ($issue?->has_notif ?? false),
                        'timeline'         => $timeline,
                    ];
                })
                ->values();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal memuat troubled services di period show: ' . $e->getMessage());
            $troubledServices = [];
        }

        return Inertia::render('Admin/Periods/Show', [
            'period' => [
                'id' => $period->id,
                'title' => $periodTitle,
                'date' => $carbonDate->format('Y-m-d'),
                'deadline_date' => $period->deadline_date ? Carbon::parse($period->deadline_date)->format('d F Y') : null,
                'deadline_formatted' => $period->deadline_date ? $this->formatIndonesianDate(Carbon::parse($period->deadline_date)) : null,
                'status' => $period->status,
                'is_overdue' => $period->is_overdue,
                'created_at_label' => Carbon::parse($period->created_at)->format('d F Y H:i'),
                'creator_name' => $period->creator?->name ?? 'Admin',
                'total_services' => $totalServices,
                'checked_services' => $checkedServices,
                'normal_services' => $normalServices,
                'issues_services' => $issuesServices,
                'pending_services' => $pendingServices,
                'progress_percent' => $totalServices > 0 ? round(($checkedServices / $totalServices) * 100) : 0,
            ],
            'distribution' => $distribution,
            'troubled_services' => $troubledServices,
        ]);
    }

    /**
     * Memperbarui data periode (Edit form / Quick status toggle).
     */
    public function update(Request $request, CheckingPeriod $period)
    {
        // Kasus 1: Toggle status dari tabel Index atau halaman Show
        if ($request->has('status') && !$request->has('tester_ids') && !$request->has('date') && !$request->has('tanggal_periode')) {
            $request->validate([
                'status' => 'required|in:proses,selesai,aktif,nonaktif',
            ]);

            $statusVal = match($request->status) {
                'aktif' => 'proses',
                'nonaktif' => 'selesai',
                default => $request->status,
            };

            // Validasi: Cegah aktivasi jika deadline periode sudah melewati batas
            if ($statusVal === 'proses' && $period->is_overdue) {
                $formattedDeadline = $period->deadline_date ? $this->formatIndonesianDate(Carbon::parse($period->deadline_date)) : '-';
                return back()->with('error', "Deadline periode ini telah melewati batas ({$formattedDeadline}) dan tidak dapat diaktifkan. Silakan perbarui/edit deadline terlebih dahulu jika ingin mengaktifkan kembali.");
            }

            $period->update([
                'status' => $statusVal,
            ]);

            $label = $statusVal === 'proses' ? 'diaktifkan' : 'dinonaktifkan';
            return back()->with('success', "Periode berhasil {$label}.");
        }

        // Kasus 2: Update dari Form Edit Periode (Tanggal, Tester & Distribusi)
        if ($request->has('tanggal_periode') && !$request->has('date')) {
            $request->merge(['date' => $request->tanggal_periode]);
        }
        if ($request->has('deadline') && !$request->has('deadline_date')) {
            $request->merge(['deadline_date' => $request->deadline]);
        }

        $request->validate([
            'date' => 'required|date',
            'deadline_date' => 'nullable|date|after_or_equal:date',
            'tester_ids' => 'required|array|min:1',
            'tester_ids.*' => 'exists:users,id',
        ], [
            'date.required' => 'Tanggal pengecekan wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'deadline_date.date' => 'Format tenggat waktu tidak valid.',
            'deadline_date.after_or_equal' => 'Tenggat waktu tidak boleh lebih awal dari tanggal periode.',
            'tester_ids.required' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.min' => 'Pilih minimal satu tester untuk periode pengecekan.',
        ]);

        $date = Carbon::parse($request->date)->format('Y-m-d');

        // Cek duplikasi tanggal kecuali untuk periode ini sendiri
        if (CheckingPeriod::where('date', $date)->where('id', '!=', $period->id)->exists()) {
            return back()->withErrors([
                'date' => 'Periode untuk tanggal ' . $this->formatIndonesianDate(Carbon::parse($date)) . ' sudah ada.',
            ]);
        }

        $testerIds = array_values(array_unique($request->tester_ids));
        $targetDateObj = \Carbon\Carbon::parse($date)->toDateString();
        
        $busyTesters = \App\Models\User::whereIn('id', $testerIds)
            ->whereHas('periodServices', function ($q) use ($period, $targetDateObj) {
                $q->where('status', 'pending')
                  ->whereHas('period', function ($pq) use ($period, $targetDateObj) {
                      $pq->where('status', 'proses')
                         ->where('checking_periods.id', '!=', $period->id)
                         ->where('date', '<=', $targetDateObj)
                         ->where(function ($query) use ($targetDateObj) {
                             $query->whereNull('deadline_date')
                                   ->orWhere('deadline_date', '>=', $targetDateObj);
                         });
                  });
            })
            ->get();
            
        Log::info('PeriodController Update validation', [
            'period_id' => $period->id,
            'tester_ids' => $testerIds,
            'busy' => $busyTesters->pluck('name')
        ]);

        if ($busyTesters->isNotEmpty()) {
            $names = $busyTesters->pluck('name')->implode(', ');
            return back()->withErrors([
                'tester_ids' => "Pembaruan dibatalkan! Tester berikut masih memiliki tugas pengecekan yang belum selesai (pending) di periode aktif lain: {$names}."
            ]);
        }

        DB::transaction(function () use ($period, $date, $request) {
            $period->update([
                'date' => $date,
                'deadline_date' => $request->deadline_date ? Carbon::parse($request->deadline_date)->format('Y-m-d') : null,
            ]);

            // Simpan status cek yang sudah ada sebelum redistribusi agar catatan tidak hilang
            $existingServices = PeriodService::where('period_id', $period->id)->get()->keyBy('service_id');

            // Perbarui tester periode
            $testerIds = array_values(array_unique($request->tester_ids));
            PeriodTester::where('period_id', $period->id)->delete();
            foreach ($testerIds as $testerId) {
                PeriodTester::create([
                    'period_id' => $period->id,
                    'tester_id' => $testerId,
                ]);
            }

            // Redistribusi layanan aktif
            $services = Service::where('is_active', true)->whereNotNull('synced_at')->orderBy('id')->get();
            $testerCount = count($testerIds);

            PeriodService::where('period_id', $period->id)->delete();
            foreach ($services as $index => $service) {
                $assignedTesterId = $testerIds[$index % $testerCount];
                $prev = $existingServices->get($service->id);

                PeriodService::create([
                    'period_id' => $period->id,
                    'service_id' => $service->id,
                    'tester_id' => $assignedTesterId,
                    'status' => $prev ? $prev->status : 'pending',
                    'description' => $prev ? $prev->description : null,
                    'checked_at' => $prev ? $prev->checked_at : null,
                ]);
            }
        });

        return redirect()->route('admin.periods.index')->with('success', 'Data periode berhasil diperbarui.');
    }

    /**
     * Menghapus periode pengecekan (Hapus).
     * 2. Periode yang sudah ada tester tapi BELUM melakukan pengecekan -> MASIH BISA DIHAPUS
     * 3. Periode yang sudah memiliki HASIL pengecekan -> TIDAK BISA DIHAPUS
     */
    public function destroy(CheckingPeriod $period)
    {
        try {
            DB::transaction(function () use ($period) {
                $period->periodTesters()->delete();
                $period->periodServices()->delete();
                $period->delete();
            });

            return redirect()->route('admin.periods.index')->with('success', 'Periode pengecekan berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus periode pengecekan: ' . $e->getMessage(), [
                'period_id' => $period->id,
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.periods.index')->with('error', 'Gagal menghapus periode. Data mungkin masih terkait riwayat pengujian.');
        }
    }

    /**
     * Helper untuk memfilter daftar PeriodService saat Export Excel / PDF berdasarkan query parameter.
     */
    private function filterPeriodServicesForExport($periodServices, Request $request): array
    {
        $category = trim((string) $request->query('category', ''));
        $level    = trim((string) $request->query('level', ''));
        $status   = trim((string) $request->query('status', ''));
        $testerId = trim((string) $request->query('tester_id', ''));
        $search   = trim((string) $request->query('search', ''));

        $activeFilters = [];

        $filtered = $periodServices->filter(function ($ps) use ($category, $level, $status, $testerId, $search) {
            if ($category !== '' && $category !== 'all') {
                if (strcasecmp(trim((string) ($ps->service?->category ?? '')), $category) !== 0) {
                    return false;
                }
            }

            if ($level !== '' && $level !== 'all') {
                $svcLevel = trim((string) ($ps->service?->level ?? ''));
                $normalizedFilterLevel = preg_replace('/^level\s*/i', '', $level);
                $normalizedSvcLevel    = preg_replace('/^level\s*/i', '', $svcLevel);
                if (strcasecmp($svcLevel, $level) !== 0 && strcasecmp($normalizedSvcLevel, $normalizedFilterLevel) !== 0) {
                    return false;
                }
            }

            if ($status !== '' && $status !== 'all') {
                if ($ps->status !== $status) {
                    return false;
                }
            }

            if ($testerId !== '' && $testerId !== 'all') {
                if ((int) $ps->tester_id !== (int) $testerId) {
                    return false;
                }
            }

            if ($search !== '') {
                $q = mb_strtolower($search);
                $svcName = mb_strtolower((string) ($ps->service?->service_name ?? ''));
                $svcCat  = mb_strtolower((string) ($ps->service?->category ?? ''));
                $tName   = mb_strtolower((string) ($ps->tester?->name ?? ''));
                if (!str_contains($svcName, $q) && !str_contains($svcCat, $q) && !str_contains($tName, $q)) {
                    return false;
                }
            }

            return true;
        })->values();

        if ($category !== '' && $category !== 'all') {
            $activeFilters['OPD / Kategori'] = $category;
        }
        if ($level !== '' && $level !== 'all') {
            $activeFilters['Level Layanan'] = str_starts_with(strtolower($level), 'level') ? $level : "Level {$level}";
        }
        if ($status !== '' && $status !== 'all') {
            $activeFilters['Status'] = match ($status) {
                'normal'   => 'Normal',
                'gangguan' => 'Terkendala',
                'pending'  => 'Belum Dicek',
                default    => strtoupper($status),
            };
        }
        if ($testerId !== '' && $testerId !== 'all') {
            $testerName = User::find((int) $testerId)?->name ?? "ID #{$testerId}";
            $activeFilters['Tester'] = $testerName;
        }
        if ($search !== '') {
            $activeFilters['Kata Kunci'] = $search;
        }

        return [
            'services'       => $filtered,
            'active_filters' => $activeFilters,
        ];
    }

    /**
     * Export Laporan Periode ke Format Excel (CSV kompatibel Excel dengan UTF-8 BOM).
     */
    public function exportExcel(Request $request, CheckingPeriod $period): StreamedResponse
    {
        $period->load([
            'creator:id,name',
            'periodServices' => fn($q) => $q->withoutAttachmentData(),
            'periodServices.service',
            'periodServices.tester:id,name',
        ]);

        $filterResult   = $this->filterPeriodServicesForExport($period->periodServices, $request);
        $targetServices = $filterResult['services'];
        $activeFilters  = $filterResult['active_filters'];

        $carbonDate = Carbon::parse($period->date);
        $periodTitle = $this->formatIndonesianDate($carbonDate);
        $fileSafeTitle = str_replace(' ', '_', $periodTitle);

        $filename = "Laporan_Pengecekan_{$fileSafeTitle}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($period, $periodTitle, $targetServices, $activeFilters) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // ==========================================
            // SECTION 1 — IDENTITAS LAPORAN & INFORMASI
            // ==========================================
            fputcsv($file, ['MAJADIGI CHECKER']);
            fputcsv($file, ['LAPORAN HASIL PENGECEKAN LAYANAN DIGITAL']);
            fputcsv($file, []);

            fputcsv($file, ['INFORMASI LAPORAN']);
            fputcsv($file, ['Periode Pengecekan', $periodTitle]);
            fputcsv($file, ['Status Periode', strtoupper($period->status)]);
            fputcsv($file, ['Dibuat Oleh', $period->creator?->name ?? 'Admin']);
            fputcsv($file, ['Tanggal Laporan', date('d/m/Y H:i:s')]);
            if (!empty($activeFilters)) {
                $filterSummary = collect($activeFilters)->map(fn($v, $k) => "{$k}: {$v}")->implode(' | ');
                fputcsv($file, ['Filter Aktif', $filterSummary]);
            }
            fputcsv($file, []);

            // Perhitungan metrik aktual (mengikuti filter jika ada)
            $totalServices = $targetServices->count();
            $normalCount   = $targetServices->where('status', 'normal')->count();
            $issuesCount   = $targetServices->where('status', 'gangguan')->count();
            $pendingCount  = $targetServices->where('status', 'pending')->count();
            $checkedCount  = $totalServices - $pendingCount;
            $readinessPct  = $totalServices > 0 ? round(($checkedCount / $totalServices) * 100) : 0;

            $opdCount = $targetServices
                ->map(fn($ps) => $ps->service?->category ?: '-')
                ->unique()
                ->count();

            // ==========================================
            // SECTION 2 — RINGKASAN HASIL PENGECEKAN
            // ==========================================
            fputcsv($file, ['RINGKASAN HASIL PENGECEKAN']);
            fputcsv($file, ['Total Layanan', $totalServices]);
            fputcsv($file, ['Layanan Normal', $normalCount]);
            fputcsv($file, ['Layanan Terkendala', $issuesCount]);
            fputcsv($file, ['Belum Diperiksa', $pendingCount]);
            fputcsv($file, ['Tingkat Kesiapan', "{$readinessPct}%"]);
            fputcsv($file, ['Jumlah OPD/Kab/Kota', $opdCount]);
            fputcsv($file, []);

            // ==========================================
            // SECTION 3 — REKAP STATUS
            // ==========================================
            fputcsv($file, ['REKAP STATUS']);
            fputcsv($file, ['Status Pengecekan', 'Jumlah']);
            fputcsv($file, ['NORMAL', $normalCount]);
            fputcsv($file, ['TERKENDALA', $issuesCount]);
            fputcsv($file, ['BELUM DIPERIKSA', $pendingCount]);
            fputcsv($file, []);

            // ==========================================
            // SECTION 4 — REKAP OPD/KAB/KOTA
            // ==========================================
            $opdSummary = $targetServices
                ->groupBy(fn($ps) => $ps->service?->category ?: '-')
                ->map(fn($group) => $group->count())
                ->sortDesc();

            fputcsv($file, ['REKAP OPD/KAB/KOTA']);
            fputcsv($file, ['OPD/Kab/Kota', 'Jumlah Layanan']);
            foreach ($opdSummary as $opdName => $opdTotal) {
                fputcsv($file, [$opdName, $opdTotal]);
            }
            fputcsv($file, []);

            // Pemisahan Layanan Berjalan & Terkendala (Konsisten dengan Report PDF)
            $runningServices = $targetServices->filter(fn($ps) => $ps->status === 'normal')->values();
            $issueServices = $targetServices->filter(fn($ps) => $ps->status !== 'normal')->values();

            // ==========================================
            // SECTION 5 — A. LAYANAN BERJALAN
            // ==========================================
            fputcsv($file, ['A. LAYANAN BERJALAN (' . $runningServices->count() . ' Layanan)']);
            fputcsv($file, [
                'No.',
                'Nama Layanan',
                'Link Layanan',
                'OPD/Kab/Kota',
                'Level Layanan',
            ]);

            $noRunning = 1;
            foreach ($runningServices as $ps) {
                $levelDisplay = !empty($ps->service?->level) ? $ps->service->level : '-';
                fputcsv($file, [
                    $noRunning++,
                    $ps->service?->service_name ?? '-',
                    $ps->service?->service_url ?: '-',
                    $ps->service?->category ?: '-',
                    $levelDisplay,
                ]);
            }
            if ($runningServices->isEmpty()) {
                fputcsv($file, ['-', 'Tidak ada data layanan berjalan pada periode ini', '-', '-', '-']);
            }
            fputcsv($file, []);

            // ==========================================
            // SECTION 6 — B. LAYANAN TERKENDALA
            // ==========================================
            fputcsv($file, ['B. LAYANAN TERKENDALA (' . $issueServices->count() . ' Layanan)']);
            fputcsv($file, [
                'No.',
                'Nama Layanan',
                'Link Layanan',
                'OPD/Kab/Kota',
                'Level Layanan',
            ]);

            $noIssue = 1;
            foreach ($issueServices as $ps) {
                $levelDisplay = !empty($ps->service?->level) ? $ps->service->level : '-';
                fputcsv($file, [
                    $noIssue++,
                    $ps->service?->service_name ?? '-',
                    $ps->service?->service_url ?: '-',
                    $ps->service?->category ?: '-',
                    $levelDisplay,
                ]);
            }
            if ($issueServices->isEmpty()) {
                fputcsv($file, ['-', 'Tidak ada data layanan terkendala pada periode ini', '-', '-', '-']);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Laporan Periode ke Tampilan Infografis / Siap Cetak PDF.
     */
    public function exportPdf(Request $request, CheckingPeriod $period)
    {
        $period->load([
            'creator:id,name,email',
            'periodServices' => fn($q) => $q->withoutAttachmentData(),
            'periodServices.service:id,source_service_id,service_name,category,level,icon,service_url',
            'periodServices.tester:id,name',
        ]);

        $filterResult   = $this->filterPeriodServicesForExport($period->periodServices, $request);
        $targetServices = $filterResult['services'];
        $activeFilters  = $filterResult['active_filters'];

        $carbonDate = Carbon::parse($period->date);
        $periodTitle = $this->formatIndonesianDate($carbonDate);
        $dayNumber = (int) $carbonDate->format('j');
        $monthName = $this->indonesianMonths[(int) $carbonDate->format('n')] ?? $carbonDate->format('F');
        $year = $carbonDate->format('Y');

        // Format nama file PDF sesuai ketentuan: Laporan_Pengecekan_[Tanggal]_[Bulan]_[Tahun]
        $pdfFileName = "Laporan_Pengecekan_{$dayNumber}_{$monthName}_{$year}";

        $totalServices = $targetServices->count();
        $checkedServices = $targetServices->filter(fn($ps) => $ps->status !== 'pending' && !empty($ps->checked_at))->count();
        $normalServices = $targetServices->where('status', 'normal')->count();
        $issuesServices = $targetServices->where('status', 'gangguan')->count();
        $pendingServices = $totalServices - $checkedServices;
        $progressPercent = $totalServices > 0 ? round(($checkedServices / $totalServices) * 100) : 0;

        $runningServices = $targetServices
            ->filter(fn($ps) => $ps->status === 'normal')
            ->values()
            ->map(function ($ps, $idx) {
                return [
                    'no' => $idx + 1,
                    'id' => $ps->id,
                    'service_name' => $ps->service?->service_name ?? 'Layanan',
                    'service_url' => $ps->service?->service_url,
                    'category' => $ps->service?->category ?: '-',
                    'opd' => $ps->service?->category ?: '-',
                    'level' => $ps->service?->level ?: '-',
                    'status' => $ps->status,
                ];
            })
            ->all();

        $issueServices = $targetServices
            ->filter(fn($ps) => $ps->status !== 'normal')
            ->values()
            ->map(function ($ps, $idx) {
                return [
                    'no' => $idx + 1,
                    'id' => $ps->id,
                    'service_name' => $ps->service?->service_name ?? 'Layanan',
                    'service_url' => $ps->service?->service_url,
                    'category' => $ps->service?->category ?: '-',
                    'opd' => $ps->service?->category ?: '-',
                    'level' => $ps->service?->level ?: '-',
                    'status' => $ps->status,
                ];
            })
            ->all();

        $servicesList = $targetServices->values()->map(function ($ps, $idx) {
            return [
                'no' => $idx + 1,
                'id' => $ps->id,
                'service_name' => $ps->service?->service_name ?? 'Layanan',
                'service_url' => $ps->service?->service_url,
                'category' => $ps->service?->category ?: '-',
                'opd' => $ps->service?->category ?: '-',
                'level' => $ps->service?->level ?: '-',
                'status' => $ps->status,
            ];
        })->all();

        return Inertia::render('Admin/Periods/ExportReport', [
            'report' => [
                'title' => $periodTitle,
                'file_name' => $pdfFileName,
                'pdf_filename' => "{$pdfFileName}.pdf",
                'month_name' => $monthName,
                'year' => $year,
                'period_date' => $carbonDate->format('Y-m-d'),
                'formatted_date' => $periodTitle,
                'status' => $period->status,
                'created_at_label' => Carbon::parse($period->created_at)->format('d F Y H:i'),
                'creator_name' => $period->creator?->name ?? 'Admin',
                'generated_at' => Carbon::now()->format('d F Y H:i'),
                'total_services' => $totalServices,
                'checked_services' => $checkedServices,
                'normal_services' => $normalServices,
                'issues_services' => $issuesServices,
                'pending_services' => $pendingServices,
                'progress_percent' => $progressPercent,
                'active_filters' => $activeFilters,
            ],
            'running_services' => $runningServices,
            'issue_services' => $issueServices,
            'services' => $servicesList,
        ]);
    }

    /**
     * Unduh attachment bukti kendala pengecekan layanan.
     */
    public function downloadAttachment(Request $request, PeriodService $periodService)
    {
        $user = $request->user();

        // Proteksi Otorisasi (Anti-IDOR):
        // Admin diizinkan mengakses seluruh lampiran; Tester hanya diizinkan mengakses lampiran
        // pada layanan/periode yang ditugaskan kepadanya.
        if (!$user || ($user->role !== 'admin' && $periodService->tester_id !== $user->id)) {
            $isAssignedToPeriod = $user && $periodService->period()
                ->whereHas('periodTesters', fn($q) => $q->where('tester_id', $user->id))
                ->exists();

            if (!$isAssignedToPeriod) {
                abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses lampiran ini.');
            }
        }

        if (!$periodService->attachment && !$periodService->attachment_data) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        $extension = pathinfo($periodService->attachment ?? 'file.jpg', PATHINFO_EXTENSION);
        $serviceName = $periodService->service?->service_name ?? 'Kendala';
        $serviceSlug = Str::slug($serviceName);
        $dateSuffix = $periodService->checked_at ? Carbon::parse($periodService->checked_at)->format('Ymd_His') : date('Ymd');
        $downloadName = 'Bukti_Kendala_' . ($serviceSlug ?: 'layanan') . '_' . $dateSuffix . ($extension ? ('.' . $extension) : '');

        $isInline = request()->boolean('inline') || request()->has('inline');

        // 1. Try serving from database (base64 data) — works permanently on Vercel
        $attachmentBase64 = $periodService->attachment_data;
        $attachmentMime = $periodService->attachment_mime;

        if (!$attachmentBase64) {
            $latestLogWithData = \App\Models\ServiceCheckLog::where('period_service_id', $periodService->id)
                ->whereNotNull('attachment_data')
                ->latest()
                ->first(['attachment_data', 'attachment_mime']);
            if ($latestLogWithData) {
                $attachmentBase64 = $latestLogWithData->attachment_data;
                $attachmentMime = $latestLogWithData->attachment_mime;
            }
        }

        if ($attachmentBase64) {
            $mimeType = $attachmentMime ?: 'image/jpeg';
            $binaryData = base64_decode($attachmentBase64);
            $disposition = $isInline ? 'inline' : 'attachment';

            return response($binaryData, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => "{$disposition}; filename=\"{$downloadName}\"",
                'Content-Length'      => strlen($binaryData),
                'Cache-Control'       => 'public, max-age=86400',
            ]);
        }

        // 2. Fallback: try serving from storage disk (works in local / traditional hosting)
        if ($periodService->attachment) {
            try {
                $disk = Storage::disk('public');
                if ($disk->exists($periodService->attachment)) {
                    if ($isInline) {
                        $mimeType = $disk->mimeType($periodService->attachment) ?: 'image/jpeg';
                        return response()->file($disk->path($periodService->attachment), [
                            'Content-Type'        => $mimeType,
                            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
                        ]);
                    }
                    return $disk->download($periodService->attachment, $downloadName);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Disk download error: ' . $e->getMessage());
            }
        }

        // 3. Fallback for older records where file was in ephemeral /tmp before migration:
        // Return crisp SVG representation so images in modals don't appear as broken icons
        if ($isInline) {
            $fileName = htmlspecialchars(basename($periodService->attachment ?: 'attachment.jpg'));
            $cleanServiceName = htmlspecialchars($serviceName);
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="260" viewBox="0 0 600 260">
                <rect width="600" height="260" fill="#f8fafc" rx="16"/>
                <rect x="2" y="2" width="596" height="256" fill="none" stroke="#e2e8f0" stroke-width="2" rx="14"/>
                <g transform="translate(268, 45)">
                    <rect width="64" height="64" rx="16" fill="#eff6ff" stroke="#bfdbfe" stroke-width="1.5"/>
                    <path d="M18 46l11-13 9 11 15-19 13 21H18z" fill="#3b82f6"/>
                    <circle cx="28" cy="23" r="5" fill="#60a5fa"/>
                </g>
                <text x="300" y="145" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="14" font-weight="700" fill="#1e293b" text-anchor="middle">Bukti Kendala: ' . $cleanServiceName . '</text>
                <text x="300" y="170" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="12" font-weight="500" fill="#64748b" text-anchor="middle">' . $fileName . '</text>
                <text x="300" y="195" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="11" fill="#94a3b8" text-anchor="middle">File tercatat di database Majadigi Checker</text>
            </svg>';
            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=utf-8',
                'Cache-Control' => 'no-cache, private',
            ]);
        }

        // For download fallback
        $infoText = "MAJADIGI CHECKING SYSTEM - BUKTI KENDALA\n"
                  . "========================================\n"
                  . "Layanan   : " . ($periodService->service?->service_name ?? '-') . "\n"
                  . "Periode   : " . ($periodService->period?->date ?? '-') . "\n"
                  . "Tester    : " . ($periodService->tester?->name ?? '-') . "\n"
                  . "Waktu Cek : " . ($periodService->checked_at ?? '-') . "\n"
                  . "File      : " . basename($periodService->attachment ?? 'bukti') . "\n"
                  . "Catatan   : " . ($periodService->description ?: '-') . "\n";

        return response($infoText, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$downloadName}.txt\"",
        ]);
    }

    /**
     * Menyusun timeline alur pemeriksaan secara lengkap dan kronologis:
     * Dari log kendala awal, tindak lanjut penanganan admin, hingga log pengecekan baru/ulang.
     */
    protected function buildTimeline(PeriodService $ps): array
    {
        $issue = $ps->serviceIssue;
        $events = collect();

        // 1. Ambil seluruh log pemeriksaan dari tabel service_check_logs
        $logs = $ps->checkLogs && $ps->checkLogs->isNotEmpty()
            ? $ps->checkLogs->sortBy('created_at')->values()
            : collect();

        // Fallback jika migrasi lama belum ada data di service_check_logs tapi layanan sudah pernah dicek
        if ($logs->isEmpty() && $ps->status !== 'pending') {
            $logs = collect([(object) [
                'id' => 0,
                'status' => $ps->status,
                'description' => $ps->description,
                'attachment' => $ps->attachment,
                'created_at' => $ps->checked_at ?? $ps->updated_at,
                'tester' => $ps->tester,
            ]]);
        }

        $firstCheckAt = null;

        foreach ($logs as $idx => $log) {
            $isFirst = ($idx === 0);
            $createdAt = $log->created_at ? Carbon::parse($log->created_at) : null;
            if ($isFirst && $createdAt) {
                $firstCheckAt = $createdAt;
            }

            if ($isFirst) {
                $stage = 'initial_check';
                $stageLabel = ($log->status === 'gangguan') 
                    ? 'Log Kendala (Pemeriksaan Awal)' 
                    : 'Pemeriksaan Awal';
            } else {
                $stage = 'new_check';
                $stageLabel = ($log->status === 'normal') 
                    ? 'Log Pengecekan Baru (Normal)' 
                    : 'Log Pengecekan Baru (Terkendala)';
            }

            $events->push([
                'id' => 'check_' . ($log->id ?? $idx),
                'type' => 'tester_check',
                'stage' => $stage,
                'stage_label' => $stageLabel,
                'is_initial' => $isFirst,
                'is_new_check' => !$isFirst,
                'status' => $log->status,
                'status_label' => ($log->status === 'normal') ? 'Normal' : 'Terkendala',
                'actor_name' => $log->tester?->name ?? $ps->tester?->name ?? 'Tester',
                'actor_role' => 'Tester Layanan',
                'description' => $log->description,
                'attachment' => $log->attachment,
                'attachment_name' => $log->attachment ? basename($log->attachment) : null,
                'attachment_url' => $log->attachment ? route('admin.periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                'created_at' => $createdAt ? $createdAt->format('d M Y, H:i') : '-',
                'created_at_human' => $createdAt ? $createdAt->diffForHumans() : null,
                'timestamp' => $createdAt ? $createdAt->timestamp : ($idx * 10),
            ]);
        }

        // 2. Tindak lanjut penanganan oleh Admin
        $adminNote = $issue?->admin_note ?? $ps->admin_note;
        $adminHandledAt = $issue?->admin_handled_at;
        $hasAdminHandled = !empty($adminNote) || !empty($adminHandledAt) || ($issue && $issue->has_solved);

        if ($hasAdminHandled) {
            $handledCarbon = $adminHandledAt ? Carbon::parse($adminHandledAt) : null;
            $ts = $handledCarbon 
                ? $handledCarbon->timestamp 
                : ($firstCheckAt ? $firstCheckAt->timestamp + 60 : time());

            $events->push([
                'id' => 'admin_handling_' . ($issue?->id ?? $ps->id),
                'type' => 'admin_handling',
                'stage' => 'admin_handling',
                'stage_label' => 'Tindak Lanjut Penanganan (Admin)',
                'is_initial' => false,
                'is_new_check' => false,
                'status' => $issue?->handling_status ?? 'perlu_pengecekan_ulang',
                'status_label' => match($issue?->handling_status) {
                    'perlu_pengecekan_ulang' => 'Ditangani Admin (Perlu Cek Ulang)',
                    'selesai' => 'Selesai Ditindaklanjuti',
                    default => 'Telah Ditindaklanjuti',
                },
                'actor_name' => $issue?->admin?->name ?? 'Administrator',
                'actor_role' => 'Admin Sistem',
                'description' => $adminNote,
                'attachment' => null,
                'attachment_name' => null,
                'attachment_url' => null,
                'created_at' => $handledCarbon ? $handledCarbon->format('d M Y, H:i') : '-',
                'created_at_human' => $handledCarbon ? $handledCarbon->diffForHumans() : null,
                'timestamp' => $ts,
            ]);
        }

        $sorted = $events->sortBy('timestamp')->values();

        if ($sorted->isNotEmpty()) {
            $lastIdx = $sorted->count() - 1;
            $sorted = $sorted->map(function ($item, $key) use ($lastIdx) {
                $item['is_latest'] = ($key === $lastIdx);
                $item['step_number'] = $key + 1;
                return $item;
            });
        }

        return $sorted->all();
    }

    /**
     * Sinkronisasikan layanan periode dengan Master Layanan Majadigi secara manual.
     */
    public function syncPeriodServices(CheckingPeriod $period): RedirectResponse
    {
        $period->syncWithMasterServices();
        return back()->with('success', 'Layanan periode berhasil disinkronkan dengan Master Layanan Majadigi (' . $period->periodServices()->count() . ' layanan).');
    }
}
