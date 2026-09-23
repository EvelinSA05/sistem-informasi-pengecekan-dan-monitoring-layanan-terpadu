const fs = require('fs');
const path = require('path');

const rootDir = 'c:/xampp/htdocs/majadigi-checking-layanan';

function updateFile(relativePath, search, replace) {
    const filePath = path.join(rootDir, relativePath);
    if (!fs.existsSync(filePath)) {
        console.log('File not found:', filePath);
        return;
    }
    let content = fs.readFileSync(filePath, 'utf8');
    if (content.includes(search)) {
        content = content.replace(search, replace);
        fs.writeFileSync(filePath, content, 'utf8');
        console.log('Updated banner for:', relativePath);
    } else {
        console.log('Search pattern not found in:', relativePath);
    }
}

// 1. Tester/Periods/Index.vue
updateFile(
    'resources/js/Pages/Tester/Periods/Index.vue',
    `<!-- ── Main Card Container ──────────────────────────────── -->`,
    `<!-- ── Hero Banner ────────────────────────────────────── -->
    <div class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-6">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
          <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
          Penugasan Pengecekan
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Daftar Periode Pengecekan</h2>
        <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-xl">Pilih periode aktif di bawah ini untuk memulai atau melanjutkan proses pengujian layanan digital.</p>
      </div>
    </div>

    <!-- ── Main Card Container ──────────────────────────────── -->`
);

// 2. Admin/Periods/Index.vue
updateFile(
    'resources/js/Pages/Admin/Periods/Index.vue',
    `<!-- ── Main Card Container ──────────────────────────────── -->`,
    `<!-- ── Hero Banner ────────────────────────────────────── -->
    <div class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-6">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_1.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
          <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
          Manajemen Periode
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Daftar Periode & Distribusi</h2>
        <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-xl">Kelola periode pengujian bulanan dan alokasi tugas pengujian layanan ke para tester.</p>
      </div>
    </div>

    <!-- ── Main Card Container ──────────────────────────────── -->`
);

// 3. Admin/MasterLayanan.vue
updateFile(
    'resources/js/Pages/Admin/MasterLayanan.vue',
    `<!-- ── Stats Cards ───────────────────────────────────── -->`,
    `<!-- ── Hero Banner ────────────────────────────────────── -->
    <div class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-6">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_2.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
          <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
          Master Data
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Kelola Master Layanan Digital</h2>
        <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-xl">Tambah, perbarui, dan atur status ketersediaan seluruh aplikasi & layanan digital Majalengka.</p>
      </div>
    </div>

    <!-- ── Stats Cards ───────────────────────────────────── -->`
);

// 4. Admin/ManajemenUser.vue
updateFile(
    'resources/js/Pages/Admin/ManajemenUser.vue',
    `<!-- ── Stats Cards ───────────────────────────────────── -->`,
    `<!-- ── Hero Banner ────────────────────────────────────── -->
    <div class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-6">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_3.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
          <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
          Pengguna Sistem
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Manajemen User & Tester</h2>
        <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-xl">Kelola data pengguna, hak akses tester, NIP, serta bidang dinas dalam sistem MAJADIGI.</p>
      </div>
    </div>

    <!-- ── Stats Cards ───────────────────────────────────── -->`
);

// 5. Tester/Services/Index.vue
updateFile(
    'resources/js/Pages/Tester/Services/Index.vue',
    `<!-- ── Deadline Banner ─────────────────────────────────── -->`,
    `<!-- ── Header Banner ────────────────────────────────────── -->
    <div v-if="periodInfo" class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-4">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_3.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
            Pengujian Layanan
          </div>
          <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Pengecekan: {{ periodInfo.month_name || 'Periode Pengujian' }}</h2>
          <p class="text-white/80 text-xs sm:text-sm mt-1">Lakukan evaluasi dan catat status pengujian layanan digital yang ditugaskan kepada Anda.</p>
        </div>
      </div>
    </div>

    <!-- ── Deadline Banner ─────────────────────────────────── -->`
);

console.log('Finished adding page banners');
