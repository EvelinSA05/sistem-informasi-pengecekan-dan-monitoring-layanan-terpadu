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

            return [
                'id' => $period->id,
                'date' => $carbonDate->format('Y-m-d'),
                'period_title' => $periodTitle,
                'created_at_label' => "Dibuat " . Carbon::parse($period->created_at)->format('Y-m-d'),
                'status' => $period->status,
                'testers' => $testers,
                'checked_services' => $checkedServices,
                'total_services' => $totalServices,
                'progress_percent' => $progressPercent,
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
    private function getFormData()
    {
        $testers = User::where('role', 'tester')
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nip', 'bidang'])
            ->map(function ($tester) {
                $parts = explode(' ', trim($tester->name));
                $initials = count($parts) >= 2
                    ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                    : strtoupper(substr($parts[0] ?? 'T', 0, 2));

                return [
                    'id' => $tester->id,
                    'name' => $tester->name,
                    'email' => $tester->email,
                    'nip' => $tester->nip,
                    'bidang' => $tester->bidang ?? 'Layanan Publik',
                    'initials' => $initials,
                ];
            });

        $services = Service::where('is_active', true)
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
        [$testers, $services] = $this->getFormData();

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
                'status' => $period->status,
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

        $request->validate([
            'date' => 'required|date',
            'tester_ids' => 'required|array|min:1',
            'tester_ids.*' => 'exists:users,id',
        ], [
            'date.required' => 'Tanggal pengecekan wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'tester_ids.required' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.min' => 'Pilih minimal satu tester untuk periode pengecekan.',
        ]);

        $date = Carbon::parse($request->date)->format('Y-m-d');

        if (CheckingPeriod::where('date', $date)->exists()) {
            return back()->withErrors([
                'date' => 'Periode untuk tanggal ' . $this->formatIndonesianDate(Carbon::parse($date)) . ' sudah ada.',
            ]);
        }

        DB::transaction(function () use ($date, $request) {
            $period = CheckingPeriod::create([
                'date' => $date,
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

            $services = Service::where('is_active', true)->orderBy('id')->get();
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
        $period->load([
            'creator:id,name,email',
            'periodTesters.tester:id,name,email,bidang,nip',
            'periodServices.service:id,source_service_id,service_name,category,icon,service_url',
            'periodServices.tester:id,name,email,bidang',
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

            $assignedServices = $period->periodServices->where('tester_id', $tester->id)->map(function ($ps) {
                return [
                    'id' => $ps->id,
                    'service_id' => $ps->service_id,
                    'service_name' => $ps->service?->service_name ?? 'Layanan',
                    'category' => $ps->service?->category ?? 'Umum',
                    'icon' => $ps->service?->icon ?? 'globe',
                    'service_url' => $ps->service?->service_url,
                    'status' => $ps->status,
                    'description' => $ps->description,
                    'checked_at' => $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d M Y H:i') : null,
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

        return Inertia::render('Admin/Periods/Show', [
            'period' => [
                'id' => $period->id,
                'title' => $periodTitle,
                'date' => $carbonDate->format('Y-m-d'),
                'status' => $period->status,
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
        ]);
    }

    /**
     * Memperbarui data periode (Edit form / Quick status toggle).
     */
    public function update(Request $request, CheckingPeriod $period)
    {
        // Kasus 1: Toggle status dari halaman Show
        if ($request->has('status') && !$request->has('tester_ids') && !$request->has('date') && !$request->has('tanggal_periode')) {
            $request->validate([
                'status' => 'required|in:proses,selesai',
            ]);

            $period->update([
                'status' => $request->status,
            ]);

            return back()->with('success', 'Status periode berhasil diperbarui.');
        }

        // Kasus 2: Update dari Form Edit Periode (Tanggal, Tester & Distribusi)
        if ($request->has('tanggal_periode') && !$request->has('date')) {
            $request->merge(['date' => $request->tanggal_periode]);
        }

        $request->validate([
            'date' => 'required|date',
            'tester_ids' => 'required|array|min:1',
            'tester_ids.*' => 'exists:users,id',
        ], [
            'date.required' => 'Tanggal pengecekan wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
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

        DB::transaction(function () use ($period, $date, $request) {
            $period->update([
                'date' => $date,
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
            $services = Service::where('is_active', true)->orderBy('id')->get();
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
     */
    public function destroy(CheckingPeriod $period)
    {
        $period->delete();

        return redirect()->route('admin.periods.index')->with('success', 'Periode pengecekan berhasil dihapus.');
    }

    /**
     * Export Laporan Periode ke Format Excel (CSV kompatibel Excel dengan UTF-8 BOM).
     */
    public function exportExcel(CheckingPeriod $period): StreamedResponse
    {
        $period->load([
            'creator:id,name',
            'periodServices.service',
            'periodServices.tester',
        ]);

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

        $callback = function () use ($period, $periodTitle) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header Laporan Eksekutif
            fputcsv($file, ['MAJADIGI CHECKER - DINAS KOMUNIKASI DAN INFORMATIKA PROVINSI JAWA TIMUR']);
            fputcsv($file, ['LAPORAN HASIL PENGECEKAN LAYANAN DIGITAL']);
            fputcsv($file, []);
            fputcsv($file, ['Periode Pengecekan', $periodTitle]);
            fputcsv($file, ['Status Periode', strtoupper($period->status)]);
            fputcsv($file, ['Dibuat Oleh', $period->creator?->name ?? 'Admin']);
            fputcsv($file, ['Tanggal Laporan', date('d/m/Y H:i:s')]);
            fputcsv($file, []);

            // Ringkasan Metrik
            $total = $period->periodServices->count();
            $normal = $period->periodServices->where('status', 'normal')->count();
            $issues = $period->periodServices->where('status', 'gangguan')->count();
            $pending = $period->periodServices->where('status', 'pending')->count();
            $checked = $total - $pending;
            $percent = $total > 0 ? round(($checked / $total) * 100) : 0;

            fputcsv($file, ['RINGKASAN METRIK']);
            fputcsv($file, ['Total Layanan', $total]);
            fputcsv($file, ['Layanan Normal', $normal]);
            fputcsv($file, ['Layanan Terkendala', $issues]);
            fputcsv($file, ['Belum Diperiksa', $pending]);
            fputcsv($file, ['Tingkat Kesiapan (Progress)', "{$percent}%"]);
            fputcsv($file, []);

            // Tabel Data Detail Layanan
            fputcsv($file, [
                'No',
                'ID Layanan',
                'Nama Layanan',
                'Kategori Layanan',
                'Tester Penanggung Jawab',
                'Email Tester',
                'Status Pengecekan',
                'Waktu Pengecekan',
                'Keterangan / Catatan',
            ]);

            $no = 1;
            foreach ($period->periodServices as $ps) {
                $statusLabel = match($ps->status) {
                    'normal' => 'NORMAL',
                    'gangguan' => 'KENDALA / GANGGUAN',
                    default => 'BELUM DICEK',
                };

                fputcsv($file, [
                    $no++,
                    $ps->service?->source_service_id ?? '-',
                    $ps->service?->service_name ?? '-',
                    $ps->service?->category ?? 'Umum',
                    $ps->tester?->name ?? '-',
                    $ps->tester?->email ?? '-',
                    $statusLabel,
                    $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d/m/Y H:i') : '-',
                    $ps->description ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Laporan Periode ke Tampilan Infografis / Siap Cetak PDF.
     */
    public function exportPdf(CheckingPeriod $period)
    {
        $period->load([
            'creator:id,name,email',
            'periodTesters.tester:id,name,email,bidang,nip',
            'periodServices.service:id,source_service_id,service_name,category,icon,service_url',
            'periodServices.tester:id,name,email,bidang',
        ]);

        $carbonDate = Carbon::parse($period->date);
        $periodTitle = $this->formatIndonesianDate($carbonDate);
        $monthName = $this->indonesianMonths[(int) $carbonDate->format('n')] ?? $carbonDate->format('F');
        $year = $carbonDate->format('Y');

        $totalServices = $period->periodServices->count();
        $checkedServices = $period->periodServices->filter(fn($ps) => $ps->status !== 'pending' && !empty($ps->checked_at))->count();
        $normalServices = $period->periodServices->where('status', 'normal')->count();
        $issuesServices = $period->periodServices->where('status', 'gangguan')->count();
        $pendingServices = $totalServices - $checkedServices;
        $progressPercent = $totalServices > 0 ? round(($checkedServices / $totalServices) * 100) : 0;

        // Distribusi tester
        $testersWorkload = [];
        foreach ($period->periodTesters as $pt) {
            $t = $pt->tester;
            if (!$t) continue;
            $tServices = $period->periodServices->where('tester_id', $t->id);
            $tChecked = $tServices->filter(fn($ps) => $ps->status !== 'pending' && !empty($ps->checked_at))->count();
            $testersWorkload[] = [
                'name' => $t->name,
                'email' => $t->email,
                'total' => $tServices->count(),
                'checked' => $tChecked,
                'percent' => $tServices->count() > 0 ? round(($tChecked / $tServices->count()) * 100) : 0,
            ];
        }

        $servicesList = $period->periodServices->map(function ($ps, $idx) {
            return [
                'no' => $idx + 1,
                'service_name' => $ps->service?->service_name ?? 'Layanan',
                'category' => $ps->service?->category ?? 'Umum',
                'tester_name' => $ps->tester?->name ?? 'Tester',
                'status' => $ps->status,
                'checked_at' => $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d M Y H:i') : '-',
                'description' => $ps->description ?? '-',
            ];
        });

        return Inertia::render('Admin/Periods/ExportReport', [
            'report' => [
                'title' => $periodTitle,
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
            ],
            'testers_workload' => $testersWorkload,
            'services' => $servicesList,
        ]);
    }
}
