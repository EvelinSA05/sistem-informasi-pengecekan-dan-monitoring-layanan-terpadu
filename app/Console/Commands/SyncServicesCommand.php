<?php

namespace App\Console\Commands;

use App\Services\ExternalServiceSyncService;
use Illuminate\Console\Command;

class SyncServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi daftar layanan digital terpadu dari sumber URL eksternal (JSON/CSV) atau katalog bawaan';

    /**
     * Execute the console command.
     */
    public function handle(ExternalServiceSyncService $syncService): int
    {
        $this->info('Memulai sinkronisasi layanan terpadu...');

        try {
            $result = $syncService->syncServices();

            $this->info("Sinkronisasi berhasil! Sumber: {$result['source']}");
            $this->table(
                ['Keterangan', 'Jumlah'],
                [
                    ['Total Layanan Diproses', $result['total']],
                    ['Layanan Baru Ditambahkan', $result['created']],
                    ['Layanan Diperbarui', $result['updated']],
                    ['Layanan Lama Dibersihkan', $result['deleted']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal sinkronisasi layanan: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
