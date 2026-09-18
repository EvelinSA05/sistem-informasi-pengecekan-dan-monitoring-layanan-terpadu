<?php

namespace App\Http\Controllers;

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return Inertia::render('Dashboard');
        }

        // Tester Dashboard Data
        $activePeriod = CheckingPeriod::where('status', 'proses')->orderBy('date', 'desc')->first();
        
        $assignedServices = [];
        $stats = [
            'checked' => 0,
            'pending' => 0,
            'issues' => 0,
            'need_confirmation' => 0
        ];

        if ($activePeriod) {
            $services = PeriodService::with('service')
                ->where('period_id', $activePeriod->id)
                ->where('tester_id', $user->id)
                ->get();

            $stats['checked'] = $services->where('status', 'normal')->count() + $services->where('status', 'gangguan')->count();
            $stats['pending'] = $services->where('status', 'pending')->count();
            $stats['issues']  = $services->where('status', 'gangguan')->count();

            $assignedServices = $services->map(function ($ps) {
                return [
                    'id' => $ps->id, // period_service ID
                    'service_id' => $ps->service->id,
                    'name' => $ps->service->service_name,
                    'category' => $ps->service->category ?? 'Umum',
                    'url' => $ps->service->service_url,
                    'status' => $ps->status,
                    'deskripsi_layanan' => $ps->service->deskripsi_layanan,
                    'tester_instruction' => $ps->service->tester_instruction,
                    'last_description' => $ps->description,
                ];
            })->values();
        }

        return Inertia::render('Dashboard', [
            'activePeriod' => $activePeriod ? Carbon::parse($activePeriod->date)->format('F Y') : null,
            'assignedServices' => $assignedServices,
            'stats' => $stats,
        ]);
    }

    public function submitCheck(Request $request, PeriodService $periodService)
    {
        $request->validate([
            'status' => 'required|in:normal,gangguan',
            'description' => 'nullable|string'
        ]);

        $periodService->update([
            'status' => $request->status,
            'description' => $request->description,
            'checked_at' => now(),
        ]);

        return back()->with('success', 'Hasil pengecekan berhasil disimpan.');
    }
}
