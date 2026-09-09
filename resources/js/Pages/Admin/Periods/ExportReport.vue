<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    report:          { type: Object, required: true },
    testers_workload:{ type: Array,  default: () => [] },
    services:        { type: Array,  default: () => [] },
});

function printDocument() {
    window.print();
}

onMounted(() => {
    // Memberikan jeda singkat agar font dan SVG selesai dirender sebelum dialog cetak terbuka
    setTimeout(() => {
        window.print();
    }, 600);
});
</script>

<template>
    <Head :title="`Laporan Pengecekan ${report.title} - MAJADIGI CHECKER`" />

    <div class="min-h-screen bg-slate-100 py-8 px-4 font-sans text-slate-800 antialiased print:p-0 print:bg-white">

        <!-- ── Action Bar (Hidden when printing) ──────────────── -->
        <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between print:hidden">
            <button
                @click="$window.history ? window.history.back() : null"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs"
            >
                ← Kembali
            </button>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">
                    Gunakan opsi <strong>"Save as PDF"</strong> pada dialog print.
                </span>
                <button
                    @click="printDocument"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold shadow-md hover:shadow-lg transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Cetak / Simpan PDF</span>
                </button>
            </div>
        </div>

        <!-- ── Printable Sheet (A4 format) ───────────────────── -->
        <div class="max-w-4xl mx-auto bg-white rounded-3xl p-10 shadow-xl border border-slate-200/80 print:shadow-none print:border-none print:p-6 print:rounded-none print:max-w-none">

            <!-- ── Document Header / KOP Laporan ─────────────── -->
            <div class="border-b-2 border-slate-900 pb-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-blue-800 text-white flex items-center justify-center font-bold text-xl shadow-md">
                            M
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-slate-900 tracking-tight uppercase leading-none">
                                MAJADIGI CHECKER SYSTEM
                            </h1>
                            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider mt-1">
                                DINAS KOMUNIKASI DAN INFORMATIKA PROVINSI JAWA TIMUR
                            </p>
                            <p class="text-[11px] text-slate-400">
                                Sistem Monitoring & Evaluasi Kesiapan Layanan Digital Pemerintah
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-blue-50 text-blue-800 border border-blue-200">
                            {{ report.status }}
                        </span>
                        <p class="text-xs font-semibold text-slate-500 mt-2">
                            Periode: <strong class="text-slate-900">{{ report.title }}</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Title & Metadata -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-900 leading-tight">
                    LAPORAN EKSEKUTIF PEMERIKSAAN LAYANAN DIGITAL
                </h2>
                <div class="flex items-center gap-6 mt-2 text-xs text-slate-500">
                    <p>Pembuat: <strong class="text-slate-800">{{ report.creator_name }}</strong></p>
                    <p>Tanggal Pembuatan: <strong class="text-slate-800">{{ report.created_at_label }}</strong></p>
                    <p>Waktu Cetak: <strong class="text-slate-800">{{ report.generated_at }}</strong></p>
                </div>
            </div>

            <!-- ── Infographic KPI Cards ──────────────────────── -->
            <div class="grid grid-cols-4 gap-4 mb-8">
                <!-- Total -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Layanan</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ report.total_services }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">100% dari target</p>
                </div>

                <!-- Normal -->
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                    <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Layanan Normal</p>
                    <p class="text-2xl font-black text-emerald-800 mt-1">{{ report.normal_services }}</p>
                    <p class="text-[10px] text-emerald-700 mt-0.5">
                        {{ report.total_services > 0 ? Math.round((report.normal_services / report.total_services) * 100) : 0 }}% sehat
                    </p>
                </div>

                <!-- Kendala -->
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900">
                    <p class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">Terkendala</p>
                    <p class="text-2xl font-black text-rose-800 mt-1">{{ report.issues_services }}</p>
                    <p class="text-[10px] text-rose-700 mt-0.5">Perlu tindak lanjut</p>
                </div>

                <!-- Progress -->
                <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900">
                    <p class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Progress Selesai</p>
                    <p class="text-2xl font-black text-blue-800 mt-1">{{ report.progress_percent }}%</p>
                    <p class="text-[10px] text-blue-700 mt-0.5">
                        {{ report.checked_services }}/{{ report.total_services }} terverifikasi
                    </p>
                </div>
            </div>

            <!-- ── Tester Workload Section ───────────────────── -->
            <div class="mb-8">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                    DISTRIBUSI TESTER & HASIL EVALUASI
                </h3>
                <div class="grid grid-cols-3 gap-3">
                    <div
                        v-for="tw in testers_workload"
                        :key="tw.name"
                        class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 flex items-center justify-between"
                    >
                        <div>
                            <p class="text-xs font-bold text-slate-800 leading-tight">{{ tw.name }}</p>
                            <p class="text-[10.5px] text-slate-400 mt-0.5 font-medium">{{ tw.bidang }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-[10.5px] font-extrabold bg-white border border-slate-200 text-slate-700">
                                {{ tw.checked }}/{{ tw.total }} ({{ tw.percent }}%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Detailed Checklist Table ───────────────────── -->
            <div class="mb-10">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                    RINCIAN STATUS PENGECEKAN SELURUH LAYANAN
                </h3>
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-bold text-slate-600 uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3">Nama Layanan</th>
                                <th class="py-2.5 px-3">Kategori</th>
                                <th class="py-2.5 px-3">Tester</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3">Waktu Cek</th>
                                <th class="py-2.5 px-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-[11px]">
                            <tr v-for="srv in services" :key="srv.no" class="hover:bg-slate-50">
                                <td class="py-2 px-3 text-center text-slate-400 font-semibold">{{ srv.no }}</td>
                                <td class="py-2 px-3 font-bold text-slate-800">{{ srv.service_name }}</td>
                                <td class="py-2 px-3 text-slate-500">{{ srv.category }}</td>
                                <td class="py-2 px-3 font-semibold text-slate-700">{{ srv.tester_name }}</td>
                                <td class="py-2 px-3">
                                    <span
                                        v-if="srv.status === 'normal'"
                                        class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800"
                                    >
                                        NORMAL
                                    </span>
                                    <span
                                        v-else-if="srv.status === 'gangguan'"
                                        class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800"
                                    >
                                        KENDALA
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600"
                                    >
                                        BELUM DICEK
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-slate-500 font-mono text-[10px]">{{ srv.checked_at }}</td>
                                <td class="py-2 px-3 text-slate-600 max-w-xs truncate">{{ srv.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Signature Block / Lembar Pengesahan ────────── -->
            <div class="grid grid-cols-2 gap-12 pt-6 border-t border-slate-200 text-xs">
                <div class="text-center">
                    <p class="text-slate-500">Mengetahui,</p>
                    <p class="font-bold text-slate-800 mt-1">Koordinator Pengujian Layanan</p>
                    <div class="h-20 flex items-center justify-center">
                        <span class="text-slate-300 italic text-[10px]">[Tanda Tangan Digital]</span>
                    </div>
                    <p class="font-bold text-slate-800 underline">Sari Dewi, S.Kom.</p>
                    <p class="text-[11px] text-slate-400 font-mono">NIP. 199004122015022001</p>
                </div>

                <div class="text-center">
                    <p class="text-slate-500">Surabaya, {{ report.generated_at }}</p>
                    <p class="font-bold text-slate-800 mt-1">Administrator Sistem Majadigi</p>
                    <div class="h-20 flex items-center justify-center">
                        <span class="text-slate-300 italic text-[10px]">[Tanda Tangan Digital]</span>
                    </div>
                    <p class="font-bold text-slate-800 underline">{{ report.creator_name }}</p>
                    <p class="text-[11px] text-slate-400 font-mono">NIP. 198503152010011002</p>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
@media print {
    body {
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    @page {
        size: A4;
        margin: 1.2cm;
    }
}
</style>
