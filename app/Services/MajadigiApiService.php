<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MajadigiApiService
{
    protected string $baseUrl;
    protected ?string $username;
    protected ?string $password;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.majadigi.base_url', 'https://api.majadigidev.jatimprov.go.id'), '/');
        $this->username = config('services.majadigi.username');
        $this->password = config('services.majadigi.password');
    }

    /**
     * Melakukan autentikasi Basic Auth ke Majadigi API dan mengembalikan token Bearer.
     */
    public function authenticate(): string
    {
        if (empty($this->username) || empty($this->password)) {
            throw new RuntimeException('Kredensial Majadigi API (MAJADIGI_API_USERNAME / MAJADIGI_API_PASSWORD) belum dikonfigurasi di file .env.');
        }

        $loginUrl = "{$this->baseUrl}/auth/login";

        try {
            $response = Http::withoutVerifying()
                ->timeout(20)
                ->connectTimeout(10)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->withBasicAuth($this->username, $this->password)
                ->post($loginUrl);
        } catch (\Throwable $e) {
            Log::error('Majadigi API Login Request Error: ' . $e->getMessage());
            throw new RuntimeException('Gagal menghubungi server Majadigi: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            $msg = $response->json('message') ?? 'Status HTTP ' . $response->status();
            Log::error('Majadigi API Login Failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException("Autentikasi Majadigi API gagal: {$msg}");
        }

        $token = $response->json('data.token') ?? $response->json('data.jwtToken') ?? $response->json('token');

        if (empty($token)) {
            Log::error('Majadigi API Login Token Missing', ['response' => $response->json()]);
            throw new RuntimeException('Token autentikasi tidak ditemukan dalam respon Majadigi API.');
        }

        return $token;
    }

    /**
     * Mengambil seluruh daftar layanan dari endpoint /api/layanan/level.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchServices(): array
    {
        $token = $this->authenticate();
        $servicesUrl = "{$this->baseUrl}/api/layanan/level";

        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->connectTimeout(10)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->withToken($token)
                ->get($servicesUrl);
        } catch (\Throwable $e) {
            Log::error('Majadigi API Fetch Services Error: ' . $e->getMessage());
            throw new RuntimeException('Gagal mengambil data layanan dari Majadigi: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            $msg = $response->json('message') ?? 'Status HTTP ' . $response->status();
            throw new RuntimeException("Gagal mengambil data layanan dari Majadigi: {$msg}");
        }

        $data = $response->json('data');

        if (!is_array($data)) {
            throw new RuntimeException('Format data layanan dari Majadigi tidak valid (data bukan array).');
        }

        return $data;
    }

    /**
     * Melakukan sinkronisasi data layanan dari API ke database lokal.
     *
     * @return array{total: int, created: int, updated: int}
     */
    public function syncServices(): array
    {
        $items = $this->fetchServices();

        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            $sourceId = trim((string) ($item['id'] ?? ''));
            $name     = trim((string) ($item['nama'] ?? ''));

            if (empty($sourceId) || empty($name)) {
                continue;
            }

            $rawLevel = $item['level'] ?? null;
            $level = null;
            if ($rawLevel !== null && $rawLevel !== '') {
                $level = is_numeric($rawLevel) ? "Level {$rawLevel}" : (string) $rawLevel;
            }

            $category = !empty($item['owner']) ? trim((string) $item['owner']) : 'Umum';
            $url      = !empty($item['url']) ? trim((string) $item['url']) : null;

            // 1. Cek apakah layanan sudah ada berdasarkan source_service_id
            $service = Service::where('source_service_id', $sourceId)->first();

            // 2. Jika belum ketemu, cek apakah ada layanan lama dengan nama dan URL yang sama
            if (!$service && !empty($url)) {
                $service = Service::where('service_name', $name)
                    ->where('service_url', $url)
                    ->first();
            }

            if ($service) {
                // Perbarui layanan yang ada
                $service->update([
                    'source_service_id' => $sourceId,
                    'service_name'      => $name,
                    'category'          => $category,
                    'level'             => $level,
                    'service_url'       => $url,
                    'is_active'         => true,
                    'status'            => Service::STATUS_AKTIF,
                    'synced_at'         => now(),
                ]);
                $updated++;
            } else {
                // Buat layanan baru
                Service::create([
                    'source_service_id' => $sourceId,
                    'service_name'      => $name,
                    'category'          => $category,
                    'level'             => $level,
                    'service_url'       => $url,
                    'icon'              => 'layanan',
                    'is_active'         => true,
                    'status'            => Service::STATUS_AKTIF,
                    'synced_at'         => now(),
                ]);
                $created++;
            }
        }

        return [
            'total'   => count($items),
            'created' => $created,
            'updated' => $updated,
        ];
    }
}
