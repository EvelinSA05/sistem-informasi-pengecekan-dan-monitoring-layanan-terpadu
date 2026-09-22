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
     * Menampilkan daftar periode pengecekan.
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
     * Halaman form pembuatan periode baru.
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
     * Halaman form edit periode.
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
                'deadline_date' => $period->deadline_date ? Carbon::parse($period->deadline_date)->format('Y-m-d') : null,
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
            'date'          => 'required|date',
            'deadline_date' => 'nullable|date|after_or_equal:date',
            'tester_ids'    => 'required|array|min:1',
            'tester_ids.*'  => 'required|integer|exists:users,id',
        ], [
            'date.required' => 'Tanggal pengecekan wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'deadline_date.date' => 'Format tenggat waktu tidak valid.',
            'deadline_date.after_or_equal' => 'Tenggat waktu tidak boleh lebih awal dari tanggal periode.',
            'tester_ids.required' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.min' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.*.exists' => 'Tester yang dipilih tidak valid.',
        ]);

        $date = Carbon::parse($request->date)->format('Y-m-d');

        if (CheckingPeriod::where('date', $date)->exists()) {
            return back()->withErrors([
                'date' => 'Periode untuk tanggal ' . $this->formatIndonesianDate(Carbon::parse($date)) . ' sudah ada.',
            ]);
        }

        $testerIds = array_values(array_map('intval', array_unique((array) $request->tester_ids)));
        
        $busyTesters = User::whereIn('id', $testerIds)
            ->whereHas('periodTesters.period', function ($q) {
                $q->where('status', 'proses');
            })
            ->get();

        if ($busyTesters->isNotEmpty()) {
            $names = $busyTesters->pluck('name')->implode(', ');
            return back()->withErrors([
                'tester_ids' => "Pembuatan dibatalkan! Tester berikut masih memiliki tugas di periode aktif yang belum selesai: {$names}. Silakan tutup periode lama terlebih dahulu."
            ]);
        }

        try {
            DB::transaction(function () use ($date, $request, $testerIds) {
                $period = CheckingPeriod::create([
                    'date'          => $date,
                    'deadline_date' => $request->deadline_date ? Carbon::parse($request->deadline_date)->format('Y-m-d') : null,
                    'status'        => 'proses',
                    'created_by'    => $request->user()->id,
                ]);

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
                        'period_id'   => $period->id,
                        'service_id'  => $service->id,
                        'tester_id'   => $assignedTesterId,
                        'status'      => 'pending',
                        'description' => null,
                        'checked_at'  => null,
                    ]);
                }
            });

            return redirect()->route('admin.periods.index')->with('success', 'Periode pengecekan berhasil dibuat dan layanan didistribusikan.');
        } catch (\Throwable $e) {
            Log::error('Gagal membuat periode pengecekan: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat membuat periode pengecekan. Silakan coba lagi.');
        }
    }

    /**
     * Menampilkan detail periode.
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
                    'id'           => $ps->id,
                    'service_id'   => $ps->service_id,
                    'service_name' => $ps->service?->service_name ?? 'Layanan',
                    'category'     => $ps->service?->category ?? 'Umum',
                    'icon'         => $ps->service?->icon ?? 'globe',
                    'service_url'  => $ps->service?->service_url,
                    'status'       => $ps->status,
                    'description'  => $ps->description,
                    'checked_at'   => $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d M Y H:i') : null,
                ];
            })->values();

            $distribution[] = [
                'tester_id'        => $tester->id,
                'name'             => $tester->name,
                'email'            => $tester->email,
                'initials'         => $initials,
                'total_services'   => $assignedServices->count(),
                'checked_services' => $assignedServices->filter(fn($s) => $s['status'] !== 'pending')->count(),
                'services'         => $assignedServices,
            ];
        }

        return Inertia::render('Admin/Periods/Show', [
            'period' => [
                'id'               => $period->id,
                'title'            => $periodTitle,
                'date'             => $carbonDate->format('Y-m-d'),
                'deadline_date'    => $period->deadline_date ? Carbon::parse($period->deadline_date)->format('d F Y') : null,
                'status'           => $period->status,
                'created_at_label' => Carbon::parse($period->created_at)->format('d F Y H:i'),
                'creator_name'     => $period->creator?->name ?? 'Admin',
                'total_services'   => $totalServices,
                'checked_services' => $checkedServices,
                'normal_services'  => $normalServices,
                'issues_services'  => $issuesServices,
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
            
            if ($request->status === 'proses') {
                $testerIds = $period->periodTesters()->pluck('tester_id')->toArray();
                
                $busyTesters = User::whereIn('id', $testerIds)
                    ->whereHas('periodTesters.period', function ($q) use ($period) {
                        $q->where('status', 'proses')->where('checking_periods.id', '!=', $period->id);
                    })
                    ->get();
                    
                if ($busyTesters->isNotEmpty()) {
                    $names = $busyTesters->pluck('name')->implode(', ');
                    return back()->withErrors(['status' => "Gagal membuka kembali! Tester berikut masih memiliki tugas di periode aktif lain: {$names}."]);
                }
            }

            try {
                $period->update([
                    'status' => $request->status,
                ]);

                return back()->with('success', 'Status periode berhasil diperbarui.');
            } catch (\Throwable $e) {
                Log::error('Gagal memperbarui status periode: ' . $e->getMessage(), [
                    'period_id' => $period->id,
                ]);

                return back()->with('error', 'Terjadi kesalahan saat memperbarui status periode.');
            }
        }

        // Kasus 2: Update dari Form Edit Periode (Tanggal, Tester & Distribusi)
        if ($request->has('tanggal_periode') && !$request->has('date')) {
            $request->merge(['date' => $request->tanggal_periode]);
        }

        $request->validate([
            'date'          => 'required|date',
            'deadline_date' => 'nullable|date|after_or_equal:date',
            'tester_ids'    => 'required|array|min:1',
            'tester_ids.*'  => 'required|integer|exists:users,id',
        ], [
            'date.required' => 'Tanggal pengecekan wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'deadline_date.date' => 'Format tenggat waktu tidak valid.',
            'deadline_date.after_or_equal' => 'Tenggat waktu tidak boleh lebih awal dari tanggal periode.',
            'tester_ids.required' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.min' => 'Pilih minimal satu tester untuk periode pengecekan.',
            'tester_ids.*.exists' => 'Tester yang dipilih tidak valid.',
        ]);

        $date = Carbon::parse($request->date)->format('Y-m-d');

        // Cek duplikasi tanggal kecuali untuk periode ini sendiri
        if (CheckingPeriod::where('date', $date)->where('id', '!=', $period->id)->exists()) {
            return back()->withErrors([
                'date' => 'Periode untuk tanggal ' . $this->formatIndonesianDate(Carbon::parse($date)) . ' sudah ada.',
            ]);
        }

        $testerIds = array_values(array_map('intval', array_unique((array) $request->tester_ids)));
        
        $busyTesters = User::whereIn('id', $testerIds)
            ->whereHas('periodTesters.period', function ($q) use ($period) {
                $q->where('status', 'proses')->where('checking_periods.id', '!=', $period->id);
            })
            ->get();

        if ($busyTesters->isNotEmpty()) {
            $names = $busyTesters->pluck('name')->implode(', ');
            return back()->withErrors([
                'tester_ids' => "Pembaruan dibatalkan! Tester berikut masih memiliki tugas di periode aktif yang belum selesai: {$names}. Silakan tutup periode lama terlebih dahulu."
            ]);
        }

        try {
            DB::transaction(function () use ($period, $date, $request, $testerIds) {
                $period->update([
                    'date'          => $date,
                    'deadline_date' => $request->deadline_date ? Carbon::parse($request->deadline_date)->format('Y-m-d') : null,
                ]);

                // Simpan status cek yang sudah ada sebelum redistribusi agar catatan tidak hilang
                $existingServices = PeriodService::where('period_id', $period->id)->get()->keyBy('service_id');

                // Perbarui tester periode
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
                        'period_id'   => $period->id,
                        'service_id'  => $service->id,
                        'tester_id'   => $assignedTesterId,
                        'status'      => $prev ? $prev->status : 'pending',
                        'description' => $prev ? $prev->description : null,
                        'checked_at'  => $prev ? $prev->checked_at : null,
                    ]);
                }
            });

            return redirect()->route('admin.periods.index')->with('success', 'Data periode berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui periode pengecekan: ' . $e->getMessage(), [
                'period_id' => $period->id,
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat memperbarui periode pengecekan. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus periode pengecekan.
     */
    public function destroy(CheckingPeriod $period)
    {
        try {
            $period->delete();

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
     * Export Laporan Periode ke Format Excel (CSV kompatibel Excel dengan UTF-8 BOM).
     */
    public function exportExcel(CheckingPeriod $period): StreamedResponse
    {
        $period->load([
            'creator:id,name',
            'periodTesters.tester:id,name,email,bidang',
            'periodServices.service',
            'periodServices.tester:id,name,email',
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
            fputcsv($file, []);

            // Perhitungan metrik aktual
            $totalServices = $period->periodServices->count();
            $normalCount   = $period->periodServices->where('status', 'normal')->count();
            $issuesCount   = $period->periodServices->where('status', 'gangguan')->count();
            $pendingCount  = $period->periodServices->where('status', 'pending')->count();
            $checkedCount  = $totalServices - $pendingCount;
            $readinessPct  = $totalServices > 0 ? round(($checkedCount / $totalServices) * 100) : 0;

            $categoriesCount = $period->periodServices
                ->map(fn($ps) => $ps->service?->category ?: 'Umum')
                ->unique()
                ->count();

            $testersCount = $period->periodTesters->count() > 0
                ? $period->periodTesters->count()
                : $period->periodServices->pluck('tester_id')->filter()->unique()->count();

            // ==========================================
            // SECTION 2 — RINGKASAN HASIL PENGECEKAN
            // ==========================================
            fputcsv($file, ['RINGKASAN HASIL PENGECEKAN']);
            fputcsv($file, ['Total Layanan', $totalServices]);
            fputcsv($file, ['Layanan Normal', $normalCount]);
            fputcsv($file, ['Layanan Terkendala', $issuesCount]);
            fputcsv($file, ['Belum Diperiksa', $pendingCount]);
            fputcsv($file, ['Tingkat Kesiapan', "{$readinessPct}%"]);
            fputcsv($file, ['Jumlah Kategori Layanan', $categoriesCount]);
            fputcsv($file, ['Jumlah Tester Terlibat', $testersCount]);
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
            // SECTION 4 — REKAP KATEGORI
            // ==========================================
            $categorySummary = $period->periodServices
                ->groupBy(fn($ps) => $ps->service?->category ?: 'Umum')
                ->map(fn($group) => $group->count())
                ->sortDesc();

            fputcsv($file, ['REKAP KATEGORI']);
            fputcsv($file, ['Kategori Layanan', 'Jumlah Layanan']);
            foreach ($categorySummary as $catName => $catTotal) {
                fputcsv($file, [$catName, $catTotal]);
            }
            fputcsv($file, []);

            // ==========================================
            // SECTION 5 — REKAP TESTER
            // ==========================================
            $testerSummary = [];

            // Inisialisasi dari tester yang ditugaskan pada periode
            foreach ($period->periodTesters as $pt) {
                $tName = $pt->tester?->name ?? ('Tester #' . $pt->tester_id);
                $testerSummary[$pt->tester_id] = [
                    'name'       => $tName,
                    'total'      => 0,
                    'normal'     => 0,
                    'terkendala' => 0,
                    'pending'    => 0,
                ];
            }

            // Akumulasi layanan per tester
            foreach ($period->periodServices as $ps) {
                $tId = $ps->tester_id;
                if (!$tId) {
                    $tKey = 'unassigned';
                    if (!isset($testerSummary[$tKey])) {
                        $testerSummary[$tKey] = [
                            'name'       => 'Belum Ditugaskan',
                            'total'      => 0,
                            'normal'     => 0,
                            'terkendala' => 0,
                            'pending'    => 0,
                        ];
                    }
                } else {
                    $tKey = $tId;
                    if (!isset($testerSummary[$tKey])) {
                        $testerSummary[$tKey] = [
                            'name'       => $ps->tester?->name ?? ('Tester #' . $tId),
                            'total'      => 0,
                            'normal'     => 0,
                            'terkendala' => 0,
                            'pending'    => 0,
                        ];
                    }
                }

                $testerSummary[$tKey]['total']++;
                if ($ps->status === 'normal') {
                    $testerSummary[$tKey]['normal']++;
                } elseif ($ps->status === 'gangguan') {
                    $testerSummary[$tKey]['terkendala']++;
                } else {
                    $testerSummary[$tKey]['pending']++;
                }
            }

            fputcsv($file, ['REKAP TESTER']);
            fputcsv($file, ['Tester', 'Jumlah Layanan', 'Normal', 'Terkendala', 'Belum Diperiksa']);
            foreach ($testerSummary as $tStat) {
                fputcsv($file, [
                    $tStat['name'],
                    $tStat['total'],
                    $tStat['normal'],
                    $tStat['terkendala'],
                    $tStat['pending'],
                ]);
            }
            fputcsv($file, []);

            // ==========================================
            // SECTION 6 — DETAIL HASIL PENGECEKAN
            // ==========================================
            fputcsv($file, ['DETAIL HASIL PENGECEKAN']);
            fputcsv($file, [
                'No',
                'ID Layanan',
                'Nama Layanan',
                'Kategori',
                'Level',
                'Tester',
                'Email Tester',
                'Status Pengecekan',
                'Waktu Pengecekan',
                'Keterangan / Catatan',
            ]);

            $no = 1;
            foreach ($period->periodServices as $ps) {
                $statusLabel = match($ps->status) {
                    'normal'   => 'NORMAL',
                    'gangguan' => 'TERKENDALA',
                    default    => 'BELUM DIPERIKSA',
                };

                $levelDisplay = !empty($ps->service?->level) ? $ps->service->level : '-';
                $checkedAtDisplay = $ps->checked_at ? Carbon::parse($ps->checked_at)->format('d/m/Y H:i') : '-';
                $descDisplay = !empty($ps->description) ? $ps->description : '-';

                fputcsv($file, [
                    $no++,
                    $ps->service?->source_service_id ?? ('SRV-' . str_pad($ps->service_id, 3, '0', STR_PAD_LEFT)),
                    $ps->service?->service_name ?? '-',
                    $ps->service?->category ?: 'Umum',
                    $levelDisplay,
                    $ps->tester?->name ?? 'Belum Ditugaskan',
                    $ps->tester?->email ?? '-',
                    $statusLabel,
                    $checkedAtDisplay,
                    $descDisplay,
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
