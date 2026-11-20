<?php

namespace App\Services;

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\Service;
use App\Models\ServiceCheckLog;
use App\Models\ServiceIssue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ExternalServiceSyncService
{
    protected ?string $syncUrl;
    protected ?string $bearerToken;
    protected string $fallbackCatalogPath;

    public function __construct()
    {
        $url = config('services.service_sync.url');
        $this->syncUrl = is_string($url) && trim($url) !== '' ? trim($url) : null;
        $token = config('services.service_sync.token');
        $this->bearerToken = is_string($token) && trim($token) !== '' ? trim($token) : null;
        $this->fallbackCatalogPath = database_path('data/integrated_services.json');
    }

    /**
     * Mengambil daftar layanan dari URL eksternal (JSON atau CSV / Google Sheets Published CSV),
     * atau dari katalog layanan terpadu bawaan jika SERVICE_SYNC_URL belum diatur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchServices(): array
    {
        if ($this->syncUrl !== null) {
            return $this->fetchFromExternalUrl($this->syncUrl);
        }

        return $this->fetchFromBuiltInCatalog();
    }

    /**
     * Tarik daftar layanan dari full URL eksternal (mendukung JSON dan CSV).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFromExternalUrl(string $url): array
    {
        try {
            $request = Http::withoutVerifying()
                ->timeout(30)
                ->connectTimeout(10)
                ->withHeaders(['Accept' => 'application/json, text/csv, text/plain, */*']);

            if ($this->bearerToken !== null) {
                $request = $request->withToken($this->bearerToken);
            }

            $response = $request->get($url);
        } catch (\Throwable $e) {
            Log::error('External Service Sync Request Error: ' . $e->getMessage(), ['url' => $url]);
            throw new RuntimeException('Gagal menghubungi URL sumber layanan eksternal: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            throw new RuntimeException("Gagal mengambil data dari sumber eksternal (HTTP {$response->status()}).");
        }

        $body = trim($response->body());
        if ($body === '') {
            throw new RuntimeException('Respon dari URL sumber layanan eksternal kosong.');
        }

        // 1. Coba parse sebagai JSON terlebih dahulu
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
            if (!is_array($items)) {
                throw new RuntimeException('Format JSON dari sumber layanan eksternal tidak valid.');
            }
            return array_values($items);
        }

        // 2. Jika bukan JSON, parse sebagai CSV (misal Google Sheets Published CSV)
        return $this->parseCsv($body);
    }

    /**
     * Parse isi CSV menjadi array asosiatif.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseCsv(string $csvContent): array
    {
        // Hapus UTF-8 BOM jika ada
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent) ?? $csvContent;

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('Gagal memproses stream CSV sumber eksternal.');
        }

        fwrite($handle, $csvContent);
        rewind($handle);

        $headers = fgetcsv($handle);
        if (!is_array($headers) || empty($headers)) {
            fclose($handle);
            throw new RuntimeException('Header CSV tidak ditemukan atau format sumber eksternal tidak valid.');
        }

        $normalizedHeaders = array_map(
            fn ($h) => Str::snake(strtolower(trim((string) $h))),
            $headers
        );

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }
            $assoc = [];
            foreach ($normalizedHeaders as $idx => $key) {
                if ($key !== '') {
                    $assoc[$key] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
                }
            }
            if (!empty($assoc)) {
                $rows[] = $assoc;
            }
        }

        fclose($handle);

        if (empty($rows)) {
            throw new RuntimeException('Tidak ada baris data layanan yang valid pada CSV sumber eksternal.');
        }

        return $rows;
    }

    /**
     * Muat katalog layanan digital publik terpadu bawaan.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFromBuiltInCatalog(): array
    {
        if (!file_exists($this->fallbackCatalogPath)) {
            throw new RuntimeException('File katalog layanan terpadu bawaan tidak ditemukan.');
        }

        $decoded = json_decode((string) file_get_contents($this->fallbackCatalogPath), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Format katalog layanan terpadu bawaan tidak valid.');
        }

        return $decoded;
    }

    /**
     * Melakukan sinkronisasi data layanan ke database lokal.
     *
     * @return array{total: int, created: int, updated: int, deleted: int, source: string}
     */
    public function syncServices(): array
    {
        $items = $this->fetchServices();

        $created = 0;
        $updated = 0;
        $validSourceIds = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['nama'] ?? $item['service_name'] ?? $item['name'] ?? $item['nama_layanan'] ?? ''));
            $url  = trim((string) ($item['url'] ?? $item['service_url'] ?? $item['link'] ?? ''));

            if ($name === '') {
                continue;
            }

            $rawId = trim((string) ($item['id'] ?? $item['source_service_id'] ?? $item['kode'] ?? ''));
            $sourceId = $rawId !== '' ? $rawId : 'svc-' . Str::slug($name) . '-' . ($index + 1);

            $validSourceIds[] = $sourceId;

            $rawLevel = $item['level'] ?? null;
            $level = null;
            if ($rawLevel !== null && trim((string) $rawLevel) !== '') {
                $trimmedLevel = trim((string) $rawLevel);
                $level = is_numeric($trimmedLevel) ? "Level {$trimmedLevel}" : $trimmedLevel;
            }

            $rawCategory = $item['owner'] ?? $item['category'] ?? $item['kategori'] ?? $item['instansi'] ?? null;
            $category = !empty($rawCategory) ? trim((string) $rawCategory) : 'Umum';
            $cleanUrl = $url !== '' ? $url : null;

            // 1. Cek berdasarkan source_service_id
            $service = Service::where('source_service_id', $sourceId)->first();

            // 2. Jika belum ketemu, cek berdasarkan nama dan URL yang sama
            if (!$service && $cleanUrl !== null) {
                $service = Service::where('service_name', $name)
                    ->where('service_url', $cleanUrl)
                    ->first();
            }

            if ($service) {
                $service->update([
                    'source_service_id' => $sourceId,
                    'service_name'      => $name,
                    'category'          => $category,
                    'level'             => $level,
                    'service_url'       => $cleanUrl,
                    'is_active'         => true,
                    'status'            => Service::STATUS_AKTIF,
                    'synced_at'         => now(),
                ]);
                $updated++;
            } else {
                Service::create([
                    'source_service_id' => $sourceId,
                    'service_name'      => $name,
                    'category'          => $category,
                    'level'             => $level,
                    'service_url'       => $cleanUrl,
                    'icon'              => 'layanan',
                    'is_active'         => true,
                    'status'            => Service::STATUS_AKTIF,
                    'synced_at'         => now(),
                ]);
                $created++;
            }
        }

        // Bersihkan layanan lama yang tidak ada pada sumber sinkronisasi terbaru
        $invalidServices = Service::where(function ($q) use ($validSourceIds) {
            $q->whereNotIn('source_service_id', $validSourceIds)
                ->orWhereNull('source_service_id')
                ->orWhereNull('synced_at');
        })->get();

        $deleted = 0;
        if ($invalidServices->isNotEmpty()) {
            $invalidIds = $invalidServices->pluck('id')->all();
            $invalidPsIds = PeriodService::whereIn('service_id', $invalidIds)->pluck('id')->all();

            if (!empty($invalidPsIds)) {
                ServiceIssue::whereIn('period_service_id', $invalidPsIds)->delete();
                ServiceCheckLog::whereIn('period_service_id', $invalidPsIds)->delete();
                PeriodService::whereIn('id', $invalidPsIds)->delete();
            }

            ServiceIssue::whereIn('layanan_id', $invalidIds)->delete();
            $deleted = Service::whereIn('id', $invalidIds)->delete();
        }

        // Sinkronkan seluruh periode aktif dengan master layanan terbaru
        try {
            CheckingPeriod::syncAllActivePeriodsWithMasterServices();
        } catch (\Throwable $e) {
            Log::warning('Gagal menyinkronkan periode aktif setelah sinkronisasi layanan: ' . $e->getMessage());
        }

        return [
            'total'   => count($validSourceIds),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'source'  => $this->syncUrl ?? 'Katalog Layanan Terpadu SIMONTER',
        ];
    }
}
