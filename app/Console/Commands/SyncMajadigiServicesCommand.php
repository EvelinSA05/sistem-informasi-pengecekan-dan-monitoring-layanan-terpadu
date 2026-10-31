<?php

namespace App\Console\Commands;

use App\Services\MajadigiApiService;
use Illuminate\Console\Command;

class SyncMajadigiServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'majadigi:sync-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi daftar layanan digital dari API Majadigi';

    /**
     * Execute the console command.
     */
    public function handle(MajadigiApiService $apiService): int
    {
        $this->info('Memulai sinkronisasi layanan dari API Majadigi...');

        try {
            $result = $apiService->syncServices();

            $this->info("Sinkronisasi berhasil!");
            $this->table(
                ['Keterangan', 'Jumlah'],
                [
                    ['Total Layanan dari API', $result['total']],
                    ['Layanan Baru Ditambahkan', $result['created']],
                    ['Layanan Diperbarui', $result['updated']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal sinkronisasi layanan: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
