<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Flash messages dibagikan ke semua halaman Inertia secara otomatis
            'flash' => [
                'session_expired' => $request->session()->get('session_expired'),
                'status'          => $request->session()->get('status'),
                'error'           => $request->session()->get('error'),
                'success'         => $request->session()->get('success'),
            ],
            'recaptcha_site_key' => config('services.recaptcha.site_key'),
            'notifications' => fn () => $this->buildNotifications($request),
        ];
    }

    /**
     * Menyusun daftar notifikasi in-app untuk Admin dan Tester (lonceng notifikasi di navbar).
     */
    protected function buildNotifications(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [
                'unread_count' => 0,
                'items'        => [],
            ];
        }

        try {
            $items = [];
            $readAtSession = $request->session()->get('notifications_read_at_' . $user->id);
            $readAtCarbon = $readAtSession ? \Carbon\Carbon::parse($readAtSession) : null;

            if ($user->role === 'admin') {
                // 1. Notifikasi Kendala Layanan yang Belum Ditindaklanjuti
                $openIssues = \App\Models\ServiceIssue::with([
                    'service:id,service_name,category',
                    'period:id,date',
                    'periodService' => fn ($q) => $q->select(['id', 'period_id', 'service_id', 'tester_id', 'status', 'description', 'checked_at'])
                        ->with('tester:id,name'),
                ])
                    ->where('handling_status', 'belum_ditindaklanjuti')
                    ->orderByDesc('updated_at')
                    ->take(10)
                    ->get();

                foreach ($openIssues as $issue) {
                    $serviceName = $issue->service?->service_name ?? 'Layanan Digital';
                    $testerName = $issue->periodService?->tester?->name ?? 'Tester';
                    $eventTime = $issue->periodService?->checked_at
                        ? \Carbon\Carbon::parse($issue->periodService->checked_at)
                        : ($issue->updated_at ?? now());

                    $isUnread = !$issue->has_notif && (!$readAtCarbon || $eventTime->isAfter($readAtCarbon));

                    $items[] = [
                        'id'          => 'issue-' . $issue->id,
                        'type'        => 'issue_reported',
                        'severity'    => 'danger',
                        'badge'       => 'Kendala Baru',
                        'title'       => "Kendala: {$serviceName}",
                        'description' => "Dilaporkan oleh {$testerName}" . ($issue->periodService?->description ? ' — "' . \Illuminate\Support\Str::limit($issue->periodService->description, 70) . '"' : ''),
                        'time_label'  => $eventTime->diffForHumans(),
                        'url'         => $issue->periode_id ? route('admin.periods.show', $issue->periode_id) : route('admin.periods.index'),
                        'is_unread'   => $isUnread,
                    ];
                }

                // 2. Pengingat Tenggat Waktu Periode Aktif (H-3 s/d Hari Ini)
                $activePeriods = \App\Models\CheckingPeriod::where('status', 'proses')
                    ->whereNotNull('deadline_date')
                    ->orderBy('deadline_date', 'asc')
                    ->take(5)
                    ->get();

                foreach ($activePeriods as $period) {
                    $deadline = \Carbon\Carbon::parse($period->deadline_date)->startOfDay();
                    $daysLeft = (int) \Carbon\Carbon::today()->diffInDays($deadline, false);

                    if ($daysLeft >= 0 && $daysLeft <= 3) {
                        $pendingCount = \App\Models\PeriodService::where('period_id', $period->id)
                            ->where('status', 'pending')
                            ->count();

                        if ($pendingCount > 0) {
                            $periodDateLabel = \Carbon\Carbon::parse($period->date)->format('d M Y');
                            $isUnread = !$readAtCarbon || $readAtCarbon->lt(\Carbon\Carbon::today());

                            $items[] = [
                                'id'          => 'deadline-admin-' . $period->id,
                                'type'        => 'deadline_warning',
                                'severity'    => 'warning',
                                'badge'       => $daysLeft === 0 ? 'Deadline Hari Ini' : "H-{$daysLeft} Deadline",
                                'title'       => "Periode {$periodDateLabel} mendekati tenggat waktu",
                                'description' => "Masih terdapat {$pendingCount} layanan yang belum selesai diperiksa oleh tester.",
                                'time_label'  => $daysLeft === 0 ? 'Berakhir hari ini' : "{$daysLeft} hari tersisa",
                                'url'         => route('admin.periods.show', $period->id),
                                'is_unread'   => $isUnread,
                            ];
                        }
                    }
                }
            } else {
                // ─── NOTIFIKASI UNTUK TESTER ────────────────────────────────
                // 1. Kendala yang telah ditindaklanjuti Admin -> Perlu Pengecekan Ulang
                $recheckItems = \App\Models\PeriodService::withoutAttachmentData()
                    ->with([
                        'service:id,service_name',
                        'serviceIssue',
                        'period:id,date,status',
                    ])
                    ->where('tester_id', $user->id)
                    ->where('status', 'gangguan')
                    ->whereHas('serviceIssue', function ($q) {
                        $q->where('handling_status', 'perlu_pengecekan_ulang');
                    })
                    ->orderByDesc('updated_at')
                    ->take(10)
                    ->get();

                foreach ($recheckItems as $ps) {
                    $serviceName = $ps->service?->service_name ?? 'Layanan Digital';
                    $handledAt = $ps->serviceIssue?->admin_handled_at
                        ? \Carbon\Carbon::parse($ps->serviceIssue->admin_handled_at)
                        : ($ps->updated_at ?? now());
                    $adminNote = $ps->serviceIssue?->admin_note ?? $ps->admin_note;
                    $isUnread = !$readAtCarbon || $handledAt->isAfter($readAtCarbon);

                    $items[] = [
                        'id'          => 'recheck-' . $ps->id,
                        'type'        => 'recheck_needed',
                        'severity'    => 'warning',
                        'badge'       => 'Perlu Cek Ulang',
                        'title'       => "Cek Ulang: {$serviceName}",
                        'description' => $adminNote
                            ? 'Admin menindaklanjuti kendala: "' . \Illuminate\Support\Str::limit($adminNote, 70) . '"'
                            : 'Administrator telah menandai kendala selesai. Silakan lakukan pengecekan ulang.',
                        'time_label'  => $handledAt->diffForHumans(),
                        'url'         => route('tester.periods.services', ['period' => $ps->period_id, 'status' => 'gangguan']),
                        'is_unread'   => $isUnread,
                    ];
                }

                // 2. Pengingat Tenggat Waktu (H-3 / H-1 / Hari Ini) & Penugasan Periode Aktif
                $assignedPeriods = \App\Models\CheckingPeriod::where('status', 'proses')
                    ->where(function ($q) use ($user) {
                        $q->whereHas('periodTesters', fn ($sq) => $sq->where('tester_id', $user->id))
                          ->orWhereHas('periodServices', fn ($sq) => $sq->where('tester_id', $user->id));
                    })
                    ->orderByDesc('date')
                    ->take(5)
                    ->get();

                foreach ($assignedPeriods as $period) {
                    $pendingCount = \App\Models\PeriodService::where('period_id', $period->id)
                        ->where('tester_id', $user->id)
                        ->where('status', 'pending')
                        ->count();

                    if ($pendingCount <= 0) {
                        continue;
                    }

                    $periodDateLabel = \Carbon\Carbon::parse($period->date)->format('d M Y');

                    if ($period->deadline_date) {
                        $deadline = \Carbon\Carbon::parse($period->deadline_date)->startOfDay();
                        $daysLeft = (int) \Carbon\Carbon::today()->diffInDays($deadline, false);

                        if ($daysLeft >= 0 && $daysLeft <= 3) {
                            $isUnread = !$readAtCarbon || $readAtCarbon->lt(\Carbon\Carbon::today());
                            $items[] = [
                                'id'          => 'deadline-tester-' . $period->id,
                                'type'        => 'deadline_warning',
                                'severity'    => 'danger',
                                'badge'       => $daysLeft === 0 ? 'Hari Terakhir!' : "Pengingat H-{$daysLeft}",
                                'title'       => "Tenggat Periode {$periodDateLabel}",
                                'description' => "Segera selesaikan {$pendingCount} layanan yang masih berstatus Belum Dicek sebelum batas waktu berakhir.",
                                'time_label'  => $daysLeft === 0 ? 'Batas akhir hari ini' : "Tersisa {$daysLeft} hari",
                                'url'         => route('tester.periods.services', ['period' => $period->id, 'status' => 'pending']),
                                'is_unread'   => $isUnread,
                            ];
                            continue;
                        }
                    }

                    $createdAt = $period->created_at ?? now();
                    $isUnread = !$readAtCarbon || $createdAt->isAfter($readAtCarbon);

                    $items[] = [
                        'id'          => 'period-assigned-' . $period->id,
                        'type'        => 'period_assigned',
                        'severity'    => 'info',
                        'badge'       => 'Tugas Aktif',
                        'title'       => "Penugasan Periode {$periodDateLabel}",
                        'description' => "Anda memiliki {$pendingCount} layanan yang menunggu pengujian pada periode aktif ini.",
                        'time_label'  => $createdAt->diffForHumans(),
                        'url'         => route('tester.periods.services', ['period' => $period->id, 'status' => 'pending']),
                        'is_unread'   => $isUnread,
                    ];
                }
            }

            $unreadCount = collect($items)->where('is_unread', true)->count();

            return [
                'unread_count' => $unreadCount,
                'total_count'  => count($items),
                'items'        => $items,
            ];
        } catch (\Throwable $e) {
            return [
                'unread_count' => 0,
                'total_count'  => 0,
                'items'        => [],
            ];
        }
    }
}
