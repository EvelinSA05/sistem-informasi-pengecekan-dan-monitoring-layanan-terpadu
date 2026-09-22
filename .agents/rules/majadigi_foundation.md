# MajaDigi Service Checking - Konsep Dasar & Aturan

Saat memodifikasi atau menganalisis proyek MajaDigi Service Checking, Anda WAJIB mematuhi seluruh batasan dasar berikut:

## 1. Gambaran Umum & Arsitektur Proyek
- **Tujuan**: Aplikasi web untuk melakukan *checking* dan pemantauan (monitoring) seluruh layanan aplikasi MajaDigi.
- **Teknologi (Stack)**: Laravel, Inertia, Vue, PostgreSQL, Tailwind CSS. (Dukungan untuk Docker dan docker-compose juga disiapkan).
- **Sumber Data Layanan**: Data master layanan diambil/bersumber dari database utama MajaDigi. Desain database aplikasi ini harus fleksibel untuk mengakomodasi perubahan data dari hulu tersebut.

## 2. Peran Pengguna (User Roles) & Manajemen
- **Role**: Sistem berjalan dengan dua peran utama: `Admin` dan `Tester`.
- **Manajemen User**: Aplikasi harus memiliki menu Manajemen User di mana Admin dapat melakukan operasi CRUD (Create, Read, Update, Delete) secara penuh terhadap akun Tester, termasuk mengatur *username* dan *password*.

## 3. Periode Pengecekan
- **Alur Pembuatan**: Admin adalah pihak yang membuat periode pengecekan. Dalam satu form yang sama, Admin memilih bulan/tanggal periode tersebut sekaligus menugaskan/menambahkan siapa saja Tester yang akan dilibatkan.
- **Distribusi Otomatis**: Seluruh layanan dari daftar master yang berstatus aktif akan secara otomatis dibagikan dan didistribusikan secara merata kepada Tester yang dipilih. (Contoh: Jika ada 200 layanan dan 4 Tester, setiap Tester akan secara otomatis menerima tepat 50 layanan untuk dicek).
- **Status Periode**: Setiap periode memiliki status berjalannya, utamanya `proses` (In Progress) dan `done` (Selesai).
- **Aksi Periode**: Periode yang telah dibuat harus bisa dilihat detailnya, diedit, atau dihapus dari daftar.

## 4. Monitoring & Halaman Detail
- **Metrik Halaman Detail**: Pada halaman detail periode, harus terdapat rekap data meliputi: total layanan berjalan (normal), total layanan terkendala, dan total layanan yang membutuhkan konfirmasi.
- **Fitur Daftar Layanan**: Di dalam detail periode, terdapat daftar layanan yang ditugaskan. Daftar ini wajib memiliki fitur pencarian (search), filter berdasarkan nama Tester, dan filter berdasarkan status layanan (normal, terkendala, butuh konfirmasi, dalam proses).
- **Hak Akses Admin**: Melalui daftar layanan ini, Admin memiliki kemampuan untuk mengganti status layanan secara manual serta melihat kolom keterangan (terutama jika tester meminta/membutuhkan konfirmasi).
- **Export Laporan**: Halaman detail wajib mendukung export laporan ke dalam format PDF. Laporan tersebut memuat: informasi periode, daftar user/tester yang bertugas, total layanan normal, total layanan terkendala, beserta perincian daftar layanan normal dan terkendala.

## 5. Alur Kerja Tester (Tester Workflow)
- Ketika login, Tester akan diarahkan ke halaman *dashboard* khusus yang **hanya** menampilkan layanan-layanan spesifik yang dibebankan/ditugaskan kepadanya pada periode aktif tersebut.
