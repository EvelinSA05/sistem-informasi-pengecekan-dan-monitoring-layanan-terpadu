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

        $services = Service::query()
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
            'total'              => Service::count(),
            'aktif'              => Service::where('status', Service::STATUS_AKTIF)->count(),
            'nonaktif'           => Service::where('status', Service::STATUS_TIDAK_AKTIF)->count(),
            'butuhkonfirmasi'    => Service::where('status', Service::STATUS_BUTUH_KONFIRMASI)->count(),
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

            $message = "Sinkronisasi berhasil! Total {$result['total']} layanan dari Majadigi diproses ({$result['created']} data baru ditambahkan, {$result['updated']} data diperbarui).";
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
            $history = \App\Models\ServiceCheckLog::with(['periodService.period', 'tester'])
                ->whereHas('periodService', function ($query) use ($service) {
                    $query->where('service_id', $service->id);
                })
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($log) {
                    return [
                        'id'          => $log->id,
                        'period_date' => $log->periodService?->period?->date ? \Carbon\Carbon::parse($log->periodService->period->date)->format('d M Y') : '-',
                        'tester'      => $log->tester ? $log->tester->name : 'Unknown',
                        'status'      => $log->status,
                        'description' => $log->description,
                        'checked_at'  => $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') : '-',
                    ];
                });

            return response()->json($history);
        } catch (\Throwable $e) {
            Log::error('Gagal memuat riwayat layanan: ' . $e->getMessage(), [
                'service_id' => $service->id,
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Dapatkan daftar riwayat kendala khusus untuk layanan ini.
     */
    /**
     * Memuat history kendala untuk layanan tertentu (Tugas 12).
     */
    public function issues(Service $service)
    {
        try {
            // 1. Pastikan tabel service_issues ada di PostgreSQL
            if (!\Illuminate\Support\Facades\Schema::hasTable('service_issues')) {
                try {
                    \Illuminate\Support\Facades\DB::statement("
                        CREATE TABLE IF NOT EXISTS service_issues (
                            id BIGSERIAL PRIMARY KEY,
                            layanan_id BIGINT NOT NULL,
                            periode_id BIGINT NOT NULL,
                            period_service_id BIGINT NOT NULL,
                            has_notif BOOLEAN DEFAULT FALSE,
                            has_solved BOOLEAN DEFAULT FALSE,
                            created_at TIMESTAMP NULL,
                            updated_at TIMESTAMP NULL
                        )
                    ");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Direct table creation failed: ' . $e->getMessage());
                }
            }

            // 2. Kumpulkan semua ID yang merujuk ke layanan ini (mengatasi variasi ID akibat duplikasi seeder / sinkronisasi Majadigi)
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
            $troubledPeriodServices = PeriodService::with(['period', 'tester', 'service'])
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
                    'periodService.tester',
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
                        'attachment'            => null,
                        'has_notif'             => (bool) $issue->has_notif,
                        'has_solved'            => (bool) $issue->has_solved,
                        'has_solved_label'      => $issue->has_solved ? 'sudah tindak lanjuti' : 'belum tindak lanjuti',
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
                        'attachment'            => null,
                        'has_notif'             => false,
                        'has_solved'            => false,
                        'has_solved_label'      => 'belum tindak lanjuti',
                        'tester_name'           => $ps->tester?->name ?? 'Tester',
                        'checked_at'            => $ps->checked_at ? \Carbon\Carbon::parse($ps->checked_at)->format('d M Y, H:i') : null,
                    ];
                }
            }

            return response()->json($issues)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        } catch (\Throwable $e) {
            Log::error('Gagal memuat history kendala layanan: ' . $e->getMessage(), [
                'service_id' => $service->id,
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Toggle status tindak lanjut kendala (has_solved).
     */
    public function toggleIssueSolved($issueId)
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

            $issue->update([
                'has_solved' => !$issue->has_solved,
            ]);

            return response()->json([
                'success'    => true,
                'has_solved' => (bool) $issue->has_solved,
                'message'    => $issue->has_solved 
                    ? 'Kendala telah ditandai sudah ditindaklanjuti.' 
                    : 'Status kendala diubah menjadi belum ditindaklanjuti.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status tindak lanjut: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }
}
