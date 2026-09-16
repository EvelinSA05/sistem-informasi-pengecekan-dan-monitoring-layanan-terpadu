<?php

namespace Database\Seeders;

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\PeriodTester;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Dapatkan atau buat Admin
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@majadigi.jatimprov.go.id',
                'username' => 'budisantoso',
                'password' => Hash::make('password'),
                'nip' => '198503152010011002',
                'bidang' => 'Infrastruktur',
                'role' => 'admin',
                'status' => 'aktif',
            ]);
        }

        // 2. Buat Tester realistis sesuai mockup jika belum ada
        $testersData = [
            [
                'name' => 'Sari Dewi',
                'email' => 'sari.dewi@majadigi.jatimprov.go.id',
                'username' => 'saridewi',
                'nip' => '199004122015022001',
                'bidang' => 'Layanan Publik',
                'role' => 'tester',
                'status' => 'aktif',
            ],
            [
                'name' => 'Agus Prasetyo',
                'email' => 'agus.prasetyo@majadigi.jatimprov.go.id',
                'username' => 'agusprasetyo',
                'nip' => '198807232014031003',
                'bidang' => 'Infrastruktur Digital',
                'role' => 'tester',
                'status' => 'aktif',
            ],
            [
                'name' => 'Dewi Rahayu',
                'email' => 'dewi.rahayu@majadigi.jatimprov.go.id',
                'username' => 'dewirahayu',
                'nip' => '199211052018012004',
                'bidang' => 'Layanan Publik',
                'role' => 'tester',
                'status' => 'aktif',
            ],
        ];

        $testers = [];
        foreach ($testersData as $tData) {
            $testers[] = User::firstOrCreate(
                ['email' => $tData['email']],
                array_merge($tData, ['password' => Hash::make('password')])
            );
        }

        // 3. Buat 12 Layanan Jawa Timur sesuai mockup
        $servicesData = [
            [
                'source_service_id' => 'SRV-001',
                'service_name' => 'Portal Majadigi',
                'category' => 'Portal',
                'icon' => 'globe',
                'service_url' => 'https://majadigi.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-002',
                'service_name' => 'Lapor SP4N',
                'category' => 'Pengaduan',
                'icon' => 'megaphone',
                'service_url' => 'https://lapor.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-003',
                'service_name' => 'SIMDA Barang',
                'category' => 'Aset',
                'icon' => 'box',
                'service_url' => 'https://simda.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-004',
                'service_name' => 'SiRuA',
                'category' => 'Perencanaan',
                'icon' => 'chart',
                'service_url' => 'https://sirua.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-005',
                'service_name' => 'SIPD Jawa Timur',
                'category' => 'Keuangan',
                'icon' => 'currency',
                'service_url' => 'https://sipd.kemendagri.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-006',
                'service_name' => 'PPID Jawa Timur',
                'category' => 'Informasi',
                'icon' => 'document',
                'service_url' => 'https://ppid.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-007',
                'service_name' => 'Perizinan Online',
                'category' => 'Perizinan',
                'icon' => 'certificate',
                'service_url' => 'https://dpmptsp.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-008',
                'service_name' => 'DAPODIK Jatim',
                'category' => 'Pendidikan',
                'icon' => 'academic',
                'service_url' => 'https://dapo.dindik.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-009',
                'service_name' => 'Srikandi Jatim',
                'category' => 'Kearsipan',
                'icon' => 'archive',
                'service_url' => 'https://srikandi.arsip.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-010',
                'service_name' => 'BKD Jatim Mobile',
                'category' => 'Kepegawaian',
                'icon' => 'users',
                'service_url' => 'https://bkd.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-011',
                'service_name' => 'Satu Data Jatim',
                'category' => 'Statistik',
                'icon' => 'database',
                'service_url' => 'https://satudata.jatimprov.go.id',
                'is_active' => true,
            ],
            [
                'source_service_id' => 'SRV-012',
                'service_name' => 'Jatim Bejo',
                'category' => 'Pengadaan',
                'icon' => 'shopping-cart',
                'service_url' => 'https://jatimbejo.jatimprov.go.id',
                'is_active' => true,
            ],
        ];

        $services = [];
        foreach ($servicesData as $sData) {
            $services[] = Service::firstOrCreate(
                ['source_service_id' => $sData['source_service_id']],
                $sData
            );
        }

        // 4. Buat Periode Awal Sesuai Screenshot 1 jika belum ada
        if (CheckingPeriod::count() === 0) {
            // Periode 1: Juni 2026 (Selesai)
            $pJuni = CheckingPeriod::create([
                'date' => '2026-06-01',
                'status' => 'selesai',
                'created_by' => $admin->id,
                'created_at' => '2026-06-01 08:00:00',
            ]);
            // Tester Juni: Sari Dewi & Dewi Rahayu
            PeriodTester::create(['period_id' => $pJuni->id, 'tester_id' => $testers[0]->id]);
            PeriodTester::create(['period_id' => $pJuni->id, 'tester_id' => $testers[2]->id]);
            // Layanan Juni (12 layanan)
            foreach ($services as $idx => $srv) {
                $testerId = ($idx % 2 === 0) ? $testers[0]->id : $testers[2]->id;
                PeriodService::create([
                    'period_id' => $pJuni->id,
                    'service_id' => $srv->id,
                    'tester_id' => $testerId,
                    'status' => 'normal',
                    'checked_at' => '2026-06-25 10:00:00',
                ]);
            }

            // Periode 2: Juli 2026 (Selesai)
            $pJuli = CheckingPeriod::create([
                'date' => '2026-07-01',
                'status' => 'selesai',
                'created_by' => $admin->id,
                'created_at' => '2026-07-01 08:00:00',
            ]);
            // Tester Juli: Sari Dewi & Agus Prasetyo
            PeriodTester::create(['period_id' => $pJuli->id, 'tester_id' => $testers[0]->id]);
            PeriodTester::create(['period_id' => $pJuli->id, 'tester_id' => $testers[1]->id]);
            foreach ($services as $idx => $srv) {
                $testerId = ($idx % 2 === 0) ? $testers[0]->id : $testers[1]->id;
                PeriodService::create([
                    'period_id' => $pJuli->id,
                    'service_id' => $srv->id,
                    'tester_id' => $testerId,
                    'status' => 'normal',
                    'checked_at' => '2026-07-28 14:30:00',
                ]);
            }

            // Periode 3: Agustus 2026 (Proses, 7/12 checked)
            $pAgustus = CheckingPeriod::create([
                'date' => '2026-08-01',
                'status' => 'proses',
                'created_by' => $admin->id,
                'created_at' => '2026-08-01 08:00:00',
            ]);
            // Tester Agustus: Sari Dewi, Agus Prasetyo, Dewi Rahayu
            PeriodTester::create(['period_id' => $pAgustus->id, 'tester_id' => $testers[0]->id]);
            PeriodTester::create(['period_id' => $pAgustus->id, 'tester_id' => $testers[1]->id]);
            PeriodTester::create(['period_id' => $pAgustus->id, 'tester_id' => $testers[2]->id]);
            // Layanan didistribusikan merata: 4 per tester
            foreach ($services as $idx => $srv) {
                $testerId = $testers[$idx % 3]->id;
                $isChecked = $idx < 7; // 7 dari 12 sudah dicek sesuai screenshot 1
                PeriodService::create([
                    'period_id' => $pAgustus->id,
                    'service_id' => $srv->id,
                    'tester_id' => $testerId,
                    'status' => $isChecked ? 'normal' : 'pending',
                    'checked_at' => $isChecked ? '2026-08-15 09:15:00' : null,
                ]);
            }
        }
    }
}
