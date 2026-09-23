<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\PeriodService;
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
                $query->where(function($q) use ($search) {
                    $q->where('service_name', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
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
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total'              => Service::count(),
            'aktif'              => Service::where('status', Service::STATUS_AKTIF)->count(),
            'nonaktif'           => Service::where('status', Service::STATUS_TIDAK_AKTIF)->count(),
            'butuhkonfirmasi'    => Service::where('status', Service::STATUS_BUTUH_KONFIRMASI)->count(),
        ];

        $levels = collect(['Level 1', 'Level 2', 'Level 3']);

        return Inertia::render('Admin/MasterLayanan', [
            'services' => $services,
            'stats'    => $stats,
            'search'   => $search,
            'status'   => $status,
            'level'    => $level,
            'levels'   => $levels,
            'flash'    => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
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
}
