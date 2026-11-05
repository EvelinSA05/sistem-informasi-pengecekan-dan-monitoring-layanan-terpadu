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
        $isPartialPoll = $request->hasHeader('X-Inertia-Partial-Data');

        CheckingPeriod::syncOverdueStatuses();
        if (!$isPartialPoll) {
            CheckingPeriod::syncAllActivePeriodsWithMasterServices();
        }

        $user = $request->user();

        // Jika ada parameter period_id, langsung alihkan atau tampilkan periode terkait
        if ($request->filled('period_id')) {
            $period = CheckingPeriod::findOrFail($request->input('period_id'));
            return $this->showPeriod($request, $period);
        }

        $periods = CheckingPeriod::with([
            'periodTesters.tester:id,name,bidang,email',
            'periodServices' => function ($q) use ($user) {
                $q->select(['id', 'period_id', 'tester_id', 'status', 'checked_at']);
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

        $isPartialPoll = $request->hasHeader('X-Inertia-Partial-Data');

        // Sinkronkan layanan periode aktif dengan Master Layanan Majadigi (lewatkan saat background polling)
        if ($period->status === 'proses' && !$isPartialPoll) {
            $period->syncWithMasterServices();
            $period->refresh();
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

        // Gabungkan 4 query COUNT menjadi 1 query agregat tunggal untuk efisiensi beban database
        $baseTesterQuery = PeriodService::where('period_id', $period->id);
        if ($user->role !== 'admin') {
            $baseTesterQuery->where('tester_id', $user->id);
        }

        $agg = $baseTesterQuery->selectRaw("
            COUNT(*) as total_assigned,
            SUM(CASE WHEN status = 'normal' THEN 1 ELSE 0 END) as normal_count,
            SUM(CASE WHEN status = 'gangguan' THEN 1 ELSE 0 END) as issues_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
        ")->first();

        $totalAssigned = (int) ($agg->total_assigned ?? 0);
        $normalCount = (int) ($agg->normal_count ?? 0);
        $issuesCount = (int) ($agg->issues_count ?? 0);
        $pendingCount = (int) ($agg->pending_count ?? 0);
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

        return Inertia::render('Tester/Services/Index', [
            'periodInfo' => $periodInfo,
            'stats' => $stats,
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
            // Gunakan lazy evaluation (closure) agar saat usePoll meminta only: ['periodInfo', 'stats'],
            // query daftar kategori dan layanan beserta timeline tidak dieksekusi sia-sia.
            'categories' => fn () => PeriodService::where('period_id', $period->id)
                ->join('services', 'period_services.service_id', '=', 'services.id')
                ->whereNotNull('services.category')
                ->where('services.category', '!=', '')
                ->when($user->role !== 'admin', fn ($q) => $q->where('period_services.tester_id', $user->id))
                ->distinct()
                ->orderBy('services.category')
                ->pluck('services.category')
                ->values()
                ->all(),
            'services' => function () use ($period, $user, $search, $statusFilter, $levelFilter, $categoryFilter, $request) {
                $servicesQuery = PeriodService::withoutAttachmentData()->with([
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
                    'serviceIssue.admin',
                    'checkLogs.tester',
                    'tester',
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

                return $servicesQuery
                    ->orderBy('id', 'asc')
                    ->paginate($request->input('per_page', 5))
                    ->withQueryString()
                    ->through(function ($ps) {
                        $issue = $ps->serviceIssue;
                        $handlingStatus = $issue?->handling_status ?? 'belum_ditindaklanjuti';
                        $adminNote = $issue?->admin_note ?? $ps->admin_note;
                        $isRecheckNeeded = ($ps->status === 'gangguan' && $handlingStatus === 'perlu_pengecekan_ulang');

                        $timeline = $this->buildTimeline($ps);

                        $pendingStage = null;
                        if ($ps->status === 'gangguan' && $handlingStatus === 'belum_ditindaklanjuti') {
                            $pendingStage = [
                                'type' => 'waiting_admin',
                                'title' => 'Menunggu Tindak Lanjut oleh Admin',
                                'description' => 'Kendala telah dilaporkan oleh Tester. Menunggu tindakan perbaikan dari Administrator.',
                                'badge' => 'Belum Ditindaklanjuti',
                                'badge_class' => 'bg-rose-100 text-rose-800 border-rose-200',
                            ];
                        } elseif ($isRecheckNeeded) {
                            $pendingStage = [
                                'type' => 'waiting_recheck',
                                'title' => 'Menunggu Pengecekan Ulang oleh Tester',
                                'description' => 'Administrator telah menandai kendala sudah ditangani. Silakan lakukan pengujian kembali melalui tombol Cek Ulang Layanan.',
                                'badge' => 'Perlu Pengecekan Ulang',
                                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-200',
                            ];
                        } elseif ($ps->status === 'normal' && $handlingStatus === 'selesai' && count($timeline) > 1) {
                            $pendingStage = [
                                'type' => 'solved',
                                'title' => 'Kendala Berhasil Diselesaikan',
                                'description' => 'Layanan telah ditangani oleh Administrator dan berhasil diverifikasi berstatus Normal pada pengecekan ulang.',
                                'badge' => 'Selesai Teratasi',
                                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            ];
                        }

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
                            'admin_note' => $adminNote,
                            'handling_status' => $handlingStatus,
                            'is_recheck_needed' => $isRecheckNeeded,
                            'admin_handled_at' => $issue?->admin_handled_at
                                ? Carbon::parse($issue->admin_handled_at)->format('d M Y, H:i')
                                : null,
                            'attachment' => $ps->attachment,
                            'attachment_mime' => $ps->attachment_mime,
                            'attachment_name' => $ps->attachment ? basename($ps->attachment) : null,
                            'attachment_url' => $ps->attachment ? route('periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                            'checked_at' => $ps->checked_at
                                ? Carbon::parse($ps->checked_at)->format('d M Y, H:i')
                                : null,
                            'checked_at_human' => $ps->checked_at
                                ? Carbon::parse($ps->checked_at)->diffForHumans()
                                : null,
                            'timeline' => $timeline,
                            'pending_stage' => $pendingStage,
                            'check_logs' => $timeline,
                        ];
                    });
            },
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

        $periodService->load('serviceIssue');
        $issue = $periodService->serviceIssue;
        $isRecheck = ($periodService->status === 'gangguan' && ($issue?->handling_status === 'perlu_pengecekan_ulang'));

        $validated = $request->validate([
            'status' => 'required|in:normal,gangguan',
            'description' => 'required|string|max:5000',
            'attachment' => [
                \Illuminate\Validation\Rule::requiredIf(function () use ($request, $periodService, $isRecheck) {
                    if ($request->input('status') !== 'gangguan') {
                        return false;
                    }
                    if ($isRecheck) {
                        return true;
                    }
                    if ($request->boolean('remove_attachment')) {
                        return true;
                    }
                    $hasExisting = $request->filled('existing_attachment') || !empty($periodService->attachment);
                    return !$hasExisting;
                }),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,gif',
                'max:10240',
            ],
            'existing_attachment' => 'nullable|string',
            'remove_attachment' => 'nullable|boolean',
        ], [
            'description.required_if' => $isRecheck 
                ? 'Catatan kendala terbaru wajib diisi.' 
                : 'Catatan / Keterangan kendala wajib diisi jika layanan berstatus kendala.',
            'description.max' => 'Catatan tidak boleh lebih dari 5000 karakter.',
            'attachment.required' => $isRecheck 
                ? 'Bukti lampiran kendala terbaru (screenshot/file) wajib diunggah sebagai bukti.' 
                : 'Bukti lampiran kendala (screenshot/file) wajib diunggah.',
            'attachment.file' => 'Bukti lampiran harus berupa file yang valid.',
            'attachment.mimes' => 'Format file lampiran harus berupa gambar (JPG, JPEG, PNG, WEBP, GIF) atau dokumen PDF.',
            'attachment.max' => 'Ukuran file lampiran maksimal 10MB.',
        ]);

        try {
            $attachmentPath = $periodService->attachment;
            $attachmentData = $periodService->attachment_data;
            $attachmentMime = $periodService->attachment_mime;

            if ($validated['status'] === 'normal') {
                $attachmentPath = null;
                $attachmentData = null;
                $attachmentMime = null;
            } else {
                if ($request->hasFile('attachment')) {
                    try {
                        $file = $request->file('attachment');
                        $attachmentMime = $file->getMimeType();
                        // Store base64 encoded content in DB for Vercel compatibility
                        $attachmentData = base64_encode(file_get_contents($file->getRealPath()));
                        // Keep a filename path for reference
                        $originalName = $file->getClientOriginalName();
                        $safeFilename = 'attachments/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                        $attachmentPath = $safeFilename;
                        // Also try to store to disk (may fail on Vercel, but that's OK)
                        try {
                            $tmpPublic = storage_path('app/public/attachments');
                            if (!is_dir($tmpPublic)) {
                                @mkdir($tmpPublic, 0777, true);
                            }
                            $storedPath = $file->store('attachments', 'public');
                            if ($storedPath) $attachmentPath = $storedPath;
                        } catch (\Throwable $storeErr) {
                            \Illuminate\Support\Facades\Log::warning('Attachment disk store failed (using DB): ' . $storeErr->getMessage());
                        }
                    } catch (\Throwable $storeErr) {
                        \Illuminate\Support\Facades\Log::warning('Attachment store failed: ' . $storeErr->getMessage());
                    }
                } elseif ($request->boolean('remove_attachment')) {
                    $attachmentPath = null;
                    $attachmentData = null;
                    $attachmentMime = null;
                }
            }

            try {
                $periodService->update([
                    'status' => $validated['status'],
                    'description' => $validated['description'] ?? null,
                    'attachment' => $attachmentPath,
                    'attachment_data' => $attachmentData,
                    'attachment_mime' => $attachmentMime,
                    'checked_at' => now(),
                ]);
            } catch (\Throwable $updateErr) {
                $periodService->update([
                    'status' => $validated['status'],
                    'description' => $validated['description'] ?? null,
                    'checked_at' => now(),
                ]);
            }

            try {
                ServiceCheckLog::create([
                    'period_service_id' => $periodService->id,
                    'tester_id' => $user->id,
                    'status' => $validated['status'],
                    'description' => $validated['description'] ?? null,
                    'attachment' => $attachmentPath,
                    'attachment_data' => $attachmentData,
                    'attachment_mime' => $attachmentMime,
                ]);
            } catch (\Throwable $logErr) {
                ServiceCheckLog::create([
                    'period_service_id' => $periodService->id,
                    'tester_id' => $user->id,
                    'status' => $validated['status'],
                    'description' => $validated['description'] ?? null,
                ]);
            }

            // Catat atau perbarui di tabel service_issues (layanan_terkendala)
            try {
                if ($validated['status'] === 'gangguan') {
                    ServiceIssue::updateOrCreate(
                        ['period_service_id' => $periodService->id],
                        [
                            'layanan_id'      => $periodService->service_id,
                            'periode_id'      => $periodService->period_id,
                            'has_notif'       => false,
                            'has_solved'      => false,
                            'handling_status' => 'belum_ditindaklanjuti',
                        ]
                    );
                } else if ($validated['status'] === 'normal') {
                    // Jika sebelumnya ada catatan kendala pada penugasan ini, tandai sudah selesai/teratasi
                    ServiceIssue::where('period_service_id', $periodService->id)->update([
                        'has_solved'      => true,
                        'handling_status' => 'selesai',
                    ]);
                }
            } catch (\Throwable $issueErr) {
                \Illuminate\Support\Facades\Log::warning('Failed updating service_issues in tester submit: ' . $issueErr->getMessage());
            }

            // Auto-complete: jika seluruh layanan pada periode ini sudah berstatus normal (0 pending & 0 gangguan), ubah status periode menjadi 'selesai'
            $hasPendingOrIssue = PeriodService::where('period_id', $periodService->period_id)
                ->whereIn('status', ['pending', 'gangguan'])
                ->exists();

            if (!$hasPendingOrIssue) {
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

            return back()->with('error', 'Terjadi kesalahan saat menyimpan hasil pengecekan: ' . $e->getMessage());
        }
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
        $logCount = $logs->count();

        foreach ($logs as $idx => $log) {
            $isFirst = ($idx === 0);
            $createdAt = $log->created_at ? Carbon::parse($log->created_at) : null;
            if ($isFirst && $createdAt) {
                $firstCheckAt = $createdAt;
            }

            // Tentukan label tahapan
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
                'attachment_mime' => $log->attachment_mime ?? $ps->attachment_mime ?? null,
                'attachment_name' => $log->attachment ? basename($log->attachment) : null,
                'attachment_url' => $log->attachment ? route('periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                'created_at' => $createdAt ? $createdAt->format('d M Y, H:i') : '-',
                'created_at_human' => $createdAt ? $createdAt->diffForHumans() : null,
                'timestamp' => $createdAt ? $createdAt->timestamp : ($idx * 10),
            ]);
        }

        // 2. Sertakan tahapan tindak lanjut penanganan oleh Admin jika ada
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

        // Urutkan kronologis (dari awal kendala -> penanganan admin -> log pengecekan baru)
        $sorted = $events->sortBy('timestamp')->values();

        // Tandai item terakhir sebagai 'is_latest' dan berikan nomor langkah
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
}
