<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
	service: { type: Object, required: true },
	issues: { type: Array, default: () => [] },
	flash: { type: Object, default: () => ({}) },
});

// ─── Riwayat Kendala State ─────────────────────────────────────
const issuesList = ref([...props.issues]);
watch(() => props.issues, (newVal) => {
	issuesList.value = [...(newVal || [])];
}, { deep: true });

// ─── Flash Notification ────────────────────────────────────────
const flashMsg = ref(props.flash?.success || props.flash?.error || '');
const flashType = ref(props.flash?.error ? 'error' : 'success');
let flashTimer = null;
const progressWidth = ref(100);

function clearFlashTimer() {
	if (flashTimer) {
		clearTimeout(flashTimer);
		flashTimer = null;
	}
}

function closeFlash() {
	clearFlashTimer();
	flashMsg.value = '';
}

function showFlash(msg, type = 'success', duration = 4000) {
	clearFlashTimer();
	flashMsg.value = msg;
	flashType.value = type;
	progressWidth.value = 100;

	setTimeout(() => {
		if (flashMsg.value === msg) {
			progressWidth.value = 0;
		}
	}, 50);

	flashTimer = setTimeout(() => {
		flashMsg.value = '';
		flashTimer = null;
	}, duration);
}

watch(() => props.flash, (newFlash) => {
	if (newFlash?.success) {
		showFlash(newFlash.success, 'success', 4000);
	} else if (newFlash?.error) {
		showFlash(newFlash.error, 'error', 4000);
	}
}, { deep: true });

onMounted(() => {
	if (flashMsg.value) {
		progressWidth.value = 100;
		setTimeout(() => { progressWidth.value = 0; }, 50);
		flashTimer = setTimeout(() => {
			flashMsg.value = '';
			flashTimer = null;
		}, 4000);
	}
});

onUnmounted(() => {
	clearFlashTimer();
});

// ─── Toggle Follow-Up Kendala ──────────────────────────────────
const isUpdatingIssue = ref({});

async function toggleIssueFollowUp(issue) {
	if (isUpdatingIssue.value[issue.id]) return;
	isUpdatingIssue.value[issue.id] = true;

	try {
		const response = await axios.post(route('admin.services.issues.toggle_solved', issue.id));
		if (response.data?.success) {
			issue.has_solved = response.data.has_solved;
			issue.has_solved_label = issue.has_solved ? 'sudah tindak lanjuti' : 'belum tindak lanjuti';
			showFlash(response.data.message || 'Status tindak lanjut berhasil diperbarui.', 'success', 3000);
		}
	} catch (error) {
		showFlash('Gagal mengubah status tindak lanjut kendala.', 'error');
	} finally {
		isUpdatingIssue.value[issue.id] = false;
	}
}
</script>

<template>
	<Head :title="`Detail Layanan: ${service.service_name} - MAJADIGI CHECKER`" />

	<AdminLayout
		title="Master Layanan"
		subtitle="Detail informasi data layanan digital"
	>
		<!-- ── Flash Toast Notification ────────────────────────── -->
		<Transition
			enter-active-class="transition duration-300 ease-out"
			enter-from-class="opacity-0 translate-y-2 scale-95"
			enter-to-class="opacity-100 translate-y-0 scale-100"
			leave-active-class="transition duration-200 ease-in"
			leave-from-class="opacity-100 scale-100"
			leave-to-class="opacity-0 scale-95"
		>
			<div
				v-if="flashMsg"
				class="fixed top-6 right-6 z-[200] max-w-sm w-full bg-white rounded-2xl shadow-xl border overflow-hidden"
				:class="flashType === 'success' ? 'border-emerald-200' : 'border-rose-200'"
			>
				<div class="p-4 flex items-start gap-3">
					<div
						class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
						:class="flashType === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
					>
						<svg v-if="flashType === 'success'" class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
						<svg v-else class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
					</div>
					<div class="flex-1 min-w-0 pt-0.5">
						<p class="text-xs font-bold" :class="flashType === 'success' ? 'text-emerald-950' : 'text-rose-950'">
							{{ flashType === 'success' ? 'Berhasil!' : 'Terjadi Kesalahan' }}
						</p>
						<p class="text-xs text-slate-600 mt-0.5 leading-relaxed break-words">{{ flashMsg }}</p>
					</div>
					<button @click="closeFlash" class="text-slate-400 hover:text-slate-600 p-1 shrink-0">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
					</button>
				</div>
				<div class="h-1 bg-slate-100 w-full overflow-hidden">
					<div
						class="h-full transition-all duration-[4000ms] ease-linear"
						:class="flashType === 'success' ? 'bg-emerald-500' : 'bg-rose-500'"
						:style="{ width: progressWidth + '%' }"
					></div>
				</div>
			</div>
		</Transition>

		<div class="space-y-6 max-w-7xl mx-auto pb-10">
			<!-- ── 1. Header Card Banner ──────────────────────────── -->
			<div class="relative rounded-3xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg">
				<!-- Background Effects -->
				<div class="absolute inset-0 opacity-60 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>
				<div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/90 via-[#1e3a8a]/60 to-[#1e3a8a]/30 pointer-events-none"></div>
				<div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.8) 1px, transparent 1px); background-size: 20px 20px;"></div>
				<div class="absolute -top-24 -right-24 w-72 h-72 bg-cyan-400/20 rounded-full blur-3xl pointer-events-none"></div>

				<div class="relative z-10">
					<!-- Tombol Kembali ke Master Layanan -->
					<div class="flex items-center justify-between gap-4 mb-4">
						<Link
							:href="route('admin.services.index')"
							class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white/90 bg-white/10 hover:bg-white/20 border border-white/20 transition-all backdrop-blur-sm shadow-xs group"
						>
							<svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
							</svg>
							<span>Kembali ke Master Layanan</span>
						</Link>

						<!-- Breadcrumb Badge -->
						<div class="hidden sm:flex items-center gap-1.5 text-[11px] font-medium text-white/70">
							<Link :href="route('dashboard')" class="hover:text-white transition-colors">Dashboard</Link>
							<span>/</span>
							<Link :href="route('admin.services.index')" class="hover:text-white transition-colors">Master Layanan</Link>
							<span>/</span>
							<span class="text-white font-bold truncate max-w-[200px]">{{ service.service_name }}</span>
						</div>
					</div>

					<!-- Judul & Aksi Utama -->
					<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pt-1">
						<div>
							<div class="flex items-center gap-3 flex-wrap">
								<h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
									{{ service.service_name }}
								</h1>

								<!-- Status Badge -->
								<span
									class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider backdrop-blur-sm shadow-2xs border"
									:class="{
										'bg-emerald-500/20 text-emerald-200 border-emerald-400/40': service.status === 'aktif',
										'bg-rose-500/20 text-rose-200 border-rose-400/40': service.status === 'tidak_aktif',
										'bg-amber-500/20 text-amber-200 border-amber-400/40': service.status === 'butuh_konfirmasi',
										'bg-white/10 text-white/80 border-white/20': !service.status,
									}"
								>
									<span v-if="service.status === 'aktif'">Aktif</span>
									<span v-else-if="service.status === 'tidak_aktif'">Tidak Aktif</span>
									<span v-else-if="service.status === 'butuh_konfirmasi'">Butuh Konfirmasi</span>
									<span v-else>-</span>
								</span>
							</div>

							<p class="text-xs sm:text-sm text-white/80 mt-2 flex items-center gap-2 flex-wrap font-medium">
								<span>{{ service.category || 'Tanpa Kategori' }}</span>
								<span class="text-white/30">|</span>
								<span class="px-2 py-0.5 rounded-md bg-white/15 text-white text-[11px] font-bold border border-white/20">
									{{ service.level || 'Level Tidak Ditentukan' }}
								</span>
								<template v-if="service.synced_at">
									<span class="text-white/30">|</span>
									<span class="text-white/70 text-xs">Sinkronisasi: {{ service.synced_at }}</span>
								</template>
							</p>
						</div>

						<!-- Tombol Kunjungi Layanan -->
						<div class="flex items-center gap-2.5 shrink-0 pt-2 lg:pt-0">
							<a
								v-if="service.service_url"
								:href="service.service_url"
								target="_blank"
								class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-white text-primary hover:bg-slate-50 transition-all shadow-md active:scale-95 cursor-pointer"
								title="Buka alamat URL layanan di tab baru"
							>
								<svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
								</svg>
								<span>Buka Layanan</span>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- ── 2. Grid Ringkasan Meta Info ────────────────────── -->
			<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
				<!-- Kategori -->
				<div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs space-y-2">
					<div class="flex items-center justify-between text-slate-400">
						<span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kategori</span>
						<div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
						</div>
					</div>
					<p class="text-base font-extrabold text-slate-800 line-clamp-2 leading-snug">
						{{ service.category || '-' }}
					</p>
					<p class="text-[11px] text-slate-400">OPD / Instansi Pengampu</p>
				</div>

				<!-- Level Layanan -->
				<div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs space-y-2">
					<div class="flex items-center justify-between text-slate-400">
						<span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Level Layanan</span>
						<div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
						</div>
					</div>
					<p class="text-xl font-extrabold text-slate-800">
						{{ service.level || '-' }}
					</p>
					<p class="text-[11px] text-slate-400">Tingkat Prioritas Pengecekan</p>
				</div>

				<!-- Riwayat Penugasan -->
				<div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs space-y-2">
					<div class="flex items-center justify-between text-slate-400">
						<span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Penugasan</span>
						<div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
						</div>
					</div>
					<p class="text-2xl font-extrabold text-slate-800">
						{{ service.period_services_count || 0 }} <span class="text-xs font-semibold text-slate-400">Periode</span>
					</p>
					<p class="text-[11px] text-slate-400">Tercatat dalam periode pengujian</p>
				</div>

				<!-- Total Riwayat Kendala -->
				<div class="bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs space-y-2">
					<div class="flex items-center justify-between text-slate-400">
						<span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Riwayat Kendala</span>
						<div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
						</div>
					</div>
					<p class="text-2xl font-extrabold" :class="issuesList.length > 0 ? 'text-rose-600' : 'text-slate-800'">
						{{ issuesList.length }} <span class="text-xs font-semibold text-slate-400">Laporan</span>
					</p>
					<p class="text-[11px] text-slate-400">
						{{ issuesList.filter(i => !i.has_solved).length }} belum selesai ditangani
					</p>
				</div>
			</div>

			<!-- ── 3. Detail Utama & Konten Layanan ───────────────── -->
			<div class="space-y-5">
				<!-- Card URL & Akses Layanan -->
				<div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-3">
					<div class="flex items-center justify-between">
						<h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
							<svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
							<span>Tautan / URL Layanan</span>
						</h3>
					</div>

					<div v-if="service.service_url" class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
						<a
							:href="service.service_url"
							target="_blank"
							class="text-xs sm:text-sm font-bold text-primary hover:underline break-all inline-flex items-center gap-1.5"
						>
							<span>{{ service.service_url }}</span>
							<svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
						</a>

						<a
							:href="service.service_url"
							target="_blank"
							class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-white text-slate-700 hover:text-primary border border-slate-200 hover:border-primary/40 transition-colors shadow-2xs"
						>
							Kunjungi URL
						</a>
					</div>
					<div v-else class="p-3.5 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-400 italic">
						Belum ada tautan URL yang didaftarkan untuk layanan ini.
					</div>
				</div>

				<!-- Card Deskripsi Layanan -->
				<div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-3">
					<h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
						<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
						<span>Deskripsi Layanan</span>
					</h3>
					<div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
						<p v-if="service.deskripsi_layanan" class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed font-normal">
							{{ service.deskripsi_layanan }}
						</p>
						<p v-else class="text-sm text-slate-400 italic">
							Belum ada deskripsi layanan yang ditambahkan.
						</p>
					</div>
				</div>

				<!-- Card Instruksi Khusus Tester (Aksen Merah) -->
				<div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-3">
					<h3 class="text-xs font-bold text-rose-600 uppercase tracking-wider flex items-center gap-2">
						<svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
						<span>Instruksi Khusus Tester</span>
					</h3>
					<div class="p-4 bg-rose-50/70 rounded-2xl border border-rose-200/80">
						<p v-if="service.tester_instruction" class="text-sm text-rose-900 whitespace-pre-wrap leading-relaxed font-medium">
							{{ service.tester_instruction }}
						</p>
						<p v-else class="text-sm text-rose-400/80 italic">
							Belum ada instruksi khusus untuk tester pada layanan ini.
						</p>
					</div>
				</div>

				<!-- Card Catatan / Notes Tambahan (Aksen Amber) -->
				<div v-if="service.notes" class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-3">
					<h3 class="text-xs font-bold text-amber-700 uppercase tracking-wider flex items-center gap-2">
						<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
						<span>Keterangan / Catatan Tambahan</span>
					</h3>
					<div class="p-4 bg-amber-50/60 rounded-2xl border border-amber-200/70">
						<p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed font-medium">
							{{ service.notes }}
						</p>
					</div>
				</div>
			</div>

			<!-- ── 4. Section: Riwayat Kendala Layanan ──────────────── -->
			<div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-7 shadow-xs space-y-4">
				<div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
					<div>
						<h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
							<svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
							<span>Riwayat Kendala Layanan</span>
							<span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-slate-100 text-slate-600 border border-slate-200">
								{{ issuesList.length }} Catatan
							</span>
						</h3>
						<p class="text-xs text-slate-400 mt-0.5">Daftar laporan kendala dari tester pada setiap periode pengujian</p>
					</div>
				</div>

				<!-- Empty State -->
				<div v-if="issuesList.length === 0" class="py-10 px-4 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center">
					<div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2.5">
						<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
					</div>
					<h4 class="text-sm font-bold text-slate-800">Tidak Ada Riwayat Kendala</h4>
					<p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
						Layanan ini belum pernah dilaporkan mengalami kendala saat periode pengecekan berlangsung.
					</p>
				</div>

				<!-- List History Kendala -->
				<div v-else class="space-y-3">
					<div
						v-for="(issue, idx) in issuesList"
						:key="issue.id"
						class="p-4 sm:p-5 rounded-2xl border transition-all text-xs"
						:class="issue.has_solved ? 'bg-slate-50/70 border-slate-200 text-slate-700' : 'bg-rose-50/40 border-rose-200/90 text-slate-800 shadow-2xs'"
					>
						<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
							<div class="flex items-center gap-2 flex-wrap font-medium">
								<span class="font-bold text-slate-900 w-5">{{ idx + 1 }}.</span>

								<!-- Periode Check -->
								<span class="text-slate-700">
									Periode: <strong class="text-slate-900">tgl {{ issue.period_date }}</strong>
								</span>

								<span class="text-slate-300 hidden sm:inline">|</span>

								<!-- Status -->
								<span class="text-slate-700">
									Status:
									<span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] bg-rose-100 text-rose-700 border border-rose-200">
										{{ issue.status || 'Terkendala' }}
									</span>
								</span>

								<span class="text-slate-300 hidden sm:inline">|</span>

								<!-- Tester -->
								<span class="text-slate-500">
									Tester: <strong class="text-slate-700">{{ issue.tester_name || 'Tester' }}</strong>
								</span>

								<span class="text-slate-300 hidden sm:inline">|</span>

								<!-- Status Tindak Lanjut Badge -->
								<span
									class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase inline-flex items-center shadow-2xs"
									:class="{
										'bg-amber-100 text-amber-800 border border-amber-200': issue.handling_status === 'perlu_pengecekan_ulang',
										'bg-emerald-100 text-emerald-800 border border-emerald-200': issue.has_solved || issue.handling_status === 'selesai',
										'bg-rose-100 text-rose-800 border border-rose-200': !issue.has_solved && issue.handling_status !== 'perlu_pengecekan_ulang',
									}"
								>
									{{ issue.has_solved_label }}
								</span>
							</div>

							<!-- Tombol Tindak Lanjuti -->
							<button
								type="button"
								@click="toggleIssueFollowUp(issue)"
								:disabled="isUpdatingIssue[issue.id]"
								class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-bold transition-all border cursor-pointer active:scale-95 disabled:opacity-50"
								:class="issue.has_solved
									? 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200 shadow-2xs'
									: 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600 shadow-sm'"
								:title="issue.has_solved ? 'Klik untuk ubah status kembali' : 'Tandai kendala ini sudah selesai ditindaklanjuti'"
							>
								{{ issue.has_solved ? 'Ubah Status' : 'Tandai Selesai' }}
							</button>
						</div>

						<!-- Catatan Kendala -->
						<div class="mt-3 pt-3 border-t border-slate-200/70 space-y-1.5">
							<p class="text-xs text-slate-700 leading-relaxed font-normal">
								<span class="font-bold text-slate-800">Catatan Kendala:</span>
								{{ issue.catatan || '(Tanpa catatan deskripsi)' }}
							</p>

							<!-- Bukti Lampiran -->
							<div v-if="issue.attachment" class="pt-1">
								<a
									:href="issue.attachment"
									target="_blank"
									class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors"
								>
									<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
									<span>Buka Bukti Lampiran Kendala</span>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</AdminLayout>
</template>
