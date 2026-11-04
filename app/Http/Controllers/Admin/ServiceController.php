<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\PeriodService;
use App\Models\ServiceIssue;
use App\Services\MajadigiApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Tampilkan daftar master layanan.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:aktif,tidak_aktif,butuh_konfirmasi',
            'level'  => 'nullable|string|max:50',
        ]);

        $search = (string) $request->get('search', '');
        $status = (string) $request->get('status', '');
        $level  = (string) $request->get('level', '');

        // Master Layanan hanya menampilkan data yang sudah tersinkronisasi dari web Majadigi
        $baseQuery = Service::query()->whereNotNull('synced_at');

        $services = (clone $baseQuery)
            ->withCount('periodServices')
            ->when(!empty($search), function ($query) use ($search) {
                $searchTerm = strtolower($search);
                $query->where(function($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(service_name) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchTerm}%"]);
                });
            })
            ->when(!empty($status) && in_array($status, Service::VALID_STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(!empty($level), function ($query) use ($level) {
                $query->where('level', $level);
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 5))
            ->withQueryString();

        $stats = [
            'total'              => (clone $baseQuery)->count(),
            'aktif'              => (clone $baseQuery)->where('status', Service::STATUS_AKTIF)->count(),
            'nonaktif'           => (clone $baseQuery)->where('status', Service::STATUS_TIDAK_AKTIF)->count(),
            'butuhkonfirmasi'    => (clone $baseQuery)->where('status', Service::STATUS_BUTUH_KONFIRMASI)->count(),
        ];

        $levels = collect(['Level 1', 'Level 2', 'Level 3']);

        $lastSyncedAt = Service::whereNotNull('synced_at')->max('synced_at');
        $lastSyncedFormatted = $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : null;

        return Inertia::render('Admin/MasterLayanan', [
            'services'          => $services,
            'stats'             => $stats,
            'search'            => $search,
            'status'            => $status,
            'level'             => $level,
            'levels'            => $levels,
            'last_synced_at'    => $lastSyncedFormatted,
            'flash'             => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }

    /**
     * Sinkronisasi data layanan dari API Majadigi.
     */
    public function syncMajadigi(MajadigiApiService $apiService): RedirectResponse
    {
        try {
            $result = $apiService->syncServices();

            $deletedMsg = !empty($result['deleted']) ? ", {$result['deleted']} data non-sinkronisasi berhasil dibersihkan" : "";
            $message = "Sinkronisasi berhasil! Total {$result['total']} layanan dari Majadigi diproses ({$result['created']} data baru ditambahkan, {$result['updated']} data diperbarui{$deletedMsg}).";
            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Majadigi sync error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal melakukan sinkronisasi layanan: ' . $e->getMessage());
        }
    }

    /**
     * Simpan layanan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_name'       => 'required|string|max:255',
            'category'           => 'nullable|string|max:100',
            'level'              => 'nullable|string|max:50',
            'service_url'        => 'nullable|url|max:2048',
            'status'             => 'required|in:aktif,tidak_aktif,butuh_konfirmasi',
            'notes'              => 'nullable|string|max:10000',
            'deskripsi_layanan'  => 'nullable|string|max:10000',
            'tester_instruction' => 'nullable|string|max:10000',
        ]);

        try {
            $isActive = $validated['status'] === Service::STATUS_AKTIF;

            Service::create([
                'source_service_id'  => Str::uuid()->toString(),
                'service_name'       => $validated['service_name'],
                'category'           => $validated['category'] ?? null,
                'level'              => $validated['level'] ?? null,
                'service_url'        => $validated['service_url'] ?? null,
                'status'             => $validated['status'],
                'is_active'          => $isActive,
                'notes'              => $validated['notes'] ?? null,
                'deskripsi_layanan'  => $validated['deskripsi_layanan'] ?? null,
                'tester_instruction' => $validated['tester_instruction'] ?? null,
                'icon'               => 'layanan',
            ]);

            return redirect()->route('admin.services.index')
                ->with('success', 'Layanan berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan layanan baru: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.services.index')
                ->with('error', 'Terjadi kesalahan saat menyimpan layanan. Silakan coba lagi.');
        }
    }

    /**
     * Update data layanan.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'service_name'       => 'required|string|max:255',
            'category'           => 'nullable|string|max:100',
            'level'              => 'nullable|string|max:50',
            'service_url'        => 'nullable|url|max:2048',
            'status'             => 'required|in:aktif,tidak_aktif,butuh_konfirmasi',
            'notes'              => 'nullable|string|max:10000',
            'deskripsi_layanan'  => 'nullable|string|max:10000',
            'tester_instruction' => 'nullable|string|max:10000',
        ]);

        try {
            $isActive = $validated['status'] === Service::STATUS_AKTIF;

            $service->update([
                'service_name'       => $validated['service_name'],
                'category'           => $validated['category'] ?? null,
                'level'              => $validated['level'] ?? null,
                'service_url'        => $validated['service_url'] ?? null,
                'status'             => $validated['status'],
                'is_active'          => $isActive,
                'notes'              => $validated['notes'] ?? null,
                'deskripsi_layanan'  => $validated['deskripsi_layanan'] ?? null,
                'tester_instruction' => $validated['tester_instruction'] ?? null,
            ]);

            // Jika layanan dinonaktifkan, cabut layanan tersebut dari semua periode yang masih dalam proses
            if (!$isActive) {
                PeriodService::where('service_id', $service->id)
                    ->whereHas('period', function ($query) {
                        $query->where('status', 'proses');
                    })->delete();
            }

            return redirect()->route('admin.services.index')
                ->with('success', 'Layanan berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui layanan: ' . $e->getMessage(), [
                'service_id' => $service->id,
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.services.index')
                ->with('error', 'Terjadi kesalahan saat memperbarui layanan. Silakan coba lagi.');
        }
    }

    /**
     * Hapus layanan.
     */
    public function destroy(Service $service): RedirectResponse
    {
        // 1. Layanan yang sudah masuk periode pengecekan -> TIDAK BISA DIHAPUS
        $isInPeriod = \App\Models\PeriodService::where('service_id', $service->id)->exists();
        if ($isInPeriod) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Layanan tidak dapat dihapus karena sudah masuk ke dalam periode pengecekan.');
        }

        try {
            $service->delete();

            return redirect()->route('admin.services.index')
                ->with('success', 'Layanan berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus layanan: ' . $e->getMessage(), [
                'service_id' => $service->id,
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.services.index')
                ->with('error', 'Gagal menghapus layanan. Layanan mungkin masih memiliki riwayat atau terkait data lain.');
        }
    }

    /**
     * Dapatkan riwayat pengecekan layanan.
     */
    public function history(Service $service)
    {
        try {
            $logs = collect();

            // 1. Ambil dari ServiceCheckLog jika ada (tanpa blob attachment_data)
            try {
                $checkLogs = \App\Models\ServiceCheckLog::withoutAttachmentData()
                    ->with([
                        'periodService' => fn($q) => $q->withoutAttachmentData()->with('period'),
                        'tester'
                    ])
                    ->whereHas('periodService', function ($query) use ($service) {
                        $query->where('service_id', $service->id);
                    })
                    ->orderByDesc('created_at')
                    ->get();

                foreach ($checkLogs as $log) {
                    $ps = $log->periodService;
                    $createdAt = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : null;
                    $logs->push([
                        'id'          => 'log_' . $log->id,
                        'period_service_id' => $log->period_service_id,
                        'period_date' => $ps?->period?->date ? \Carbon\Carbon::parse($ps->period->date)->format('d M Y') : '-',
                        'tester'      => $log->tester?->name ?? ($ps?->tester?->name ?? 'Tester'),
                        'status'      => $log->status,
                        'description' => $log->description,
                        'attachment'  => $log->attachment,
                        'attachment_name' => $log->attachment ? basename($log->attachment) : null,
                        'attachment_url'  => $log->attachment && $ps ? route('periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                        'checked_at'  => $createdAt ? $createdAt->format('d M Y H:i') : '-',
                        'timestamp'   => $createdAt ? $createdAt->timestamp : 0,
                    ]);
                }
            } catch (\Throwable $logEx) {
                Log::warning('Gagal query ServiceCheckLog di history: ' . $logEx->getMessage());
            }

            // 2. Ambil dari PeriodService yang telah diperiksa (checked_at tidak null)
            try {
                $periodServices = \App\Models\PeriodService::withoutAttachmentData()
                    ->with(['period', 'tester'])
                    ->where('service_id', $service->id)
                    ->whereNotNull('checked_at')
                    ->where('status', '!=', 'pending')
                    ->orderByDesc('checked_at')
                    ->get();

                foreach ($periodServices as $ps) {
                    $checkedAt = $ps->checked_at ? \Carbon\Carbon::parse($ps->checked_at) : null;
                    $ts = $checkedAt ? $checkedAt->timestamp : 0;

                    // Hindari duplikasi jika log di atas sudah mencatat pemeriksaan yang sama
                    $isDuplicate = $logs->contains(function ($item) use ($ps, $ts) {
                        return $item['period_service_id'] == $ps->id && abs($item['timestamp'] - $ts) < 60;
                    });

                    if (!$isDuplicate) {
                        $logs->push([
                            'id'          => 'ps_' . $ps->id,
                            'period_service_id' => $ps->id,
                            'period_date' => $ps->period?->date ? \Carbon\Carbon::parse($ps->period->date)->format('d M Y') : '-',
                            'tester'      => $ps->tester?->name ?? 'Tester',
                            'status'      => $ps->status,
                            'description' => $ps->description,
                            'attachment'  => $ps->attachment,
                            'attachment_name' => $ps->attachment ? basename($ps->attachment) : null,
                            'attachment_url'  => $ps->attachment ? route('periods.attachment.download', ['periodService' => $ps->id, 'inline' => 1]) : null,
                            'checked_at'  => $checkedAt ? $checkedAt->format('d M Y H:i') : '-',
                            'timestamp'   => $ts,
                        ]);
                    }
                }
            } catch (\Throwable $psEx) {
                Log::warning('Gagal query PeriodService di history: ' . $psEx->getMessage());
            }

            $sortedHistory = $logs->sortByDesc('timestamp')->values();

            return response()->json($sortedHistory);
        } catch (\Throwable $e) {
            Log::error('Gagal memuat riwayat layanan: ' . $e->getMessage(), [
                'service_id' => $service->id,
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Tampilkan halaman detail master layanan.
     */
    public function show(Service $service): Response
    {
        $service->loadCount('periodServices');

        $issues = $this->fetchServiceIssues($service);

        return Inertia::render('Admin/Services/Show', [
            'service' => [
                'id'                    => $service->id,
                'source_service_id'     => $service->source_service_id,
                'service_name'          => $service->service_name,
                'category'              => $service->category,
                'level'                 => $service->level,
                'icon'                  => $service->icon,
                'service_url'           => $service->service_url,
                'deskripsi_layanan'     => $service->deskripsi_layanan,
                'tester_instruction'    => $service->tester_instruction,
                'is_active'             => (bool) $service->is_active,
                'status'                => $service->status,
                'notes'                 => $service->notes,
                'synced_at'             => $service->synced_at ? \Carbon\Carbon::parse($service->synced_at)->format('d M Y, H:i') : null,
                'created_at'            => $service->created_at ? \Carbon\Carbon::parse($service->created_at)->format('d M Y, H:i') : null,
                'updated_at'            => $service->updated_at ? \Carbon\Carbon::parse($service->updated_at)->format('d M Y, H:i') : null,
                'period_services_count' => $service->period_services_count,
            ],
            'issues'  => $issues,
            'flash'   => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }

    /**
     * Dapatkan daftar riwayat kendala khusus untuk layanan ini (API).
     */
    public function issues(Service $service)
    {
        $issues = $this->fetchServiceIssues($service);

        return response()->json($issues)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Helper untuk mengambil data riwayat kendala layanan.
     */
    public function fetchServiceIssues(Service $service): array
    {
        try {
            // Kumpulkan semua ID yang merujuk ke layanan ini (mengatasi variasi ID akibat duplikasi seeder / sinkronisasi Majadigi)
            $serviceName = trim($service->service_name);
            $serviceIds = Service::where(function($q) use ($service, $serviceName) {
                $q->where('id', $service->id)
                  ->orWhereRaw('LOWER(TRIM(service_name)) = ?', [strtolower($serviceName)]);
                if (!empty($service->source_service_id)) {
                    $q->orWhere('source_service_id', $service->source_service_id);
                }
            })->pluck('id')->toArray();

            if (empty($serviceIds)) {
                $serviceIds = [$service->id];
            }

            // 3. Cari seluruh riwayat penugasan berstatus kendala/gangguan untuk seluruh ID layanan yang cocok
            $troubledPeriodServices = PeriodService::withoutAttachmentData()
                ->with(['period', 'tester', 'service'])
                ->where(function($q) use ($serviceIds, $serviceName) {
                    $q->whereIn('service_id', $serviceIds)
                      ->orWhereHas('service', function($sq) use ($serviceName) {
                          $sq->whereRaw('LOWER(TRIM(service_name)) = ?', [strtolower($serviceName)]);
                      });
                })
                ->whereIn('status', ['gangguan', 'kendala', 'tidak_aktif', 'error'])
                ->orderByDesc('checked_at')
                ->get();

            // 4. Sinkronkan secara otomatis ke tabel service_issues jika belum tercatat
            if (\Illuminate\Support\Facades\Schema::hasTable('service_issues')) {
                foreach ($troubledPeriodServices as $ps) {
                    try {
                        ServiceIssue::firstOrCreate(
                            ['period_service_id' => $ps->id],
                            [
                                'layanan_id'  => $ps->service_id,
                                'periode_id'  => $ps->period_id,
                                'has_notif'   => false,
                                'has_solved'  => false,
                                'created_at'  => $ps->checked_at ?? now(),
                                'updated_at'  => $ps->checked_at ?? now(),
                            ]
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed sync to service_issues: ' . $e->getMessage());
                    }
                }
            }

            $issues = [];
            // 5. Ambil dari tabel service_issues yang berelasi
            if (\Illuminate\Support\Facades\Schema::hasTable('service_issues')) {
                $rawIssues = ServiceIssue::with([
                    'period',
                    'periodService' => fn($q) => $q->withoutAttachmentData()->with('tester'),
                    'service'
                ])
                ->where(function($q) use ($serviceIds, $serviceName) {
                    $q->whereIn('layanan_id', $serviceIds)
                      ->orWhereHas('service', function($sq) use ($serviceName) {
                          $sq->whereRaw('LOWER(TRIM(service_name)) = ?', [strtolower($serviceName)]);
                      })
                      ->orWhereHas('periodService', function($psq) use ($serviceIds, $serviceName) {
                          $psq->whereIn('service_id', $serviceIds)
                              ->orWhereHas('service', function($sq) use ($serviceName) {
                                  $sq->whereRaw('LOWER(TRIM(service_name)) = ?', [strtolower($serviceName)]);
                              });
                      });
                })
                ->orderByDesc('created_at')
                ->get()
                ->unique('period_service_id');

                foreach ($rawIssues as $issue) {
                    $rawDate = $issue->period?->date ?: ($issue->periodService?->checked_at ?: $issue->created_at);
                    $formattedDate = '-';
                    if ($rawDate) {
                        $cDate = \Carbon\Carbon::parse($rawDate);
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        $formattedDate = $cDate->format('d') . ' ' . ($months[(int) $cDate->format('n')] ?? $cDate->format('F'));
                        if ($cDate->format('Y') !== \Carbon\Carbon::now()->format('Y')) {
                            $formattedDate .= ' ' . $cDate->format('Y');
                        }
                    }

                    $issues[] = [
                        'id'                    => $issue->id,
                        'periode_id'            => $issue->periode_id,
                        'period_service_id'     => $issue->period_service_id,
                        'period_date'           => $formattedDate,
                        'status'                => 'tidak aktif',
                        'period_service_status' => $issue->periodService?->status ?? 'gangguan',
                        'catatan'               => $issue->periodService?->description ?: 'attachment',
                        'attachment'            => $issue->periodService?->attachment ? asset('storage/' . $issue->periodService->attachment) : null,
                        'attachment_name'       => $issue->periodService?->attachment ? basename($issue->periodService->attachment) : null,
                        'has_notif'             => (bool) $issue->has_notif,
                        'has_solved'            => (bool) $issue->has_solved,
                        'handling_status'       => $issue->handling_status ?? ($issue->has_solved ? 'selesai' : 'belum_ditindaklanjuti'),
                        'admin_note'            => $issue->admin_note ?? $issue->periodService?->admin_note,
                        'has_solved_label'      => match($issue->handling_status) {
                            'perlu_pengecekan_ulang' => 'perlu pengecekan ulang',
                            'selesai' => 'sudah tindak lanjuti',
                            default => $issue->has_solved ? 'sudah tindak lanjuti' : 'belum tindak lanjuti',
                        },
                        'tester_name'           => $issue->periodService?->tester?->name ?? 'Tester',
                        'checked_at'            => $issue->periodService?->checked_at ? \Carbon\Carbon::parse($issue->periodService->checked_at)->format('d M Y, H:i') : null,
                    ];
                }
            }

            // 6. Fallback jika tabel service_issues masih kosong tapi period_services mencatat kendala
            if (empty($issues) && $troubledPeriodServices->isNotEmpty()) {
                foreach ($troubledPeriodServices as $ps) {
                    $rawDate = $ps->period?->date ?: ($ps->checked_at ?: $ps->created_at);
                    $formattedDate = '-';
                    if ($rawDate) {
                        $cDate = \Carbon\Carbon::parse($rawDate);
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        $formattedDate = $cDate->format('d') . ' ' . ($months[(int) $cDate->format('n')] ?? $cDate->format('F'));
                        if ($cDate->format('Y') !== \Carbon\Carbon::now()->format('Y')) {
                            $formattedDate .= ' ' . $cDate->format('Y');
                        }
                    }

                    $issues[] = [
                        'id'                    => $ps->id,
                        'periode_id'            => $ps->period_id,
                        'period_service_id'     => $ps->id,
                        'period_date'           => $formattedDate,
                        'status'                => 'tidak aktif',
                        'period_service_status' => $ps->status,
                        'catatan'               => $ps->description ?: 'attachment',
                        'admin_note'            => $ps->admin_note,
                        'handling_status'       => 'belum_ditindaklanjuti',
                        'attachment'            => $ps->attachment ? asset('storage/' . $ps->attachment) : null,
                        'attachment_name'       => $ps->attachment ? basename($ps->attachment) : null,
                        'has_notif'             => false,
                        'has_solved'            => false,
                        'has_solved_label'      => 'belum tindak lanjuti',
                        'tester_name'           => $ps->tester?->name ?? 'Tester',
                        'checked_at'            => $ps->checked_at ? \Carbon\Carbon::parse($ps->checked_at)->format('d M Y, H:i') : null,
                    ];
                }
            }

            return $issues;
        } catch (\Throwable $e) {
            Log::error('Gagal memuat history kendala layanan: ' . $e->getMessage(), [
                'service_id' => $service->id,
            ]);

            return [];
        }
    }

    /**
     * Toggle status tindak lanjut kendala (has_solved) dan perbarui catatan jika disediakan.
     */
    public function toggleIssueSolved(Request $request, $issueId)
    {
        try {
            $issue = ServiceIssue::find($issueId);
            if (!$issue) {
                // Jika dipanggil dengan period_service_id fallback
                $issue = ServiceIssue::firstOrCreate(
                    ['period_service_id' => $issueId],
                    [
                        'layanan_id' => PeriodService::find($issueId)?->service_id ?? 0,
                        'periode_id' => PeriodService::find($issueId)?->period_id ?? 0,
                        'has_notif'  => false,
                        'has_solved' => false,
                    ]
                );
            }

            $periodService = PeriodService::find($issue->period_service_id);

            // Simpan perubahan catatan penanganan kendala jika dikirim dari request
            $adminNote = null;
            if ($request->has('description') || $request->has('admin_note')) {
                $validated = $request->validate([
                    'description' => 'nullable|string|max:5000',
                    'admin_note'  => 'nullable|string|max:5000',
                ], [
                    'description.max' => 'Catatan kendala maksimal 5000 karakter.',
                    'admin_note.max'  => 'Catatan admin maksimal 5000 karakter.',
                ]);

                $adminNote = $validated['description'] ?? $validated['admin_note'] ?? null;

                if ($periodService && $adminNote !== null) {
                    $periodService->update([
                        'admin_note' => $adminNote,
                    ]);
                }
            }

            // Tentukan status has_solved: jika diberikan secara eksplisit gunakan itu, jika tidak toggle
            if ($request->has('has_solved')) {
                $nextSolved = $request->boolean('has_solved');
            } else {
                $nextSolved = !$issue->has_solved;
            }

            // Tentukan handling_status:
            // Jika ditandai selesai oleh admin, status penanganan menjadi 'perlu_pengecekan_ulang' (bukan langsung selesai)
            // Hasil pengecekan layanan (status) tetap 'gangguan' sampai tester melakukan pengecekan ulang.
            $handlingStatus = $nextSolved ? 'perlu_pengecekan_ulang' : 'belum_ditindaklanjuti';

            $updateData = [
                'has_solved'      => $nextSolved,
                'handling_status' => $handlingStatus,
            ];

            if ($adminNote !== null) {
                $updateData['admin_note'] = $adminNote;
            }

            if ($nextSolved) {
                $updateData['admin_handled_at'] = now();
                $updateData['admin_id'] = auth()->id();
            }

            $issue->update($updateData);

            return response()->json([
                'success'         => true,
                'has_solved'      => (bool) $issue->has_solved,
                'handling_status' => $issue->handling_status,
                'admin_note'      => $issue->admin_note,
                'description'     => $periodService?->description ?? null,
                'message'         => $issue->has_solved 
                    ? 'Kendala telah ditandai selesai. Status penanganan: Perlu Pengecekan Ulang.' 
                    : 'Status kendala diubah menjadi belum ditindaklanjuti.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->validator->errors()->first(),
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status tindak lanjut: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }
}
