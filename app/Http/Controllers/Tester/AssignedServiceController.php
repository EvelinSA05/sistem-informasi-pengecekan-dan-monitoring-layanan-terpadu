<?php

namespace App\Http\Controllers\Tester;

use App\Http\Controllers\Controller;
use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AssignedServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $activePeriod = CheckingPeriod::where('status', 'proses')->orderBy('date', 'desc')->first();

        $assignedServices = [];
        
        if ($activePeriod) {
            $services = PeriodService::with('service')
                ->where('period_id', $activePeriod->id)
                ->where('tester_id', $user->id)
                ->get();

            $assignedServices = $services->map(function ($ps) {
                return [
                    'id'               => $ps->id,
                    'service_id'       => $ps->service->id,
                    'name'             => $ps->service->service_name,
                    'category'         => $ps->service->category ?? 'Umum',
                    'url'              => $ps->service->service_url,
                    'status'           => $ps->status,
                    'deskripsi_layanan'=> $ps->service->deskripsi_layanan,
                    'tester_instruction'=> $ps->service->tester_instruction,
                    'last_description' => $ps->description,
                ];
            })->values();
        }

        return Inertia::render('Tester/AssignedServices', [
            'activePeriod'    => $activePeriod
                ? Carbon::parse($activePeriod->date)->format('F Y')
                : null,
            'assignedServices'=> $assignedServices,
        ]);
    }

    public function submitCheck(Request $request, PeriodService $periodService)
    {
        $request->validate([
            'status'      => 'required|in:normal,gangguan',
            'description' => 'nullable|string',
        ]);

        $periodService->update([
            'status'      => $request->status,
            'description' => $request->description,
            'checked_at'  => now(),
        ]);

        return back()->with('success', 'Hasil pengecekan berhasil disimpan.');
    }
}
