<script setup>
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
	report: { type: Object, required: true },
	testers_workload: { type: Array, default: () => [] },
	services: { type: Array, default: () => [] },
	running_services: { type: Array, default: () => [] },
	issue_services: { type: Array, default: () => [] },
});

const runningServicesList = computed(() => {
	if (props.running_services && props.running_services.length > 0) {
		return props.running_services;
	}
	return props.services
		.filter((s) => s.status === 'normal')
		.map((s, idx) => ({ ...s, no: idx + 1 }));
});

const issueServicesList = computed(() => {
	if (props.issue_services && props.issue_services.length > 0) {
		return props.issue_services;
	}
	return props.services
		.filter((s) => s.status !== 'normal')
		.map((s, idx) => ({ ...s, no: idx + 1 }));
});

// Nama file PDF yang diharapkan: Laporan_Pengecekan_[Tanggal]_[Bulan]_[Tahun]
const targetFileName = computed(() => {
	if (props.report?.file_name) {
		return props.report.file_name;
	}
	return 'Laporan_Pengecekan';
});

// Judul halaman standar saat dokumen dibuka di tab browser
const defaultPageTitle = computed(() => {
	return `Laporan Pengecekan ${props.report?.title || ''} - MAJADIGI CHECKER`;
});

let isPrinting = false;

function printDocument() {
	if (isPrinting) return;
	isPrinting = true;

	const originalTitle = defaultPageTitle.value;

	// Atur document.title menjadi nama file yang diharapkan agar dialog print menyarankan nama ini
	document.title = targetFileName.value;

	const handleAfterPrint = () => {
		document.title = originalTitle;
		isPrinting = false;
		window.removeEventListener('afterprint', handleAfterPrint);
	};

	window.addEventListener('afterprint', handleAfterPrint);

	window.print();

	// Fallback pemulihan judul jika event afterprint tidak tertrigger di browser tertentu
	setTimeout(() => {
		if (isPrinting) {
			handleAfterPrint();
		}
	}, 1500);
}

onMounted(() => {
	document.title = targetFileName.value;

	// Listener jika user melakukan print melalui pintasan keyboard browser (Ctrl+P / Cmd+P)
	window.addEventListener('beforeprint', () => {
		document.title = targetFileName.value;
	});
	window.addEventListener('afterprint', () => {
		document.title = defaultPageTitle.value;
		isPrinting = false;
	});

	// Memberikan jeda singkat agar font dan SVG selesai dirender sebelum dialog cetak terbuka otomatis
	setTimeout(() => {
		printDocument();
	}, 600);
});
</script>

<template>
	<Head :title="defaultPageTitle" />

	<div class="min-h-screen bg-slate-100 py-8 px-4 font-sans text-slate-800 antialiased print:p-0 print:bg-white">

		<!-- ── Action Bar (Hidden when printing) ──────────────── -->
		<div class="max-w-4xl mx-auto mb-6 flex items-center justify-end print:hidden">
			<div class="flex items-center gap-3">
				<span class="text-xs text-slate-500 font-medium">
					Gunakan opsi <strong>"Save as PDF"</strong> pada dialog print.
				</span>
				<button
					@click="printDocument"
					class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-md hover:shadow-lg transition-all"
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
						<img 
							src="/images/logo_majadigi.png" 
							alt="Logo Majadigi" 
							class="h-12 sm:h-14 w-auto object-contain shrink-0"
						/>
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
						<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-blue-100 text-blue-800 border border-blue-300">
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
					<p class="text-[10px] text-blue-600 mt-0.5">
						{{ report.checked_services }}/{{ report.total_services }} terverifikasi
					</p>
				</div>
			</div>

			<!-- ── Tabel A: Layanan Berjalan ──────────────────── -->
			<div class="mb-8">
				<div class="flex items-center justify-between mb-3">
					<h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
						<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
						A. Layanan Berjalan
						<span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full normal-case">
							{{ runningServicesList.length }} Layanan
						</span>
					</h3>
				</div>
				<div class="border border-slate-200 rounded-2xl overflow-hidden print:rounded-none">
					<table class="w-full text-left text-xs border-collapse">
						<thead>
							<tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-bold text-slate-600 uppercase tracking-wider">
								<th class="py-2.5 px-3 w-10 text-center">No.</th>
								<th class="py-2.5 px-3 w-[26%]">Nama Layanan</th>
								<th class="py-2.5 px-3 w-[34%]">Link Layanan</th>
								<th class="py-2.5 px-3 w-[22%]">OPD/Kab/Kota</th>
								<th class="py-2.5 px-3 w-[18%]">Level Layanan</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-200 text-[11px]">
							<tr v-if="runningServicesList.length === 0">
								<td colspan="5" class="py-4 px-3 text-center text-slate-400 italic">
									Tidak ada data layanan berjalan pada periode ini.
								</td>
							</tr>
							<tr
								v-for="srv in runningServicesList"
								:key="'run-' + (srv.id || srv.no)"
								class="hover:bg-slate-50"
							>
								<td class="py-2.5 px-3 text-center text-slate-400 font-semibold align-top">{{ srv.no }}</td>
								<td class="py-2.5 px-3 font-bold text-slate-800 align-top">{{ srv.service_name }}</td>
								<td class="py-2.5 px-3 align-top break-all">
									<a
										v-if="srv.service_url"
										:href="srv.service_url"
										target="_blank"
										class="text-blue-600 hover:underline"
									>
										{{ srv.service_url }}
									</a>
									<span v-else class="text-slate-400">-</span>
								</td>
								<td class="py-2.5 px-3 text-slate-600 align-top">{{ srv.category || srv.opd || '-' }}</td>
								<td class="py-2.5 px-3 text-slate-600 align-top whitespace-nowrap">{{ srv.level || '-' }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- ── Tabel B: Layanan Terkendala ────────────────── -->
			<div class="mb-4">
				<div class="flex items-center justify-between mb-3">
					<h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
						<span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
						B. Layanan Terkendala
						<span class="text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full normal-case">
							{{ issueServicesList.length }} Layanan
						</span>
					</h3>
				</div>
				<div class="border border-slate-200 rounded-2xl overflow-hidden print:rounded-none">
					<table class="w-full text-left text-xs border-collapse">
						<thead>
							<tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-bold text-slate-600 uppercase tracking-wider">
								<th class="py-2.5 px-3 w-10 text-center">No.</th>
								<th class="py-2.5 px-3 w-[26%]">Nama Layanan</th>
								<th class="py-2.5 px-3 w-[34%]">Link Layanan</th>
								<th class="py-2.5 px-3 w-[22%]">OPD/Kab/Kota</th>
								<th class="py-2.5 px-3 w-[18%]">Level Layanan</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-200 text-[11px]">
							<tr v-if="issueServicesList.length === 0">
								<td colspan="5" class="py-4 px-3 text-center text-slate-400 italic">
									Tidak ada data layanan terkendala pada periode ini.
								</td>
							</tr>
							<tr
								v-for="srv in issueServicesList"
								:key="'iss-' + (srv.id || srv.no)"
								class="hover:bg-slate-50"
							>
								<td class="py-2.5 px-3 text-center text-slate-400 font-semibold align-top">{{ srv.no }}</td>
								<td class="py-2.5 px-3 font-bold text-slate-800 align-top">{{ srv.service_name }}</td>
								<td class="py-2.5 px-3 align-top break-all">
									<a
										v-if="srv.service_url"
										:href="srv.service_url"
										target="_blank"
										class="text-blue-600 hover:underline"
									>
										{{ srv.service_url }}
									</a>
									<span v-else class="text-slate-400">-</span>
								</td>
								<td class="py-2.5 px-3 text-slate-600 align-top">{{ srv.category || srv.opd || '-' }}</td>
								<td class="py-2.5 px-3 text-slate-600 align-top whitespace-nowrap">{{ srv.level || '-' }}</td>
							</tr>
						</tbody>
					</table>
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
	table {
		page-break-inside: auto;
	}
	tr {
		page-break-inside: avoid;
		page-break-after: auto;
	}
	thead {
		display: table-header-group;
	}
	tfoot {
		display: table-footer-group;
	}
}
</style>
