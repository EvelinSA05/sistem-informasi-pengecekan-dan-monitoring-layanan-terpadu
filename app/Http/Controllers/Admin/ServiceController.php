<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $level  = $request->get('level', '');

        $services = Service::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = strtolower($request->input('search'));
                $query->where(function($q) use ($search) {
                    $q->whereRaw('LOWER(service_name) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === '1');
            })
            ->when($request->filled('level'), function ($query) use ($request) {
                $query->where('level', $request->input('level'));
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total'    => Service::count(),
            'aktif'    => Service::where('is_active', true)->count(),
            'nonaktif' => Service::where('is_active', false)->count(),
        ];

        // Level options: predefined list consistent with deployment.
        // Previously used DISTINCT query which returned empty on local (all records have level=NULL).
        // Using a fixed list ensures the dropdown is always available regardless of existing data.
        $levels = collect(['Level 1', 'Level 2', 'Level 3']);

        return Inertia::render('Admin/MasterLayanan', [
            'services' => $services,
            'stats'    => $stats,
            'search'   => $search,
            'status'   => $status,
            'level'    => $level,
            'levels'   => $levels,
        ]);
    }

    /**
     * Simpan layanan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'level'        => 'nullable|string|max:50',
            'service_url'  => 'nullable|url',
            'is_active'    => 'required|boolean',
            'notes'        => 'nullable|string',
            'deskripsi_layanan'  => 'nullable|string',
            'tester_instruction' => 'nullable|string',
        ]);

        Service::create([
            'source_service_id' => Str::uuid()->toString(), // Auto generate external ID for manual entries
            'service_name'      => $request->service_name,
            'category'          => $request->category,
            'level'             => $request->level,
            'service_url'       => $request->service_url,
            'is_active'         => $request->is_active,
            'notes'             => $request->notes,
            'deskripsi_layanan'  => $request->deskripsi_layanan,
            'tester_instruction' => $request->tester_instruction,
            'icon'              => 'layanan', // default icon
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Layanan berhasil ditambahkan.');
    }

    /**
     * Update data layanan.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'level'        => 'nullable|string|max:50',
            'service_url'  => 'nullable|url',
            'is_active'    => 'required|boolean',
            'notes'        => 'nullable|string',
            'deskripsi_layanan'  => 'nullable|string',
            'tester_instruction' => 'nullable|string',
        ]);

        $service->update([
            'service_name' => $request->service_name,
            'category'     => $request->category,
            'level'        => $request->level,
            'service_url'  => $request->service_url,
            'is_active'    => $request->is_active,
            'notes'        => $request->notes,
            'deskripsi_layanan'  => $request->deskripsi_layanan,
            'tester_instruction' => $request->tester_instruction,
        ]);

        // Jika layanan dinonaktifkan, cabut layanan tersebut dari semua periode yang masih dalam proses
        if (!$request->is_active) {
            PeriodService::where('service_id', $service->id)
                ->whereHas('period', function ($query) {
                    $query->where('status', 'proses');
                })->delete();
        }

        return redirect()->route('admin.services.index')
            ->with('success', 'Layanan berhasil diperbarui.');
    }

    /**
     * Hapus layanan.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Layanan berhasil dihapus.');
    }

    /**
     * Dapatkan riwayat pengecekan layanan.
     */
    public function history(Service $service)
    {
        $history = \App\Models\ServiceCheckLog::with(['periodService.period', 'tester'])
            ->whereHas('periodService', function ($query) use ($service) {
                $query->where('service_id', $service->id);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'id'          => $log->id,
                    'period_date' => \Carbon\Carbon::parse($log->periodService->period->date)->format('d M Y'),
                    'tester'      => $log->tester ? $log->tester->name : 'Unknown',
                    'status'      => $log->status,
                    'description' => $log->description,
                    'checked_at'  => \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s'),
                ];
            });

        return response()->json($history);
    }
}
