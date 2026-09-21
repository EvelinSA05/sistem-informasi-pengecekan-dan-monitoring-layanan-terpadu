<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
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
                // Filter menggunakan kolom status (string 3 nilai)
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('level'), function ($query) use ($request) {
                $query->where('level', $request->input('level'));
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total'              => Service::count(),
            'aktif'              => Service::where('status', 'aktif')->count(),
            'nonaktif'           => Service::where('status', 'tidak_aktif')->count(),
            'butuhkonfirmasi'    => Service::where('status', 'butuh_konfirmasi')->count(),
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
            'status'       => 'required|in:aktif,tidak_aktif,butuh_konfirmasi',
            'notes'        => 'nullable|string',
            'deskripsi_layanan'  => 'nullable|string',
            'tester_instruction' => 'nullable|string',
        ]);

        // Sinkronisasi is_active dari status untuk backward compatibility
        // (PeriodController menggunakan is_active untuk daftar layanan aktif)
        $isActive = $request->status === 'aktif';

        Service::create([
            'source_service_id' => Str::uuid()->toString(), // Auto generate external ID for manual entries
            'service_name'      => $request->service_name,
            'category'          => $request->category,
            'level'             => $request->level,
            'service_url'       => $request->service_url,
            'status'            => $request->status,
            'is_active'         => $isActive,
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
            'status'       => 'required|in:aktif,tidak_aktif,butuh_konfirmasi',
            'notes'        => 'nullable|string',
            'deskripsi_layanan'  => 'nullable|string',
            'tester_instruction' => 'nullable|string',
        ]);

        // Sinkronisasi is_active dari status untuk backward compatibility
        $isActive = $request->status === 'aktif';

        $service->update([
            'service_name' => $request->service_name,
            'category'     => $request->category,
            'level'        => $request->level,
            'service_url'  => $request->service_url,
            'status'       => $request->status,
            'is_active'    => $isActive,
            'notes'        => $request->notes,
            'deskripsi_layanan'  => $request->deskripsi_layanan,
            'tester_instruction' => $request->tester_instruction,
        ]);

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
        $history = \App\Models\PeriodService::with(['period', 'tester'])
            ->where('service_id', $service->id)
            ->whereNotNull('checked_at')
            ->orderByDesc('checked_at')
            ->get()
            ->map(function ($log) {
                return [
                    'id'          => $log->id,
                    'period_date' => \Carbon\Carbon::parse($log->period->date)->format('d M Y'),
                    'tester'      => $log->tester ? $log->tester->name : 'Unknown',
                    'status'      => $log->status,
                    'description' => $log->description,
                    'checked_at'  => \Carbon\Carbon::parse($log->checked_at)->format('d M Y H:i'),
                ];
            });

        return response()->json($history);
    }
}
