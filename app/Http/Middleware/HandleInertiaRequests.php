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
            \App\Models\UserNotificationRead::ensureSchema();

            $items = [];
            $readAtDb = \Illuminate\Support\Facades\Schema::hasColumn('users', 'notifications_read_at')
                ? $user->notifications_read_at
                : null;
            $readAtSession = $request->session()->get('notifications_read_at_' . $user->id);
            $readAtCarbon = $readAtDb
                ? \Carbon\Carbon::parse($readAtDb)
                : ($readAtSession ? \Carbon\Carbon::parse($readAtSession) : null);

            $readKeys = \Illuminate\Support\Facades\Schema::hasTable('user_notification_reads')
                ? \App\Models\UserNotificationRead::where('user_id', $user->id)
                    ->pluck('notification_key')
                    ->flip()
                    ->all()
                : array_flip((array) $request->session()->get('notifications_read_keys_' . $user->id, []));

            $isItemUnread = function (string $key, \Carbon\Carbon $eventTime, bool $alreadyRead = false) use ($readKeys, $readAtCarbon): bool {
                if ($alreadyRead || isset($readKeys[$key])) {
                    return false;
                }
                return !$readAtCarbon || $eventTime->isAfter($readAtCarbon);
            };

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

                    $notifKey = 'issue-' . $issue->id;
                    $isUnread = $isItemUnread($notifKey, $eventTime, (bool) $issue->has_notif);

                    $items[] = [
                        'id'                => $notifKey,
                        'type'              => 'issue_reported',
                        'severity'          => 'danger',
                        'badge'             => 'Kendala Baru',
                        'title'             => "Kendala: {$serviceName}",
                        'description'       => "Dilaporkan oleh {$testerName}" . ($issue->periodService?->description ? ' — "' . \Illuminate\Support\Str::limit($issue->periodService->description, 90) . '"' : ''),
                        'time_label'        => $eventTime->diffForHumans(),
                        'created_at_full'   => $eventTime->format('d M Y, H:i'),
                        'sort_ts'           => $eventTime->timestamp,
                        'url'               => $issue->periode_id ? route('admin.periods.show', $issue->periode_id) : route('admin.periods.index'),
                        'is_unread'         => $isUnread,
                    ];
                }

                // 2. Log Aktivitas Pengecekan Terbaru oleh Tester (Normal / Kendala / Cek Ulang)
                $recentLogs = \App\Models\ServiceCheckLog::with([
                    'tester:id,name',
                    'periodService' => fn ($q) => $q->select(['id', 'period_id', 'service_id'])
                        ->with(['service:id,service_name', 'period:id,date']),
                ])
                    ->orderByDesc('created_at')
                    ->take(15)
                    ->get();

                foreach ($recentLogs as $log) {
                    $serviceName = $log->periodService?->service?->service_name ?? 'Layanan Digital';
                    $testerName = $log->tester?->name ?? 'Tester';
                    $periodId = $log->periodService?->period_id;
                    $eventTime = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : now();
                    $notifKey = 'checklog-' . $log->id;
                    $isUnread = $isItemUnread($notifKey, $eventTime);
                    $isIssue = $log->status === 'gangguan';

                    $items[] = [
                        'id'                => $notifKey,
                        'type'              => $isIssue ? 'check_issue' : 'check_normal',
                        'severity'          => $isIssue ? 'danger' : 'info',
                        'badge'             => $isIssue ? 'Update Kendala' : 'Update Normal',
                        'title'             => "{$testerName} memeriksa {$serviceName}",
                        'description'       => ($isIssue ? 'Status: Terkendala' : 'Status: Normal') . ($log->description ? ' — "' . \Illuminate\Support\Str::limit($log->description, 90) . '"' : ''),
                        'time_label'        => $eventTime->diffForHumans(),
                        'created_at_full'   => $eventTime->format('d M Y, H:i'),
                        'sort_ts'           => $eventTime->timestamp,
                        'url'               => $periodId ? route('admin.periods.show', $periodId) : route('admin.periods.index'),
                        'is_unread'         => $isUnread,
                    ];
                }

                // 3. Pengingat Tenggat Waktu Periode Aktif (H-3 s/d Hari Ini)
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
                            $notifKey = 'deadline-admin-' . $period->id;
                            $isUnread = $isItemUnread($notifKey, \Carbon\Carbon::today());

                            $items[] = [
                                'id'          => $notifKey,
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
                    $notifKey = 'recheck-' . $ps->id;
                    $isUnread = $isItemUnread($notifKey, $handledAt);

                    $items[] = [
                        'id'                => $notifKey,
                        'type'              => 'recheck_needed',
                        'severity'          => 'warning',
                        'badge'             => 'Perlu Cek Ulang',
                        'title'             => "Cek Ulang: {$serviceName}",
                        'description'       => $adminNote
                            ? 'Admin menindaklanjuti kendala: "' . \Illuminate\Support\Str::limit($adminNote, 70) . '"'
                            : 'Administrator telah menandai kendala selesai. Silakan lakukan pengecekan ulang.',
                        'time_label'        => $handledAt->diffForHumans(),
                        'created_at_full'   => $handledAt->format('d M Y, H:i'),
                        'sort_ts'           => $handledAt->timestamp,
                        'url'               => route('tester.periods.services', ['period' => $ps->period_id, 'status' => 'gangguan']),
                        'is_unread'         => $isUnread,
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
                            $notifKey = 'deadline-tester-' . $period->id;
                            $isUnread = $isItemUnread($notifKey, \Carbon\Carbon::today());
                            $items[] = [
                                'id'          => $notifKey,
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
                    $notifKey = 'period-assigned-' . $period->id;
                    $isUnread = $isItemUnread($notifKey, $createdAt);

                    $items[] = [
                        'id'                => $notifKey,
                        'type'              => 'period_assigned',
                        'severity'          => 'info',
                        'badge'             => 'Tugas Aktif',
                        'title'             => "Penugasan Periode {$periodDateLabel}",
                        'description'       => "Anda memiliki {$pendingCount} layanan yang menunggu pengujian pada periode aktif ini.",
                        'time_label'        => $createdAt->diffForHumans(),
                        'created_at_full'   => $createdAt->format('d M Y, H:i'),
                        'sort_ts'           => $createdAt->timestamp,
                        'url'               => route('tester.periods.services', ['period' => $period->id, 'status' => 'pending']),
                        'is_unread'         => $isUnread,
                    ];
                }

                // 3. Log Update Pengecekan Terbaru oleh Tester Ini
                $myRecentLogs = \App\Models\ServiceCheckLog::with([
                    'periodService' => fn ($q) => $q->select(['id', 'period_id', 'service_id'])
                        ->with(['service:id,service_name']),
                ])
                    ->where('tester_id', $user->id)
                    ->orderByDesc('created_at')
                    ->take(10)
                    ->get();

                foreach ($myRecentLogs as $log) {
                    $serviceName = $log->periodService?->service?->service_name ?? 'Layanan Digital';
                    $periodId = $log->periodService?->period_id;
                    $eventTime = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : now();
                    $notifKey = 'mylog-' . $log->id;
                    $isUnread = $isItemUnread($notifKey, $eventTime);
                    $isIssue = $log->status === 'gangguan';

                    $items[] = [
                        'id'                => $notifKey,
                        'type'              => $isIssue ? 'check_issue' : 'check_normal',
                        'severity'          => $isIssue ? 'danger' : 'info',
                        'badge'             => $isIssue ? 'Laporan Kendala' : 'Hasil Normal',
                        'title'             => "Update: {$serviceName}",
                        'description'       => ($isIssue ? 'Dilaporkan Terkendala' : 'Diverifikasi Normal') . ($log->description ? ' — "' . \Illuminate\Support\Str::limit($log->description, 90) . '"' : ''),
                        'time_label'        => $eventTime->diffForHumans(),
                        'created_at_full'   => $eventTime->format('d M Y, H:i'),
                        'sort_ts'           => $eventTime->timestamp,
                        'url'               => $periodId ? route('tester.periods.services', ['period' => $periodId]) : route('tester.services.index'),
                        'is_unread'         => $isUnread,
                    ];
                }
            }

            // Urutkan semua notifikasi dari yang paling baru; jika timestamp sama, utamakan notifikasi aksi (recheck/issue/deadline)
            $priorityRank = [
                'recheck_needed'   => 4,
                'issue_reported'   => 4,
                'deadline_warning' => 3,
                'period_assigned'  => 2,
                'check_issue'      => 1,
                'check_normal'     => 1,
            ];
            usort($items, function ($a, $b) use ($priorityRank) {
                $tsCmp = ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0);
                if ($tsCmp !== 0) {
                    return $tsCmp;
                }
                return ($priorityRank[$b['type'] ?? ''] ?? 0) <=> ($priorityRank[$a['type'] ?? ''] ?? 0);
            });

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
