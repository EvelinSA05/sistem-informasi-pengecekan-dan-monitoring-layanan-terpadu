<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceDataSeeder extends Seeder
{
    public function run(): void
    {
        $rawData = <<<'EOD'
1
Paket Kunjungan Agrowisata
Dinas Pertanian dan Ketahanan Pangan
Level 1
[https://majadigi.jatimprov.go.id/layanan/paket-kunjungan-agrowisata](https://majadigi.jatimprov.go.id/layanan/paket-kunjungan-agrowisata)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
2
Klinik Hoaks
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/klinik-hoaks](https://majadigi.jatimprov.go.id/layanan/klinik-hoaks)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
3
Penerbitan SKHPN Rp.0,-
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/penerbitan-skhpn-rp0-](https://majadigi.jatimprov.go.id/layanan/penerbitan-skhpn-rp0-)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
4
SIKIPO JATIM
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/sikipo-jatim](https://majadigi.jatimprov.go.id/layanan/sikipo-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
5
Layanan Titik CCTV Kota Batu
Kota Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/layanan-titik-cctv-kota-batu](https://majadigi.jatimprov.go.id/layanan/layanan-titik-cctv-kota-batu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
6
GRESIKPEDIA, One Data One Portal Open Access
Kabupaten Gresik
Level 1
[https://majadigi.jatimprov.go.id/layanan/gresikpedia,-one-data-one-portal-open-access](https://majadigi.jatimprov.go.id/layanan/gresikpedia,-one-data-one-portal-open-access)
Aktif
Hanya menampilkan media sosial dan aplikasi (bisa berfungsi dengan baik)
7
MJC Jatim
Biro Perekonomian
Level 1
[https://majadigi.jatimprov.go.id/layanan/mjc-jatim](https://majadigi.jatimprov.go.id/layanan/mjc-jatim)
Tidak
Situs tidak bisa diakses meskipun sudah dimuat ulang
8
Bapenda Kota Batu
Kota Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/bapenda-kota-batu](https://majadigi.jatimprov.go.id/layanan/bapenda-kota-batu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
9
SIMPEL K3 (Sistem Pelayanan K3L)
Dinas Tenaga Kerja dan Transmigrasi Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/simpel-k3-sistem-pelayanan-k3l-2](https://majadigi.jatimprov.go.id/layanan/simpel-k3-sistem-pelayanan-k3l-2)
Aktif
Link langsung diarahkan pada Form Permohonan Perjanjian Kerja Sama
10
Layanan Kerjasama P4GN
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/layanan-kerjasama-p4gn](https://majadigi.jatimprov.go.id/layanan/layanan-kerjasama-p4gn)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
11
SIPELITA
Dinas Lingkungan Hidup Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/sipelita](https://majadigi.jatimprov.go.id/layanan/sipelita)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
12
RS Paru Jember
Rumah Sakit Paru Jember
Level 1
[https://majadigi.jatimprov.go.id/layanan/rs-paru-jember](https://majadigi.jatimprov.go.id/layanan/rs-paru-jember)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
13
JDIH Kabupaten Gresik
Kabupaten Gresik
Level 1
[https://majadigi.jatimprov.go.id/layanan/jdih-kabupaten-gresik](https://majadigi.jatimprov.go.id/layanan/jdih-kabupaten-gresik)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
14
SINFRA Jawa Timur
Biro Administrasi Pembangunan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sinfra-jawa-timur](https://majadigi.jatimprov.go.id/layanan/sinfra-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
15
CALL CENTER SIGAP JALAN
Dinas Pekerjaan Umum Bina Marga
Level 1
[https://majadigi.jatimprov.go.id/layanan/call-center-sigap-jalan](https://majadigi.jatimprov.go.id/layanan/call-center-sigap-jalan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
16
Data Desa Center SAPADesa
Dinas Pemberdayaan Masyarakat dan Desa
Level 1
[https://majadigi.jatimprov.go.id/layanan/data-desa-center-sapadesa](https://majadigi.jatimprov.go.id/layanan/data-desa-center-sapadesa)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
17
SERGAP
Dinas Koperasi dan UKM Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/sergap](https://majadigi.jatimprov.go.id/layanan/sergap)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
18
E-KAPEKA
Kabupaten Jember
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-kapeka](https://majadigi.jatimprov.go.id/layanan/e-kapeka)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
19
Destinasi Wisata
Dinas Kebudayaan dan Pariwisata Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/destinasi-wisata](https://majadigi.jatimprov.go.id/layanan/destinasi-wisata)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
20
Jatim Bejo
Biro Pengadaan Barang dan Jasa
Level 1
[https://majadigi.jatimprov.go.id/layanan/jatim-bejo](https://majadigi.jatimprov.go.id/layanan/jatim-bejo)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
21
Layanan Magang & Penelitian
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/layanan-magang--penelitian](https://majadigi.jatimprov.go.id/layanan/layanan-magang--penelitian)
Aktif
Link langsung diarahkan pada Form Permohonan Magang / Penelitian
22
SMEP APBD
Biro Administrasi Pembangunan
Level 1
[https://majadigi.jatimprov.go.id/layanan/smep-apbd](https://majadigi.jatimprov.go.id/layanan/smep-apbd)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
23
SIPDe
Biro Perekonomian
Level 1
[https://majadigi.jatimprov.go.id/layanan/sipde](https://majadigi.jatimprov.go.id/layanan/sipde)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
24
Lynbangjol
Badan Riset dan Inovasi Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/lynbangjol](https://majadigi.jatimprov.go.id/layanan/lynbangjol)
Tidak
Situs tidak bisa diakses meskipun sudah dimuat ulang
25
Siparimanta
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/siparimanta](https://majadigi.jatimprov.go.id/layanan/siparimanta)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
26
E-NTE
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-nte](https://majadigi.jatimprov.go.id/layanan/e-nte)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
27
RSUD Dr. Saiful Anwar
Rumah Sakit Umum Daerah Dr. Saiful Anwar Malang
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-dr.-saiful-anwar](https://majadigi.jatimprov.go.id/layanan/rsud-dr.-saiful-anwar)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
28
Daksa Budaya
Dinas Kebudayaan dan Pariwisata Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/daksa-budaya](https://majadigi.jatimprov.go.id/layanan/daksa-budaya)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik (langsung diarahkan ke halaman login akun pengguna)
29
SIRIP
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sirip](https://majadigi.jatimprov.go.id/layanan/sirip)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
30
E-KLIK (Elektronik Kepuasan Layanan Informasi dan Kepelabuhanan)
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-klik-elektronik-kepuasan-layanan-informasi-dan-kepelabuhanan](https://majadigi.jatimprov.go.id/layanan/e-klik-elektronik-kepuasan-layanan-informasi-dan-kepelabuhanan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
31
E-PSIKOTEST RSUD Karsa Husada Batu
Rumah Sakit Umum Daerah Karsa Husada Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-psikotest-rsud-karsa-husada-batu](https://majadigi.jatimprov.go.id/layanan/e-psikotest-rsud-karsa-husada-batu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
32
PROKESRA
Dinas Koperasi dan UKM Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/prokesra](https://majadigi.jatimprov.go.id/layanan/prokesra)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
33
Sistem Infromasi Semua Gerak Tanam Pohon Untuk Anak Cucu (SI-SEGER TANPO AC)
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-infromasi-semua-gerak-tanam-pohon-untuk-anak-cucu-si-seger-tanpo-ac](https://majadigi.jatimprov.go.id/layanan/sistem-infromasi-semua-gerak-tanam-pohon-untuk-anak-cucu-si-seger-tanpo-ac)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
34
Harga Bahan Pokok (SISKAPERBAPO)
Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/harga-bahan-pokok-siskaperbapo](https://majadigi.jatimprov.go.id/layanan/harga-bahan-pokok-siskaperbapo)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
35
RSUD Karsa Husada Batu
Rumah Sakit Umum Daerah Karsa Husada Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-karsa-husada-batu](https://majadigi.jatimprov.go.id/layanan/rsud-karsa-husada-batu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
36
Khas Jatim
Dinas Perpustakaan dan Kearsipan
Level 1
[https://majadigi.jatimprov.go.id/layanan/khas-jatim](https://majadigi.jatimprov.go.id/layanan/khas-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
37
PASPOL (Pelayanan Administrasi Sertifikasi Produk Online)
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/paspol-pelayanan-administrasi-sertifikasi-produk-online](https://majadigi.jatimprov.go.id/layanan/paspol-pelayanan-administrasi-sertifikasi-produk-online)
Aktif
Link langsung diarahkan ke halaman login akun pengguna
38
JALAK LOEWE Publik
Dinas Pekerjaan Umum Bina Marga
Level 1
[https://majadigi.jatimprov.go.id/layanan/jalak-loewe-publik](https://majadigi.jatimprov.go.id/layanan/jalak-loewe-publik)
Aktif
Hanya menampilkan link sosia media (bisa diakses)
39
Sisten Informasi Kegiatan Penyuluh Kehutanan (SIGALUH)
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sisten-informasi-kegiatan-penyuluh-kehutanan-sigaluh](https://majadigi.jatimprov.go.id/layanan/sisten-informasi-kegiatan-penyuluh-kehutanan-sigaluh)
Aktif
Link langsung diarahkan ke halaman login akun pengguna
40
Silat Proling (Sistem Informasi Pelatihan Probolinggo)
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/silat-proling-sistem-informasi-pelatihan-probolinggo](https://majadigi.jatimprov.go.id/layanan/silat-proling-sistem-informasi-pelatihan-probolinggo)
Tidak
Link diakses menunjukkan "This page isn’t working" (404)
41
SIM RUSUN
Dinas Perumahan Rakyat, Kawasan Pemukiman dan Cipta Karya
Level 1
[https://majadigi.jatimprov.go.id/layanan/sim-rusun](https://majadigi.jatimprov.go.id/layanan/sim-rusun)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
42
Mudik Gratis
Dinas Perhubungan
Level 1
[https://majadigi.jatimprov.go.id/layanan/mudik-gratis](https://majadigi.jatimprov.go.id/layanan/mudik-gratis)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
43
Peluang Investasi Proyek Jatim (POINT JATIM)
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 1
[https://majadigi.jatimprov.go.id/layanan/peluang-investasi-proyek-jatim-point-jatim](https://majadigi.jatimprov.go.id/layanan/peluang-investasi-proyek-jatim-point-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
44
Pendaftaran Bimbingan dan Pelatihan Perbenihan
Dinas Pertanian dan Ketahanan Pangan
Level 1
[https://majadigi.jatimprov.go.id/layanan/pendaftaran-bimbingan-dan-pelatihan-perbenihan](https://majadigi.jatimprov.go.id/layanan/pendaftaran-bimbingan-dan-pelatihan-perbenihan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
45
Klinik Online Disnak Jatim
Dinas Peternakan
Level 1
[https://majadigi.jatimprov.go.id/layanan/klinik-online-disnak-jatim](https://majadigi.jatimprov.go.id/layanan/klinik-online-disnak-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
46
SKOPI
Dinas Pekerjaan Umum Sumber Daya Air
Level 1
[https://majadigi.jatimprov.go.id/layanan/skopi](https://majadigi.jatimprov.go.id/layanan/skopi)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
47
Nomor Darurat
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/nomor-darurat](https://majadigi.jatimprov.go.id/layanan/nomor-darurat)
Aktif
Fitur terintegrasi berfungsi dengan baik
48
Infeksi Emerging
Dinas Kesehatan
Level 1
[https://majadigi.jatimprov.go.id/layanan/infeksi-emerging](https://majadigi.jatimprov.go.id/layanan/infeksi-emerging)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
49
SIAPP BLK Sumenep
Dinas Tenaga Kerja dan Transmigrasi Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/siapp-blk-sumenep](https://majadigi.jatimprov.go.id/layanan/siapp-blk-sumenep)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
50
SIBB
Dinas Pekerjaan Umum Sumber Daya Air
Level 1
[https://majadigi.jatimprov.go.id/layanan/sibb](https://majadigi.jatimprov.go.id/layanan/sibb)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
51
Badan Pendapatan Daerah (BAPENDA) Jawa Timur
Badan Pendapatan Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/badan-pendapatan-daerah-bapenda-jawa-timur](https://majadigi.jatimprov.go.id/layanan/badan-pendapatan-daerah-bapenda-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
52
Transjatim AJAIB 2.0
Dinas Perhubungan
Level 1
[https://majadigi.jatimprov.go.id/layanan/transjatim-ajaib-20](https://majadigi.jatimprov.go.id/layanan/transjatim-ajaib-20)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
53
ALAS BETA
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/alas-beta](https://majadigi.jatimprov.go.id/layanan/alas-beta)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
54
Pelayanan Karesidenan Digital Online (PAKIDO)
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur IV Pamekasan
Level 1
[https://majadigi.jatimprov.go.id/layanan/pelayanan-karesidenan-digital-online-pakido](https://majadigi.jatimprov.go.id/layanan/pelayanan-karesidenan-digital-online-pakido)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
55
PATRIA
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/patria](https://majadigi.jatimprov.go.id/layanan/patria)
Tidak
Link layanan tidak bisa diakses karena ada masalah pada keamanan.
56
SWANGGI Literasi Digital
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/swanggi-literasi-digital](https://majadigi.jatimprov.go.id/layanan/swanggi-literasi-digital)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
57
LAYANAN PENERBITAN REGISTRASI GAP KEBUN / LAHAN USAHA HORTIKULTURA
Dinas Pertanian dan Ketahanan Pangan
Level 1
[https://majadigi.jatimprov.go.id/layanan/layanan-penerbitan-registrasi-gap-kebun-lahan-usaha-hortikultura](https://majadigi.jatimprov.go.id/layanan/layanan-penerbitan-registrasi-gap-kebun-lahan-usaha-hortikultura)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
58
Pusaka Jawatimuran
Dinas Perpustakaan dan Kearsipan
Level 1
[https://majadigi.jatimprov.go.id/layanan/pusaka-jawatimuran](https://majadigi.jatimprov.go.id/layanan/pusaka-jawatimuran)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
59
GIIS
Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/giis](https://majadigi.jatimprov.go.id/layanan/giis)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
60
Tata Naskah Dinas Elektronik (TNDE)
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/tata-naskah-dinas-elektronik-tnde](https://majadigi.jatimprov.go.id/layanan/tata-naskah-dinas-elektronik-tnde)
Aktif
Link langsung diarahkan ke halaman login akun pengguna
61
SPMB Jawa Timur
Dinas Pendidikan
Level 1
[https://majadigi.jatimprov.go.id/layanan/spmb-jawa-timur](https://majadigi.jatimprov.go.id/layanan/spmb-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
62
SIJAWARA+
Dinas Koperasi dan UKM Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/sijawara+](https://majadigi.jatimprov.go.id/layanan/sijawara+)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
63
Buaian Jatim
Dinas Kesehatan
Level 1
[https://majadigi.jatimprov.go.id/layanan/buaian-jatim](https://majadigi.jatimprov.go.id/layanan/buaian-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
64
Pengajuan Sosialisasi P4GN
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/pengajuan-sosialisasi-p4gn](https://majadigi.jatimprov.go.id/layanan/pengajuan-sosialisasi-p4gn)
Aktif
Link langsung diarahkan pada form Pengajuan Permohonan Layanan Sosialisasi P4GN/Penyuluhan
65
JDIH Kota Blitar
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/jdih-kota-blitar](https://majadigi.jatimprov.go.id/layanan/jdih-kota-blitar)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
66
Asesmen Terpadu
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/asesmen-terpadu](https://majadigi.jatimprov.go.id/layanan/asesmen-terpadu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
67
SIPERKASA
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/siperkasa](https://majadigi.jatimprov.go.id/layanan/siperkasa)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
68
Skrining Mandiri TBC
Dinas Kesehatan
Level 1
[https://majadigi.jatimprov.go.id/layanan/skrining-mandiri-tbc](https://majadigi.jatimprov.go.id/layanan/skrining-mandiri-tbc)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
69
PALAPA MOJO
Kota Mojokerto
Level 1
[https://majadigi.jatimprov.go.id/layanan/palapa-mojo](https://majadigi.jatimprov.go.id/layanan/palapa-mojo)
Tidak
Saat link diakses muncul "Misdirected Request"
70
Teman RSSA
Rumah Sakit Umum Daerah Dr. Saiful Anwar Malang
Level 1
[https://majadigi.jatimprov.go.id/layanan/teman-rssa](https://majadigi.jatimprov.go.id/layanan/teman-rssa)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
71
PPID DISPORA JATIM
Dinas Kepemudaan dan Olahraga
Level 1
[https://majadigi.jatimprov.go.id/layanan/ppid-dispora-jatim](https://majadigi.jatimprov.go.id/layanan/ppid-dispora-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
72
E-Jurnal Cakrawala
Badan Riset dan Inovasi Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-jurnal-cakrawala](https://majadigi.jatimprov.go.id/layanan/e-jurnal-cakrawala)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
73
RSUD Haji Prov. Jatim
Rumah Sakit Umum Daerah Haji Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-haji-prov-jatim](https://majadigi.jatimprov.go.id/layanan/rsud-haji-prov-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
74
Website Biro Perekonomian Setda Prov. Jawa Timur
Biro Perekonomian
Level 1
[https://majadigi.jatimprov.go.id/layanan/website-biro-perekonomian-setda-prov.-jawa-timur](https://majadigi.jatimprov.go.id/layanan/website-biro-perekonomian-setda-prov.-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
75
IKI Pesat
Dinas Kesehatan
Level 1
[https://majadigi.jatimprov.go.id/layanan/iki-pesat](https://majadigi.jatimprov.go.id/layanan/iki-pesat)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
76
E-KWU Cabdin Pasuruan
Dinas Pendidikan
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-kwu-cabdin-pasuruan](https://majadigi.jatimprov.go.id/layanan/e-kwu-cabdin-pasuruan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
77
Garasi PUEM
Dinas Pemberdayaan Masyarakat dan Desa
Level 1
[https://majadigi.jatimprov.go.id/layanan/garasi-puem](https://majadigi.jatimprov.go.id/layanan/garasi-puem)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
78
GOPUTAR
Kabupaten Gresik
Level 1
[https://majadigi.jatimprov.go.id/layanan/goputar](https://majadigi.jatimprov.go.id/layanan/goputar)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
79
PPID Kota Batu
Kota Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/ppid-kota-batu](https://majadigi.jatimprov.go.id/layanan/ppid-kota-batu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
80
Ekosistem Halal
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur di Malang
Level 1
[https://majadigi.jatimprov.go.id/layanan/ekosistem-halal](https://majadigi.jatimprov.go.id/layanan/ekosistem-halal)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
81
PANDUCAKTI
Kabupaten Tulungagung
Level 1
[https://majadigi.jatimprov.go.id/layanan/panducakti](https://majadigi.jatimprov.go.id/layanan/panducakti)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
82
SIMPELPAS
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/simpelpas](https://majadigi.jatimprov.go.id/layanan/simpelpas)
Aktif
Link langsung mengarah ke website dan menampilkan halaman login
83
Kalbu Smart
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/kalbu-smart](https://majadigi.jatimprov.go.id/layanan/kalbu-smart)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
84
Smart Kampung Banyuwangi
Kabupaten Banyuwangi
Level 1
[https://majadigi.jatimprov.go.id/layanan/smart-kampung-banyuwangi](https://majadigi.jatimprov.go.id/layanan/smart-kampung-banyuwangi)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
85
SI START
Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/si-start](https://majadigi.jatimprov.go.id/layanan/si-start)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
86
PAK MO Gercep
Kota Mojokerto
Level 1
[https://majadigi.jatimprov.go.id/layanan/pak-mo-gercep](https://majadigi.jatimprov.go.id/layanan/pak-mo-gercep)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
87
Nganjuk Smart City
Kabupaten Nganjuk
Level 1
[https://majadigi.jatimprov.go.id/layanan/nganjuk-smart-city-1772622267](https://majadigi.jatimprov.go.id/layanan/nganjuk-smart-city-1772622267)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
88
LEK JUKI+
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/lek-juki+](https://majadigi.jatimprov.go.id/layanan/lek-juki+)
Aktif
Link diakses langsung menampilkan halaman login
89
SIAP Dinas PU SDA Jatim
Dinas Pekerjaan Umum Sumber Daya Air
Level 1
[https://majadigi.jatimprov.go.id/layanan/siap-dinas-pu-sda-jatim](https://majadigi.jatimprov.go.id/layanan/siap-dinas-pu-sda-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
90
SIAP Grak!
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/siap-grak!](https://majadigi.jatimprov.go.id/layanan/siap-grak!)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
91
Surabaya Single Window Alfa (SSWALFA)
Kota Surabaya
Level 1
[https://majadigi.jatimprov.go.id/layanan/surabaya-single-window-alfa-sswalfa](https://majadigi.jatimprov.go.id/layanan/surabaya-single-window-alfa-sswalfa)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
92
RSUD Dungus
Rumah Sakit Umum Daerah Dungus MADIUN
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-dungus](https://majadigi.jatimprov.go.id/layanan/rsud-dungus)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
93
Bakorwil I Madiun Fasilitasi (BaFast)
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur di Madiun
Level 1
[https://majadigi.jatimprov.go.id/layanan/bakorwil-i-madiun-fasilitasi-bafast](https://majadigi.jatimprov.go.id/layanan/bakorwil-i-madiun-fasilitasi-bafast)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
94
E-PEDUM Jatim
Biro Administrasi Pembangunan
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-pedum-jatim](https://majadigi.jatimprov.go.id/layanan/e-pedum-jatim)
Aktif
Link layanan dapat diakses dan ada manual book untuk menggunakan web tersebut
95
Plasa Situbondo
Kabupaten Situbondo
Level 1
[https://majadigi.jatimprov.go.id/layanan/plasa-situbondo](https://majadigi.jatimprov.go.id/layanan/plasa-situbondo)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
96
Jatim Online Single Submission (JOSS)
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 1
[https://majadigi.jatimprov.go.id/layanan/jatim-online-single-submission-joss](https://majadigi.jatimprov.go.id/layanan/jatim-online-single-submission-joss)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
97
PODO KAYAIN JATIM
Badan Riset dan Inovasi Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/podo-kayain-jatim](https://majadigi.jatimprov.go.id/layanan/podo-kayain-jatim)
Tidak
Link diakses menunjukkan "This page isn’t working"
98
Data Penerima & Info Program Bansos (SAPA BANSOS)
Dinas Sosial
Level 1
[https://majadigi.jatimprov.go.id/layanan/data-penerima--info-program-bansos-sapa-bansos](https://majadigi.jatimprov.go.id/layanan/data-penerima--info-program-bansos-sapa-bansos)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
99
RSUD Daha Husada
Rumah Sakit Umum Daerah Daha Husada
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-daha-husada](https://majadigi.jatimprov.go.id/layanan/rsud-daha-husada)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
100
Portal Layanan Informasi DPRD Provinsi Jawa Timur
DPRD
Level 1
[https://majadigi.jatimprov.go.id/layanan/portal-layanan-informasi-dprd-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/portal-layanan-informasi-dprd-provinsi-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
101
Sistem Informasi Pelayanan AdminduK (SIPAK)
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-pelayanan-adminduk-sipak](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-pelayanan-adminduk-sipak)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
102
Rehabilitasi Narkoba Rawat Jalan
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/rehabilitasi-narkoba-rawat-jalan](https://majadigi.jatimprov.go.id/layanan/rehabilitasi-narkoba-rawat-jalan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
103
Cak Durasim App
Dinas Kebudayaan dan Pariwisata Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/cak-durasim-app](https://majadigi.jatimprov.go.id/layanan/cak-durasim-app)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
104
Website Official Bakorwil IV Pamekasan
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur IV Pamekasan
Level 1
[https://majadigi.jatimprov.go.id/layanan/website-official-bakorwil-iv-pamekasan](https://majadigi.jatimprov.go.id/layanan/website-official-bakorwil-iv-pamekasan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
105
Satu Data Jawa Timur
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/satu-data-jawa-timur](https://majadigi.jatimprov.go.id/layanan/satu-data-jawa-timur)
Aktif
Link diakses langsung menampilkan halaman login
106
TATTAS
Kota Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/tattas](https://majadigi.jatimprov.go.id/layanan/tattas)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
107
SIPAK
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/sipak](https://majadigi.jatimprov.go.id/layanan/sipak)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
108
Sigpukan
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sigpukan](https://majadigi.jatimprov.go.id/layanan/sigpukan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
109
Permohonan Deteksi Dini/Tes Urine
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/permohonan-deteksi-dinites-urine](https://majadigi.jatimprov.go.id/layanan/permohonan-deteksi-dinites-urine)
Aktif
Link layanan langsung menampilkan form Pengajuan Layanan Tes Urine Deteksi Dini Untuk Instansi/POKMAS
110
Klampid New Generation (KNG)
Kota Surabaya
Level 1
[https://majadigi.jatimprov.go.id/layanan/klampid-new-generation-kng](https://majadigi.jatimprov.go.id/layanan/klampid-new-generation-kng)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
111
SITARLIN UPT BLK Situbondo
Dinas Tenaga Kerja dan Transmigrasi Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/sitarlin-upt-blk-situbondo](https://majadigi.jatimprov.go.id/layanan/sitarlin-upt-blk-situbondo)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
112
Layangan Gemas RSUD Haji Jawa Timur
Rumah Sakit Umum Daerah Haji Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/layangan-gemas-rsud-haji-jawa-timur](https://majadigi.jatimprov.go.id/layanan/layangan-gemas-rsud-haji-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
113
SI GALON RSMM JATIM
Rumah Sakit Mata Masyarakat Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/si-galon-rsmm-jatim](https://majadigi.jatimprov.go.id/layanan/si-galon-rsmm-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
114
Fish Info Jatim
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/fish-info-jatim](https://majadigi.jatimprov.go.id/layanan/fish-info-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
115
Sipetarungsilat
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sipetarungsilat](https://majadigi.jatimprov.go.id/layanan/sipetarungsilat)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
116
Data Room KPBU
Biro Administrasi Pembangunan
Level 1
[https://majadigi.jatimprov.go.id/layanan/data-room-kpbu](https://majadigi.jatimprov.go.id/layanan/data-room-kpbu)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
117
PSMB LT
Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/psmb-lt](https://majadigi.jatimprov.go.id/layanan/psmb-lt)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
118
GUS (Gresik Urus Stunting)
Kabupaten Gresik
Level 1
[https://majadigi.jatimprov.go.id/layanan/gus-gresik-urus-stunting](https://majadigi.jatimprov.go.id/layanan/gus-gresik-urus-stunting)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
119
Pemetaan Rawan Gangguan Trantibumlinmas
Satuan Polisi Pamong Praja
Level 1
[https://majadigi.jatimprov.go.id/layanan/pemetaan-rawan-gangguan-trantibumlinmas](https://majadigi.jatimprov.go.id/layanan/pemetaan-rawan-gangguan-trantibumlinmas)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
120
Sistem Informasi Pencatatan Terpadu Elektronik Kehutanan (SICANTIK-HUT)
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-pencatatan-terpadu-elektronik-kehutanan-sicantik-hut](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-pencatatan-terpadu-elektronik-kehutanan-sicantik-hut)
Tidak
Link layanan saat diakses menunjukkan "This page isn’t working"
121
Si-Alin
Dinas Perhubungan
Level 1
[https://majadigi.jatimprov.go.id/layanan/si-alin](https://majadigi.jatimprov.go.id/layanan/si-alin)
Aktif
122
Sistem Informasi Pelayanan Rekomendasi Pemenuhan Sertifikat Standar Dan Pelaporan Online (SIAPOL)
Dinas Perhubungan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-pelayanan-rekomendasi-pemenuhan-sertifikat-standar-dan-pelaporan-online-siapol](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-pelayanan-rekomendasi-pemenuhan-sertifikat-standar-dan-pelaporan-online-siapol)
Aktif
123
Inspektorat Provinsi Jawa Timur
Inspektorat
Level 1
[https://majadigi.jatimprov.go.id/layanan/inspektorat-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/inspektorat-provinsi-jawa-timur)
Aktif
124
Portal Informasi Resmi Dinas Energi dan Sumber Daya Mineral Provinsi Jawa Timur
Dinas Energi dan Sumber Daya Mineral
Level 1
[https://majadigi.jatimprov.go.id/layanan/portal-informasi-resmi-dinas-energi-dan-sumber-daya-mineral-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/portal-informasi-resmi-dinas-energi-dan-sumber-daya-mineral-provinsi-jawa-timur)
Aktif
125
Pendidikan Anti Korupsi
Dinas Pendidikan
Level 1
[https://majadigi.jatimprov.go.id/layanan/pendidikan-anti-korupsi](https://majadigi.jatimprov.go.id/layanan/pendidikan-anti-korupsi)
Aktif
126
Website Biro Umum
Biro Umum Sekretariat Daerah Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/website-biro-umum](https://majadigi.jatimprov.go.id/layanan/website-biro-umum)
Aktif
127
Whistleblowing System DPMPTSP Jatim
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 1
[https://majadigi.jatimprov.go.id/layanan/whistleblowing-system-dpmptsp-jatim](https://majadigi.jatimprov.go.id/layanan/whistleblowing-system-dpmptsp-jatim)
Aktif
128
RS Paru Manguharjo Provinsi Jawa Timur
Rumah Sakit Paru Manguharjo Madiun
Level 1
[https://majadigi.jatimprov.go.id/layanan/rs-paru-manguharjo-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/rs-paru-manguharjo-provinsi-jawa-timur)
Aktif
129
Sistem Informasi Geologi dan Air Tanah (SIGNAL)
Dinas Energi dan Sumber Daya Mineral
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-geologi-dan-air-tanah-signal](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-geologi-dan-air-tanah-signal)
Aktif
130
Sistem Informasi Penatausahaan hasil Hutan Rakyat (SIPUHH-R)
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-penatausahaan-hasil-hutan-rakyat--sipuhh-r](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-penatausahaan-hasil-hutan-rakyat--sipuhh-r)
Aktif
131
East Java Virtual Tour 360
Dinas Kebudayaan dan Pariwisata Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/east-java-virtual-tour-360](https://majadigi.jatimprov.go.id/layanan/east-java-virtual-tour-360)
Aktif
132
OPEN DATA Kota Blitar
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/open-data-kota-blitar](https://majadigi.jatimprov.go.id/layanan/open-data-kota-blitar)
Aktif
133
RSUD dr. Soedono Madiun
Rumah Sakit Umum Daerah dr. Soedono Madiun
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-dr.-soedono-madiun](https://majadigi.jatimprov.go.id/layanan/rsud-dr.-soedono-madiun)
Aktif
134
Putik Cemerlang
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/putik-cemerlang](https://majadigi.jatimprov.go.id/layanan/putik-cemerlang)
Aktif
135
Klinik BUMDesa
Dinas Pemberdayaan Masyarakat dan Desa
Level 1
[https://majadigi.jatimprov.go.id/layanan/klinik-bumdesa](https://majadigi.jatimprov.go.id/layanan/klinik-bumdesa)
Aktif
136
Satu Data DISKOP UKM
Dinas Koperasi dan UKM Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/satu-data-diskop-ukm](https://majadigi.jatimprov.go.id/layanan/satu-data-diskop-ukm)
Aktif
137
Helpdesk DPMPTSP
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 1
[https://majadigi.jatimprov.go.id/layanan/helpdesk-dpmptsp](https://majadigi.jatimprov.go.id/layanan/helpdesk-dpmptsp)
Aktif
138
Rumah ASN
Badan Kepegawaian Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/rumah-asn-2](https://majadigi.jatimprov.go.id/layanan/rumah-asn-2)
Aktif
139
SITR
Dinas Perumahan Rakyat, Kawasan Pemukiman dan Cipta Karya
Level 1
[https://majadigi.jatimprov.go.id/layanan/sitr](https://majadigi.jatimprov.go.id/layanan/sitr)
Aktif
140
SIJALINMAJA
Satuan Polisi Pamong Praja
Level 1
[https://majadigi.jatimprov.go.id/layanan/sijalinmaja](https://majadigi.jatimprov.go.id/layanan/sijalinmaja)
Aktif
141
Produk Hukum Jawa Timur (JDIH)
Biro Hukum
Level 1
[https://majadigi.jatimprov.go.id/layanan/produk-hukum-jawa-timur-jdih](https://majadigi.jatimprov.go.id/layanan/produk-hukum-jawa-timur-jdih)
Aktif
142
Website resmi Badan Kesatuan Bangsa dan Politik Provinsi Jawa Timur
Badan Kesatuan Bangsa dan Politik
Level 1
[https://majadigi.jatimprov.go.id/layanan/website-resmi-badan-kesatuan-bangsa-dan-politik-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/website-resmi-badan-kesatuan-bangsa-dan-politik-provinsi-jawa-timur)
Aktif
Halaman tidak ditemukan
143
WBS Jatim
Inspektorat
Level 1
[https://majadigi.jatimprov.go.id/layanan/wbs-jatim](https://majadigi.jatimprov.go.id/layanan/wbs-jatim)
Aktif
144
SILABI
Dinas Lingkungan Hidup Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/silabi](https://majadigi.jatimprov.go.id/layanan/silabi)
Aktif
145
Sibermata Desa
Dinas Pemberdayaan Masyarakat dan Desa
Level 1
[https://majadigi.jatimprov.go.id/layanan/sibermata-desa](https://majadigi.jatimprov.go.id/layanan/sibermata-desa)
Aktif
146
Lapor Potensi Longsor
Badan Penanggulangan Bencana Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/lapor-potensi-longsor](https://majadigi.jatimprov.go.id/layanan/lapor-potensi-longsor)
Aktif
147
Islamic Center
Biro Kesejahteraan Rakyat
Level 1
[https://majadigi.jatimprov.go.id/layanan/islamic-center](https://majadigi.jatimprov.go.id/layanan/islamic-center)
Aktif
148
RSUD Husada Prima
Rumah Sakit Daerah Husada Prima
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-husada-prima](https://majadigi.jatimprov.go.id/layanan/rsud-husada-prima)
Aktif
149
Forum Konsultasi Disnak Jatim
Dinas Peternakan
Level 1
[https://majadigi.jatimprov.go.id/layanan/forum-konsultasi-disnak-jatim](https://majadigi.jatimprov.go.id/layanan/forum-konsultasi-disnak-jatim)
Aktif
150
SiDOTI
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/sidoti](https://majadigi.jatimprov.go.id/layanan/sidoti)
Aktif
hanya ada sosial media dan aplikasi mobile
151
SIM Jalan
Dinas Pekerjaan Umum Bina Marga
Level 1
[https://majadigi.jatimprov.go.id/layanan/sim-jalan](https://majadigi.jatimprov.go.id/layanan/sim-jalan)
Aktif
152
RS Menur
Rumah Sakit Jiwa Menur
Level 1
[https://majadigi.jatimprov.go.id/layanan/rs-menur](https://majadigi.jatimprov.go.id/layanan/rs-menur)
Aktif
153
Forum Bisnis Disnak Jatim
Dinas Peternakan
Level 1
[https://majadigi.jatimprov.go.id/layanan/forum-bisnis-disnak-jatim](https://majadigi.jatimprov.go.id/layanan/forum-bisnis-disnak-jatim)
Aktif
154
Ruang Juru
Dinas Pekerjaan Umum Sumber Daya Air
Level 1
[https://majadigi.jatimprov.go.id/layanan/ruang-juru](https://majadigi.jatimprov.go.id/layanan/ruang-juru)
Aktif
155
DIOPEN
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/diopen](https://majadigi.jatimprov.go.id/layanan/diopen)
Aktif
156
MPP Among Warga Kota Batu
Kota Batu
Level 1
[https://majadigi.jatimprov.go.id/layanan/mpp-among-warga-kota-batu](https://majadigi.jatimprov.go.id/layanan/mpp-among-warga-kota-batu)
Tidak
Link layanan tidak bisa diakses
157
SABI BISA!
Dinas Sosial
Level 1
[https://majadigi.jatimprov.go.id/layanan/sabi-bisa!](https://majadigi.jatimprov.go.id/layanan/sabi-bisa!)
Aktif
158
Hallo BNNP Jatim
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/hallo-bnnp-jatim](https://majadigi.jatimprov.go.id/layanan/hallo-bnnp-jatim)
Aktif
159
JEJAK
Dinas Perumahan Rakyat, Kawasan Pemukiman dan Cipta Karya
Level 1
[https://majadigi.jatimprov.go.id/layanan/jejak](https://majadigi.jatimprov.go.id/layanan/jejak)
Aktif
160
SIH3 Provinsi Jawa Timur
Dinas Pekerjaan Umum Sumber Daya Air
Level 1
[https://majadigi.jatimprov.go.id/layanan/sih3-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/sih3-provinsi-jawa-timur)
Aktif
161
Layanan Adminduk Kota Probolinggo
Kota Probolinggo
Level 1
[https://majadigi.jatimprov.go.id/layanan/layanan-adminduk-kota-probolinggo](https://majadigi.jatimprov.go.id/layanan/layanan-adminduk-kota-probolinggo)
Aktif
162
DESIS RSMM JATIM
Rumah Sakit Mata Masyarakat Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/desis-rsmm-jatim](https://majadigi.jatimprov.go.id/layanan/desis-rsmm-jatim)
Aktif
163
SIPENERANG
Dinas Kehutanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/sipenerang](https://majadigi.jatimprov.go.id/layanan/sipenerang)
Aktif
164
SIBANTEK
Dinas Perumahan Rakyat, Kawasan Pemukiman dan Cipta Karya
Level 1
[https://majadigi.jatimprov.go.id/layanan/sibantek](https://majadigi.jatimprov.go.id/layanan/sibantek)
Aktif
165
Digital Jatim (dJatim)
Dinas Perpustakaan dan Kearsipan
Level 1
[https://majadigi.jatimprov.go.id/layanan/digital-jatim-djatim](https://majadigi.jatimprov.go.id/layanan/digital-jatim-djatim)
Aktif
166
Sistem Informasi Magang Diskominfo Jatim
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-magang-diskominfo-jatim](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-magang-diskominfo-jatim)
Aktif
167
ULPIM Kota Blitar
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/ulpim-kota-blitar](https://majadigi.jatimprov.go.id/layanan/ulpim-kota-blitar)
Aktif
168
Informasi Bahan Bangunan (IBB)
Dinas Perumahan Rakyat, Kawasan Pemukiman dan Cipta Karya
Level 1
[https://majadigi.jatimprov.go.id/layanan/informasi-bahan-bangunan-ibb](https://majadigi.jatimprov.go.id/layanan/informasi-bahan-bangunan-ibb)
Aktif
169
PPID Bakorwil III Malang
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur di Malang
Level 1
[https://majadigi.jatimprov.go.id/layanan/ppid-bakorwil-iii-malang](https://majadigi.jatimprov.go.id/layanan/ppid-bakorwil-iii-malang)
Aktif
170
Penggunaan Ruangan EJSC Bakorwil IV Pamekasan
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur IV Pamekasan
Level 1
[https://majadigi.jatimprov.go.id/layanan/penggunaan-ruangan-ejsc-bakorwil-iv-pamekasan](https://majadigi.jatimprov.go.id/layanan/penggunaan-ruangan-ejsc-bakorwil-iv-pamekasan)
Aktif
hanya berupa google form
171
Jatim Berdasi
Badan Riset dan Inovasi Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/jatim-berdasi](https://majadigi.jatimprov.go.id/layanan/jatim-berdasi)
Aktif
172
SIAP SEWA Aset Jawa Timur
Badan Pengelola Keuangan dan Aset Daerah
Level 1
[https://majadigi.jatimprov.go.id/layanan/siap-sewa-aset-jawa-timur](https://majadigi.jatimprov.go.id/layanan/siap-sewa-aset-jawa-timur)
Aktif
173
LECA (Layanan Elektronik Certificate Of Analysis)
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/leca-layanan-elektronik-certificate-of-analysis](https://majadigi.jatimprov.go.id/layanan/leca-layanan-elektronik-certificate-of-analysis)
Aktif
174
Gresik Satu Data
Kabupaten Gresik
Level 1
[https://majadigi.jatimprov.go.id/layanan/gresik-satu-data](https://majadigi.jatimprov.go.id/layanan/gresik-satu-data)
Tidak
sistem sedang maintenance
175
EKO TREN OPOP JATIM
Dinas Koperasi dan UKM Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/eko-tren-opop-jatim](https://majadigi.jatimprov.go.id/layanan/eko-tren-opop-jatim)
Aktif
176
SIKIPO (Sistem Keterbukaan Informasi Publik Online)
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/sikipo-sistem-keterbukaan-informasi-publik-online](https://majadigi.jatimprov.go.id/layanan/sikipo-sistem-keterbukaan-informasi-publik-online)
Aktif
177
LANANG MAS
Satuan Polisi Pamong Praja
Level 1
[https://majadigi.jatimprov.go.id/layanan/lanang-mas](https://majadigi.jatimprov.go.id/layanan/lanang-mas)
Aktif
178
ID MAP (Integrasi Data Mangrove dan Pesisir)
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/id-map-integrasi-data-mangrove-dan-pesisir](https://majadigi.jatimprov.go.id/layanan/id-map-integrasi-data-mangrove-dan-pesisir)
Aktif
179
PPID RSUD DUNGUS
Rumah Sakit Umum Daerah Dungus MADIUN
Level 1
[https://majadigi.jatimprov.go.id/layanan/ppid-rsud-dungus](https://majadigi.jatimprov.go.id/layanan/ppid-rsud-dungus)
Aktif
180
Informasi Cagar Budaya Provinsi Jawa Timur (INCAR)
Dinas Kebudayaan dan Pariwisata Provinsi Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/informasi-cagar-budaya-provinsi-jawa-timur-incar](https://majadigi.jatimprov.go.id/layanan/informasi-cagar-budaya-provinsi-jawa-timur-incar)
Aktif
181
Beasiswa LPPD Jatim
Biro Kesejahteraan Rakyat
Level 1
[https://majadigi.jatimprov.go.id/layanan/beasiswa-lppd-jatim](https://majadigi.jatimprov.go.id/layanan/beasiswa-lppd-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
182
RSMM Jawa Timur
Rumah Sakit Mata Masyarakat Jawa Timur
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsmm-jawa-timur](https://majadigi.jatimprov.go.id/layanan/rsmm-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
183
LAPOR PAK (Layanan Perempuan dan Anak Korban Kekerasan)
Dinas Pemberdayaan Perempuan, Perlindungan Anak dan Kependudukan
Level 1
[https://majadigi.jatimprov.go.id/layanan/lapor-pak-layanan-perempuan-dan-anak-korban-kekerasan](https://majadigi.jatimprov.go.id/layanan/lapor-pak-layanan-perempuan-dan-anak-korban-kekerasan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
184
Informasi Antrian Pasien
Rumah Sakit Paru Manguharjo Madiun
Level 1
[https://majadigi.jatimprov.go.id/layanan/informasi-antrian-pasien](https://majadigi.jatimprov.go.id/layanan/informasi-antrian-pasien)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
185
Fast Hydrology Data
Dinas Pekerjaan Umum Sumber Daya Air
Level 1
[https://majadigi.jatimprov.go.id/layanan/fast-hydrology-data](https://majadigi.jatimprov.go.id/layanan/fast-hydrology-data)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
186
Pemesanan Bibit Holtikultura
Dinas Pertanian dan Ketahanan Pangan
Level 1
[https://majadigi.jatimprov.go.id/layanan/pemesanan-bibit-holtikultura](https://majadigi.jatimprov.go.id/layanan/pemesanan-bibit-holtikultura)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
187
SIDBANKUMDA JATIM
Biro Hukum
Level 1
[https://majadigi.jatimprov.go.id/layanan/sidbankumda-jatim](https://majadigi.jatimprov.go.id/layanan/sidbankumda-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
188
RSUD Dr. Soetomo
Rumah Sakit Umum Daerah Dr. Soetomo
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-dr-soetomo](https://majadigi.jatimprov.go.id/layanan/rsud-dr-soetomo)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
189
Sistem Informasi Magang Bakorwil III Malang
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur di Malang
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-informasi-magang-bakorwil-iii-malang](https://majadigi.jatimprov.go.id/layanan/sistem-informasi-magang-bakorwil-iii-malang)
Aktif
190
Prakiraan Cuaca dan Informasi Gempa Bumi
Badan Meteorologi, Klimatologi, dan Geofisika
Level 1
[https://majadigi.jatimprov.go.id/layanan/prakiraan-cuaca-dan-informasi-gempa-bumi](https://majadigi.jatimprov.go.id/layanan/prakiraan-cuaca-dan-informasi-gempa-bumi)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
191
KOLAK PISANG
Dinas Pemberdayaan Perempuan, Perlindungan Anak dan Kependudukan
Level 1
[https://majadigi.jatimprov.go.id/layanan/kolak-pisang](https://majadigi.jatimprov.go.id/layanan/kolak-pisang)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
192
SIISKA
Dinas Pendidikan
Level 1
[https://majadigi.jatimprov.go.id/layanan/siiska](https://majadigi.jatimprov.go.id/layanan/siiska)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
193
Sistem Peminjaman Ruangan Online
Badan Koordinasi Wilayah Pemerintahan dan Pembangunan Provinsi Jawa Timur di Malang
Level 1
[https://majadigi.jatimprov.go.id/layanan/sistem-peminjaman-ruangan-online](https://majadigi.jatimprov.go.id/layanan/sistem-peminjaman-ruangan-online)
Aktif
194
RSUD Sumberglagah
Rumah Sakit Umum Daerah Sumber Glagah
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-sumberglagah](https://majadigi.jatimprov.go.id/layanan/rsud-sumberglagah)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
195
SIMASDANIS (Sistem Informasi Manajemen Arsip Surat, Disposisi dan Informasi)
Dinas Kelautan dan Perikanan
Level 1
[https://majadigi.jatimprov.go.id/layanan/simasdanis-sistem-informasi-manajemen-arsip-surat-disposisi-dan-informasi](https://majadigi.jatimprov.go.id/layanan/simasdanis-sistem-informasi-manajemen-arsip-surat-disposisi-dan-informasi)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
196
Tuban Online Single Submission (TOSS)
Kabupaten Tuban
Level 1
[https://majadigi.jatimprov.go.id/layanan/tuban-online-single-submission-toss](https://majadigi.jatimprov.go.id/layanan/tuban-online-single-submission-toss)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
197
Jatim Lapor
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/jatim-lapor](https://majadigi.jatimprov.go.id/layanan/jatim-lapor)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
198
Satu Peta Jawa Timur
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/satu-peta-jawa-timur](https://majadigi.jatimprov.go.id/layanan/satu-peta-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
199
Sukma-e Jatim
Biro Organisasi
Level 1
[https://majadigi.jatimprov.go.id/layanan/sukma-e-jatim](https://majadigi.jatimprov.go.id/layanan/sukma-e-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
200
Penerbitan SKHPN PNBP
Badan Narkotika Nasional
Level 1
[https://majadigi.jatimprov.go.id/layanan/penerbitan-skhpn-pnbp](https://majadigi.jatimprov.go.id/layanan/penerbitan-skhpn-pnbp)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
201
JDIH Sekretariat DPRD Provinsi Jawa Timur
DPRD
Level 1
[https://majadigi.jatimprov.go.id/layanan/jdih-sekretariat-dprd-provinsi-jawa-timur](https://majadigi.jatimprov.go.id/layanan/jdih-sekretariat-dprd-provinsi-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
202
Open Data
Dinas Komunikasi & Informatika
Level 1
[https://majadigi.jatimprov.go.id/layanan/open-data-2](https://majadigi.jatimprov.go.id/layanan/open-data-2)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
203
Aplikasi Sistem Manajemen Sertifikat Pengembangan Kompetensi (SEMESTABANGKOM)
Badan Pengembangan Sumber Daya Manusia
Level 1
[https://majadigi.jatimprov.go.id/layanan/aplikasi-sistem-manajemen-sertifikat-pengembangan-kompetensi-semestabangkom](https://majadigi.jatimprov.go.id/layanan/aplikasi-sistem-manajemen-sertifikat-pengembangan-kompetensi-semestabangkom)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
204
E-Perizinan Kota Blitar
Kota Blitar
Level 1
[https://majadigi.jatimprov.go.id/layanan/e-perizinan-kota-blitar](https://majadigi.jatimprov.go.id/layanan/e-perizinan-kota-blitar)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
205
RSUD Mohammad Noer Pamekasan
Rumah Sakit Umum Daerah Mohammad Noer Pamekasan
Level 1
[https://majadigi.jatimprov.go.id/layanan/rsud-mohammad-noer-pamekasan](https://majadigi.jatimprov.go.id/layanan/rsud-mohammad-noer-pamekasan)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
206
Info Pajak Kendaraan Bermotor
Badan Pendapatan Daerah
Level 2
[https://majadigi.jatimprov.go.id/fitur/bapenda-jatim-pkb](https://majadigi.jatimprov.go.id/fitur/bapenda-jatim-pkb)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
207
Info Nilai Jual Kendaraan Bermotor
Badan Pendapatan Daerah
Level 2
[https://majadigi.jatimprov.go.id/fitur/bapenda-jatim-njkb](https://majadigi.jatimprov.go.id/fitur/bapenda-jatim-njkb)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
208
Open Data
Dinas Komunikasi & Informatika
Level 2
[https://majadigi.jatimprov.go.id/fitur/open-data](https://majadigi.jatimprov.go.id/fitur/open-data)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
209
Peluang Investasi Proyek Jatim
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 2
[https://majadigi.jatimprov.go.id/fitur/point-jatim](https://majadigi.jatimprov.go.id/fitur/point-jatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
210
E-Tracking (JOSS)
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 2
[https://majadigi.jatimprov.go.id/fitur/joss-etracking](https://majadigi.jatimprov.go.id/fitur/joss-etracking)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
211
Daftar Perizinan (JOSS)
Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu
Level 1
[https://joss.jatimprov.go.id/](https://joss.jatimprov.go.id/)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
212
Ketersediaan Kamar Rawat
Rumah Sakit Umum Daerah Karsa Husada Batu
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-karsa-husada-ketersediaan-kamar-rawat](https://majadigi.jatimprov.go.id/fitur/rsud-karsa-husada-ketersediaan-kamar-rawat)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
213
Harga Bahan Pokok (SISKAPERBAPO)
Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur
Level 2
[https://majadigi.jatimprov.go.id/fitur/hargabahanpokok](https://majadigi.jatimprov.go.id/fitur/hargabahanpokok)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
214
Klinik Hoaks
Dinas Komunikasi & Informatika
Level 2
[https://majadigi.jatimprov.go.id/fitur/klinik-hoaks](https://majadigi.jatimprov.go.id/fitur/klinik-hoaks)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
215
Jatim Lapor
Dinas Komunikasi & Informatika
Level 1
[https://jatim.lapor.go.id/](https://jatim.lapor.go.id/)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
216
Ketersediaan Kamar Rawat
Rumah Sakit Umum Daerah Daha Husada
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-daha-husada-ketersediaan-kamar-rawat](https://majadigi.jatimprov.go.id/fitur/rsud-daha-husada-ketersediaan-kamar-rawat)
Aktif
217
Jadwal Operasi
Rumah Sakit Umum Daerah Daha Husada
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-daha-husada-jadwal-operasi](https://majadigi.jatimprov.go.id/fitur/rsud-daha-husada-jadwal-operasi)
Aktif
218
Info Antrian Pasien
Rumah Sakit Umum Daerah Daha Husada
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-daha-husada-info-antrian-pasien](https://majadigi.jatimprov.go.id/fitur/rsud-daha-husada-info-antrian-pasien)
Aktif
219
Kontak Darurat
Dinas Komunikasi & Informatika
Level 2
[https://majadigi.jatimprov.go.id/fitur/nomordarurat](https://majadigi.jatimprov.go.id/fitur/nomordarurat)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
220
Info Kamar RSUD Haji
Rumah Sakit Umum Daerah Haji Provinsi Jawa Timur
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-haji-ketersediaan-kamar-rawat](https://majadigi.jatimprov.go.id/fitur/rsud-haji-ketersediaan-kamar-rawat)
Aktif
221
Naskah Kuno Jawa Timur
Dinas Perpustakaan dan Kearsipan
Level 3
[https://majadigi.jatimprov.go.id/fitur/naskah-kuno-jawa-timur](https://majadigi.jatimprov.go.id/fitur/naskah-kuno-jawa-timur)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
222
Skrining Mandiri TBC (E-TIBI)
Dinas Kesehatan
Level 3
[https://majadigi.jatimprov.go.id/fitur/skrining-e-tibi](https://majadigi.jatimprov.go.id/fitur/skrining-e-tibi)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
223
Login Smart Kampung Banyuwangi
Kabupaten Banyuwangi
Level 3
[https://sso.jatimprov.go.id/realms/JATIM/protocol/openid-connect/auth?response_type=code&client_id=diskominfo-banyuwangi&redirect_uri=https://smartkampung.id/admin/callback_sso&scope=openid+email+profile&state=cdcfb4a910989e00](https://sso.jatimprov.go.id/realms/JATIM/protocol/openid-connect/auth?response_type=code&client_id=diskominfo-banyuwangi&redirect_uri=https://smartkampung.id/admin/callback_sso&scope=openid+email+profile&state=cdcfb4a910989e00)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
224
Login Tuban Online Single Submission
Kabupaten Tuban
Level 3
[https://sso.jatimprov.go.id/realms/JATIM/protocol/openid-connect/auth?client_id=diskominfo-tuban&scope=openid+email+profile&response_type=code&redirect_uri=https%3A%2F%2Ftoss.tubankab.go.id%2Fssocallback&state=b77fbb40b66a1b25](https://sso.jatimprov.go.id/realms/JATIM/protocol/openid-connect/auth?client_id=diskominfo-tuban&scope=openid+email+profile&response_type=code&redirect_uri=https%3A%2F%2Ftoss.tubankab.go.id%2Fssocallback&state=b77fbb40b66a1b25)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
225
Statistik Trantibum
Satuan Polisi Pamong Praja
Level 2
[https://majadigi.jatimprov.go.id/fitur/sijalinmaja](https://majadigi.jatimprov.go.id/fitur/sijalinmaja)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
226
Login SSWALFA Surabaya
Kota Surabaya
Level 3
[https://sswalfa.surabaya.go.id/portaljatim](https://sswalfa.surabaya.go.id/portaljatim)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
227
Gedung Islamic Center
Biro Kesejahteraan Rakyat
Level 2
[https://majadigi.jatimprov.go.id/fitur/islamiccenter](https://majadigi.jatimprov.go.id/fitur/islamiccenter)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
228
Ketersediaan Kamar Rawat
Rumah Sakit Umum Daerah Dr. Saiful Anwar Malang
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-dr-saiful-anwar-ketersediaan-kamar-rawat](https://majadigi.jatimprov.go.id/fitur/rsud-dr-saiful-anwar-ketersediaan-kamar-rawat)
Aktif
229
Mudik Gratis 2026
Dinas Perhubungan
Level 2
[https://majadigi.jatimprov.go.id/fitur/mudik-gratis](https://majadigi.jatimprov.go.id/fitur/mudik-gratis)
Aktif
230
Tiket Trans Jatim
Dinas Perhubungan
Level 2
[https://majadigi.jatimprov.go.id/fitur/transjatim-info-tarif](https://majadigi.jatimprov.go.id/fitur/transjatim-info-tarif)
Tidak
API Transjatim tidak bisa diakses
231
Rute Transjatim
Dinas Perhubungan
Level 2
[https://majadigi.jatimprov.go.id/fitur/transjatim-info-rute](https://majadigi.jatimprov.go.id/fitur/transjatim-info-rute)
Tidak
API Transjatim tidak bisa diakses
232
Layanan Ambulance RSUD Dr. Soetomo
Rumah Sakit Umum Daerah Dr. Soetomo
Level 1
[https://itki.rsudrsoetomo.jatimprov.go.id/ambulans-rsds/public/index](https://itki.rsudrsoetomo.jatimprov.go.id/ambulans-rsds/public/index)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
233
Sistem Informasi Pasien RSUD Dr. Soetomo
Rumah Sakit Umum Daerah Dr. Soetomo
Level 1
[https://sips.rsudrsoetomo.jatimprov.go.id/](https://sips.rsudrsoetomo.jatimprov.go.id/)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
234
Pendaftaran Online RSUD Dr. Soetomo
Rumah Sakit Umum Daerah Dr. Soetomo
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-dr-soetomo-pendaftaran-online](https://majadigi.jatimprov.go.id/fitur/rsud-dr-soetomo-pendaftaran-online)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
235
Info Kamar RSUD Dr. Soetomo
Rumah Sakit Umum Daerah Dr. Soetomo
Level 2
[https://majadigi.jatimprov.go.id/fitur/rsud-dr-soetomo-informasi-ketersediaan-kamar](https://majadigi.jatimprov.go.id/fitur/rsud-dr-soetomo-informasi-ketersediaan-kamar)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
236
SIDITA
Dinas Kebudayaan dan Pariwisata Provinsi Jawa Timur
Level 2
[https://majadigi.jatimprov.go.id/fitur/destinasiwisata](https://majadigi.jatimprov.go.id/fitur/destinasiwisata)
Aktif
237
Produk Hukum Jawa Timur - JDIH
Biro Hukum
Level 2
[https://majadigi.jatimprov.go.id/fitur/informasihukum](https://majadigi.jatimprov.go.id/fitur/informasihukum)
Aktif
238
Data Penerima & Info Program Bansos
Dinas Sosial
Level 2
[https://majadigi.jatimprov.go.id/fitur/infobansos](https://majadigi.jatimprov.go.id/fitur/infobansos)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
239
Login Rumah ASN dengan SIMASTER
Badan Kepegawaian Daerah
Level 1
[https://siasn.bkd.jatimprov.go.id/oidc-master/...](https://siasn.bkd.jatimprov.go.id/oidc-master/...)
Aktif
240
Login Rumah ASN dengan SSO JATIM
Badan Kepegawaian Daerah
Level 3
[https://sso.jatimprov.go.id/realms/JATIM/protocol/openid-connect/auth?client_id=bkd-jatim&scope=openid%20profile%20email&response_type=code&redirect_uri=https%3A%2F%2Fsiasn.bkd.jatimprov.go.id%2Fhelpdesk%2Fapi%2Fauth%2Fcallback%2Fsso-pemprov&prompt=login&state=D-EtpbVyXT1xVFZxakn9nbVKarcZH82rLJheO01muzk&code_challenge=MmL2A_31V7FPvnOXY0cqR2Mo1JpJFP_VPteefIVqM0Q&code_challenge_method=S256](https://sso.jatimprov.go.id/realms/JATIM/protocol/openid-connect/auth?client_id=bkd-jatim&scope=openid%20profile%20email&response_type=code&redirect_uri=https%3A%2F%2Fsiasn.bkd.jatimprov.go.id%2Fhelpdesk%2Fapi%2Fauth%2Fcallback%2Fsso-pemprov&prompt=login&state=D-EtpbVyXT1xVFZxakn9nbVKarcZH82rLJheO01muzk&code_challenge=MmL2A_31V7FPvnOXY0cqR2Mo1JpJFP_VPteefIVqM0Q&code_challenge_method=S256)
Aktif
241
Informasi Prakiraan Cuaca dan Gempa Bumi BMKG
Badan Meteorologi, Klimatologi, dan Geofisika
Level 2
[https://majadigi.jatimprov.go.id/fitur/bmkg-prakiraan-cuaca](https://majadigi.jatimprov.go.id/fitur/bmkg-prakiraan-cuaca)
Aktif
Link layanan dapat diakses dan berfungsi dengan baik
242
Layanan Informasi Antrian Pasien
Rumah Sakit Paru Manguharjo Madiun
Level 2
[https://majadigi.jatimprov.go.id/fitur/rs-paru-manguharjo-antrian-poliklinik](https://majadigi.jatimprov.go.id/fitur/rs-paru-manguharjo-antrian-poliklinik)
Aktif
EOD;

        $lines = explode("\n", str_replace("\r", "", $rawData));
        $records = [];
        $i = 0;
        
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            
            // Look for the starting number
            if (is_numeric($line)) {
                $no = $line;
                $name = trim($lines[$i+1] ?? '');
                $opd = trim($lines[$i+2] ?? '');
                $level = trim($lines[$i+3] ?? '');
                
                $linkLine = trim($lines[$i+4] ?? '');
                // Parse markdown link or just url
                $url = $linkLine;
                if (preg_match('/\[(.*?)\]\((.*?)\)/', $linkLine, $matches)) {
                    $url = $matches[2];
                }

                $statusStr = trim($lines[$i+5] ?? '');
                $isActive = (strtolower($statusStr) === 'aktif');

                // Check if there is a note, or if the next line is the next number
                $notes = '';
                $j = $i + 6;
                $noteLines = [];
                while ($j < count($lines) && !is_numeric(trim($lines[$j]))) {
                    if (trim($lines[$j]) !== '') {
                        $noteLines[] = trim($lines[$j]);
                    }
                    $j++;
                }
                
                $keterangan = implode("\n", $noteLines);
                if ($level) {
                    $notes = "Level: " . $level;
                    if ($keterangan) {
                        $notes .= "\nKeterangan: " . $keterangan;
                    }
                } else {
                    $notes = $keterangan;
                }

                $records[] = [
                    'source_service_id' => Str::uuid()->toString(),
                    'service_name'      => substr($name, 0, 255),
                    'category'          => substr($opd, 0, 100),
                    'level'             => $level ?: null,
                    'icon'              => 'layanan',
                    'service_url'       => $url,
                    'is_active'         => $isActive,
                    'notes'             => $notes,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
                
                $i = $j;
            } else {
                $i++;
            }
        }

        // Kosongkan tabel sebelum seeding ulang agar tidak terduplikasi
        Service::truncate();

        // Insert in chunks to avoid memory/query issues
        $chunks = array_chunk($records, 50);
        foreach ($chunks as $chunk) {
            Service::insert($chunk);
        }
    }
}
