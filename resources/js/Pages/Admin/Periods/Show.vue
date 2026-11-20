<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { Head, Link, router, usePage, useForm, usePoll } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ClientPagination from '@/Components/ClientPagination.vue';
import AdminIssueDetailModal from '@/Components/AdminIssueDetailModal.vue';
import AdminSolveIssueModal from '@/Components/AdminSolveIssueModal.vue';

const props = defineProps({
 period: { type: Object, required: true },
 distribution: { type: Array, default: () => [] },
 troubled_services: { type: Array, default: () => [] },
});

// Polling real-time agar Layanan Terkendala, Statistik, & Distribusi Tester otomatis terupdate tanpa refresh manual
usePoll(10000, {
 only: ['period', 'distribution', 'troubled_services'],
});

// Layanan Terkendala state & actions
const troubledList = ref([...props.troubled_services]);
const troubledCurrentPage = ref(1);
const troubledPerPage = ref(3);

const paginatedTroubledList = computed(() => {
 const start = (troubledCurrentPage.value - 1) * troubledPerPage.value;
 return troubledList.value.slice(start, start + troubledPerPage.value);
});

const totalTroubledPages = computed(() => {
 return Math.ceil(troubledList.value.length / troubledPerPage.value) || 1;
});

function goToTroubledPage(page) {
 if (page >= 1 && page <= totalTroubledPages.value) {
  troubledCurrentPage.value = page;
 }
}

watch(() => props.troubled_services, (val) => {
 troubledList.value = [...(val || [])];
 if (troubledCurrentPage.value > totalTroubledPages.value) {
  troubledCurrentPage.value = 1;
 }
}, { deep: true });

// Detail Kendala Modal state
const showIssueDetailModal = ref(false);
const selectedIssue = ref(null);

function openIssueDetail(item) {
 selectedIssue.value = item;
 showIssueDetailModal.value = true;
}

function closeIssueDetail() {
 showIssueDetailModal.value = false;
 selectedIssue.value = null;
}

// Modal Tandai Selesai Kendala
const showSolveModal = ref(false);
const solveTargetItem = ref(null);
const solveForm = ref({
 description: '',
 errors: '',
 processing: false,
});

function openSolveModal(item) {
 solveTargetItem.value = item;
 solveForm.value = {
  description: item.admin_note || item.description || '',
  errors: '',
  processing: false,
 };
 showSolveModal.value = true;
}

function closeSolveModal() {
 showSolveModal.value = false;
 solveTargetItem.value = null;
 solveForm.value = {
  description: '',
  errors: '',
  processing: false,
 };
}

async function submitSolveKendala() {
 if (!solveTargetItem.value) return;

 const trimmed = (solveForm.value.description || '').trim();
 if (!trimmed) {
  solveForm.value.errors = 'Catatan keterangan kendala wajib diisi.';
  return;
 }

 const targetId = solveTargetItem.value.issue_id || solveTargetItem.value.id;
 if (!targetId) return;

 solveForm.value.processing = true;
 solveForm.value.errors = '';

 try {
  const response = await axios.post(route('admin.services.issues.toggle_solved', targetId), {
   description: trimmed,
   has_solved: true,
  });

  if (response.data?.success) {
   solveTargetItem.value.has_solved = true;
   solveTargetItem.value.handling_status = 'perlu_pengecekan_ulang';
   solveTargetItem.value.has_solved_label = 'perlu pengecekan ulang';
   solveTargetItem.value.admin_note = response.data.admin_note || trimmed;

   // Update juga di allServices jika ada
   const foundInAll = allServices.value.find(s => s.id === solveTargetItem.value.id);
   if (foundInAll) {
    foundInAll.admin_note = solveTargetItem.value.admin_note;
    foundInAll.handling_status = 'perlu_pengecekan_ulang';
   }

   showToast('Kendala telah ditandai selesai. Status: Perlu Pengecekan Ulang.', 'success');
   closeSolveModal();
  } else {
   solveForm.value.errors = response.data?.message || 'Gagal menyimpan perubahan.';
  }
 } catch (err) {
  const errMsg = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan catatan kendala.';
  solveForm.value.errors = errMsg;
  showToast(errMsg, 'error');
 } finally {
  solveForm.value.processing = false;
 }
}

const isUpdatingSolved = ref({});
async function toggleTroubledSolved(item) {
 const targetId = item.issue_id || item.id;
 if (!targetId) return;
 isUpdatingSolved.value[item.id] = true;
 try {
  const response = await axios.post(route('admin.services.issues.toggle_solved', targetId));
  if (response.data?.success) {
   item.has_solved = response.data.has_solved;
   item.handling_status = response.data.handling_status || (item.has_solved ? 'perlu_pengecekan_ulang' : 'belum_ditindaklanjuti');
   item.has_solved_label = item.handling_status === 'perlu_pengecekan_ulang' ? 'perlu pengecekan ulang' : (item.has_solved ? 'sudah tindak lanjuti' : 'belum tindak lanjuti');
   if (response.data.admin_note !== undefined) {
    item.admin_note = response.data.admin_note;
   }
   showToast(response.data.message, 'success');
  }
 } catch (e) {
  showToast('Gagal mengubah status tindak lanjut kendala.', 'error');
 } finally {
  isUpdatingSolved.value[item.id] = false;
 }
}

// ─── Bulk Tindak Lanjut Kendala (Aksi Massal) ─────────────────────────────
const selectedTroubledIds = ref([]);
const showBulkSolveModal = ref(false);
const bulkSolveForm = ref({
 description: '',
 errors: '',
 processing: false,
});

const selectedTroubledItems = computed(() => {
 const idSet = new Set(selectedTroubledIds.value);
 return troubledList.value.filter(item => idSet.has(item.id));
});

const unhandledTroubledCount = computed(() => {
 return troubledList.value.filter(item => !item.has_solved && item.handling_status !== 'perlu_pengecekan_ulang').length;
});

const isAllTroubledSelected = computed(() => {
 return troubledList.value.length > 0 && selectedTroubledIds.value.length === troubledList.value.length;
});

function toggleSelectAllTroubled() {
 if (isAllTroubledSelected.value) {
  selectedTroubledIds.value = [];
 } else {
  selectedTroubledIds.value = troubledList.value.map(item => item.id);
 }
}

function selectUnhandledTroubled() {
 selectedTroubledIds.value = troubledList.value
  .filter(item => !item.has_solved && item.handling_status !== 'perlu_pengecekan_ulang')
  .map(item => item.id);
}

function openBulkSolveModal() {
 if (selectedTroubledIds.value.length === 0) {
  showToast('Pilih minimal 1 layanan terkendala terlebih dahulu.', 'error');
  return;
 }
 bulkSolveForm.value = {
  description: '',
  errors: '',
  processing: false,
 };
 showBulkSolveModal.value = true;
}

function closeBulkSolveModal() {
 showBulkSolveModal.value = false;
 bulkSolveForm.value = {
  description: '',
  errors: '',
  processing: false,
 };
}

async function submitBulkSolveKendala() {
 if (selectedTroubledIds.value.length === 0) return;

 const trimmed = (bulkSolveForm.value.description || '').trim();
 if (!trimmed) {
  bulkSolveForm.value.errors = 'Catatan tindak lanjut admin wajib diisi.';
  return;
 }

 bulkSolveForm.value.processing = true;
 bulkSolveForm.value.errors = '';

 try {
  const response = await axios.post(route('admin.services.issues.bulk_toggle_solved'), {
   period_service_ids: selectedTroubledIds.value,
   description: trimmed,
  });

  if (response.data?.success) {
   const updatedSet = new Set(response.data.updated_ids || selectedTroubledIds.value);
   const note = response.data.admin_note || trimmed;
   const handledAt = response.data.admin_handled_at || null;

   troubledList.value.forEach(item => {
    if (updatedSet.has(item.id)) {
     item.has_solved = true;
     item.handling_status = 'perlu_pengecekan_ulang';
     item.has_solved_label = 'perlu pengecekan ulang';
     item.admin_note = note;
     if (handledAt) item.admin_handled_at = handledAt;
    }
   });

   allServices.value.forEach(s => {
    if (updatedSet.has(s.id)) {
     s.admin_note = note;
     s.handling_status = 'perlu_pengecekan_ulang';
    }
   });

   selectedTroubledIds.value = [];
   showToast(response.data.message || 'Tindak lanjut massal berhasil disimpan.', 'success');
   closeBulkSolveModal();
  } else {
   bulkSolveForm.value.errors = response.data?.message || 'Gagal memproses tindak lanjut massal.';
  }
 } catch (err) {
  const errMsg = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan tindak lanjut massal.';
  bulkSolveForm.value.errors = errMsg;
  showToast(errMsg, 'error');
 } finally {
  bulkSolveForm.value.processing = false;
 }
}

const page = usePage();
const statusFilter = ref('all');
const categoryFilter = ref('all');
const levelFilter = ref('all');
const testerFilter = ref('all');
const searchQuery = ref('');
const isTableVisible = ref(true);
const currentPage = ref(1);
const perPage = ref(10);

// Toast
const toast = ref('');
const toastType = ref('success');
let toastTimer = null;
function showToast(msg, type = 'success') {
 if (toastTimer) clearTimeout(toastTimer);
 toast.value = msg;
 toastType.value = type;
 toastTimer = setTimeout(() => { toast.value = ''; }, 4000);
}
watch(() => page.props.flash, (f) => {
 if (f?.success) showToast(f.success, 'success');
 else if (f?.error) showToast(f.error, 'error');
}, { deep: true });
onMounted(() => {
 if (page.props.flash?.success) showToast(page.props.flash.success, 'success');
 else if (page.props.flash?.error) showToast(page.props.flash.error, 'error');
});

// Status Toggle
const isUpdatingStatus = ref(false);
const showOverdueModal = ref(false);

function togglePeriodStatus() {
 const nextStatus = props.period.status === 'proses' ? 'selesai' : 'proses';

 if (nextStatus === 'proses' && props.period.is_overdue) {
  showOverdueModal.value = true;
  return;
 }

 isUpdatingStatus.value = true;
 router.put(route('admin.periods.update', props.period.id), { status: nextStatus }, {
 preserveScroll: true,
 onError: (errors) => {
 showToast(errors.status || Object.values(errors)[0] || 'Gagal mengubah status.', 'error');
 },
 onFinish: () => { isUpdatingStatus.value = false; }
 });
}

// Sinkronisasi Layanan Periode dengan Master Layanan Majadigi
const isSyncingServices = ref(false);
function syncServicesWithMaster() {
 isSyncingServices.value = true;
 router.post(route('admin.periods.sync_services', props.period.id), {}, {
 preserveScroll: true,
 onSuccess: () => {
 showToast('Layanan periode berhasil disinkronkan dengan Master Layanan Terpadu.', 'success');
 },
 onError: () => {
 showToast('Gagal menyinkronkan layanan periode.', 'error');
 },
 onFinish: () => {
 isSyncingServices.value = false;
 }
 });
}


// ─── Modal Uji Layanan (Admin / Tester) ─────────────────────────
const showCheckModal = ref(false);
const selectedService = ref(null);
const attachmentInput = ref(null);
const attachmentPreview = ref(null);
const attachmentFile = ref(null);
const existingAttachment = ref(null);
const existingAttachmentName = ref(null);

const checkForm = useForm({
	status: '',
	description: '',
	attachment: null,
	existing_attachment: '',
	remove_attachment: false,
});

const isEditMode = computed(() => {
	return selectedService.value && selectedService.value.status !== 'pending';
});

const isCheckFormValid = computed(() => {
	// Status harus dipilih ('normal' atau 'gangguan')
	if (!checkForm.status) return false;

	// Catatan pengujian wajib diisi dan tidak boleh hanya spasi kosong
	if (!checkForm.description || checkForm.description.trim() === '') return false;

	// Jika status gangguan/kendala, bukti lampiran wajib diunggah (atau sudah ada file sebelumnya)
	if (checkForm.status === 'gangguan') {
		const hasAttachment = Boolean(attachmentFile.value || (existingAttachment.value && !checkForm.remove_attachment));
		if (!hasAttachment) return false;
	}

	return true;
});

function openCheckModal(service) {
	selectedService.value = service;
	checkForm.status = service.status === 'pending' ? 'normal' : service.status;
	checkForm.description = service.description || service.last_description || '';
	checkForm.attachment = null;
	checkForm.existing_attachment = service.attachment || '';
	checkForm.remove_attachment = false;
	existingAttachment.value = service.attachment || null;
	existingAttachmentName.value = service.attachment_name || null;
	attachmentPreview.value = null;
	attachmentFile.value = null;
	if (attachmentInput.value) attachmentInput.value.value = '';
	checkForm.clearErrors();
	showCheckModal.value = true;
}

function handleFileChange(e) {
	const file = e.target.files[0];
	if (!file) return;
	attachmentFile.value = file;
	checkForm.attachment = file;
	checkForm.remove_attachment = false;

	if (file.type.startsWith('image/')) {
		const reader = new FileReader();
		reader.onload = (ev) => {
			attachmentPreview.value = ev.target.result;
		};
		reader.readAsDataURL(file);
	} else {
		attachmentPreview.value = null;
	}
}

function removeAttachment() {
	checkForm.attachment = null;
	checkForm.existing_attachment = '';
	attachmentFile.value = null;
	attachmentPreview.value = null;
	existingAttachment.value = null;
	existingAttachmentName.value = null;
	checkForm.remove_attachment = true;
	if (attachmentInput.value) attachmentInput.value.value = '';
}

function submitCheck() {
	if (!selectedService.value) return;

	checkForm.post(route('tester.submit_check', selectedService.value.id), {
		preserveScroll: true,
		forceFormData: true,
		onSuccess: (pageObj) => {
			showCheckModal.value = false;
			if (pageObj.props?.flash?.error) {
				showToast(pageObj.props.flash.error, 'error');
			} else {
				showToast(isEditMode.value ? 'Hasil pengujian berhasil diperbarui.' : 'Hasil pengecekan berhasil disimpan.', 'success');
			}
		},
		onError: (errs) => {
			const err = Object.values(errs)[0] || 'Gagal menyimpan hasil pengecekan.';
			showToast(err, 'error');
		},
		onFinish: () => {
			checkForm.processing = false;
		}
	});
}

// Filtered Services & Dynamic Export Query Params
const allServices = computed(() => {
 const list = [];
 props.distribution.forEach(g => {
  g.services.forEach(s => {
   list.push({
    ...s,
    tester_id: g.tester_id,
    tester_name: g.name,
    tester_bidang: g.bidang,
    tester_initials: g.initials,
   });
  });
 });
 return list;
});

const availableCategories = computed(() => {
 const cats = new Set();
 allServices.value.forEach(s => {
  if (s.category && s.category !== '-') {
   cats.add(s.category);
  }
 });
 return Array.from(cats).sort((a, b) => a.localeCompare(b, 'id'));
});

watch([searchQuery, statusFilter, categoryFilter, levelFilter, testerFilter], () => {
 currentPage.value = 1;
});

const filteredServices = computed(() => {
 return allServices.value.filter(s => {
  const matchStatus = statusFilter.value === 'all'
   || (statusFilter.value === 'checked' ? (s.status === 'normal' || s.status === 'gangguan') : s.status === statusFilter.value);
  const matchCategory = categoryFilter.value === 'all' || s.category === categoryFilter.value;

  let matchLevel = true;
  if (levelFilter.value !== 'all') {
   const normFilter = String(levelFilter.value).replace(/^level\s*/i, '').trim().toLowerCase();
   const normSvc = String(s.level || '').replace(/^level\s*/i, '').trim().toLowerCase();
   matchLevel = normFilter === normSvc;
  }

  const matchTester = testerFilter.value === 'all' || Number(s.tester_id) === Number(testerFilter.value);

  const q = searchQuery.value.toLowerCase().trim();
  const matchSearch = !q
   || (s.service_name || '').toLowerCase().includes(q)
   || (s.category || '').toLowerCase().includes(q)
   || (s.tester_name || '').toLowerCase().includes(q);

  return matchStatus && matchCategory && matchLevel && matchTester && matchSearch;
 });
});

const hasActiveFilters = computed(() => {
 return statusFilter.value !== 'all'
  || categoryFilter.value !== 'all'
  || levelFilter.value !== 'all'
  || testerFilter.value !== 'all'
  || searchQuery.value.trim() !== '';
});

const exportQueryParams = computed(() => {
 const params = {};
 if (categoryFilter.value !== 'all') params.category = categoryFilter.value;
 if (levelFilter.value !== 'all') params.level = levelFilter.value;
 if (statusFilter.value !== 'all') params.status = statusFilter.value;
 if (testerFilter.value !== 'all') params.tester_id = testerFilter.value;
 if (searchQuery.value.trim() !== '') params.search = searchQuery.value.trim();
 return params;
});

const exportExcelUrl = computed(() => {
 return route('admin.periods.export.excel', {
  period: props.period.id,
  ...exportQueryParams.value,
 });
});

const exportPdfUrl = computed(() => {
 return route('admin.periods.export.pdf', {
  period: props.period.id,
  ...exportQueryParams.value,
 });
});

function resetAllFilters() {
 statusFilter.value = 'all';
 categoryFilter.value = 'all';
 levelFilter.value = 'all';
 testerFilter.value = 'all';
 searchQuery.value = '';
 currentPage.value = 1;
}

const paginatedServices = computed(() => {
 const start = (currentPage.value - 1) * perPage.value;
 return filteredServices.value.slice(start, start + perPage.value);
});

const paginationMeta = computed(() => {
 const total = filteredServices.value.length;
 return {
 total: total,
 per_page: perPage.value,
 current_page: currentPage.value,
 last_page: Math.ceil(total / perPage.value) || 1,
 };
});

// Helpers
function getTesterProgress(g) {
 if (!g.total_services) return 0;
 return Math.round((g.checked_services / g.total_services) * 100);
}
function getTesterStatusLabel(g) {
 const p = getTesterProgress(g);
 if (p === 100) return 'Selesai';
 if (p > 0) return 'Proses';
 return 'Menunggu';
}

const avatarPalette = {
 'SD': 'bg-slate-600', 'AF': 'bg-primary', 'AP': 'bg-primary',
 'DR': 'bg-slate-600', 'BS': 'bg-slate-700', 'TK': 'bg-slate-500',
};
function getAvatarColor(initials, name) {
 if (avatarPalette[initials]) return avatarPalette[initials];
 const c = ['bg-slate-600','bg-primary','bg-slate-700','bg-slate-500','bg-primary'];
 return c[(name?.charCodeAt(0) ?? 0) % c.length];
}

const serviceIcons = {
 globe: `<svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>`,
 megaphone: `<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>`,
 box: `<svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>`,
 chart: `<svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
 currency: `<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
 document: `<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
 certificate: `<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>`,
 academic: `<svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>`,
 archive: `<svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>`,
 users: `<svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`,
 database: `<svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path stroke-linecap="round" d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path stroke-linecap="round" d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>`,
 'shopping-cart': `<svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>`,
};
function getServiceIcon(k) { return serviceIcons[k] || serviceIcons['globe']; }
</script>

<template>
 <Head :title="`Detail ${period.title} - SIMONTER`" />

 <AdminLayout title="Periode Pengecekan" subtitle="Atur periode dan distribusi layanan">

 <!-- Toast -->
 <Transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="opacity-0 translate-y-2"
 enter-to-class="opacity-100 translate-y-0"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0"
 leave-to-class="opacity-0 translate-y-2"
 >
 <div v-if="toast" :class="['fixed top-5 right-5 z-[120] flex items-center gap-3 px-4 py-3 rounded-2xl shadow-lg text-sm font-semibold border max-w-md', toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800']">
 <div :class="['w-6 h-6 rounded-lg flex items-center justify-center shrink-0', toastType === 'success' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white']">
 <svg v-if="toastType === 'success'" class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 <svg v-else class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
 </div>
 <span class="flex-1">{{ toast }}</span>
 <button type="button" @click="toast = ''" class="p-1 rounded-lg hover:bg-black/5 text-current/50 cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
 </div>
 </Transition>

 <div class="space-y-5 max-w-7xl mx-auto">

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 1. HEADER CARD -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="relative rounded-2xl overflow-hidden bg-primary p-6 shadow-lg">
  <!-- Dynamic Background Image -->
  <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>
  <div class="absolute inset-0 bg-gradient-to-r from-[#065f46]/85 via-[#065f46]/45 to-transparent pointer-events-none"></div>
 <!-- Dot texture overlay -->
 <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.8) 1px, transparent 1px); background-size: 20px 20px;"></div>
 <!-- Diagonal line texture -->
 <div class="absolute inset-0 opacity-[0.04]" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,1) 35px, rgba(255,255,255,1) 36px);"></div>
 <!-- Subtle glow circle -->
 <div class="absolute -top-20 -right-20 w-60 h-60 bg-emerald-400/10 rounded-full blur-3xl"></div>
 <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-primary/10 rounded-full blur-2xl"></div>

 <div class="relative z-10">
  <!-- Top Bar: Kembali link + Status Pill & Deadline -->
  <div class="flex items-center justify-between gap-3 mb-4">
   <Link :href="route('admin.periods.index')" class="inline-flex items-center gap-2 text-xs font-semibold text-white/85 hover:text-white transition-colors group cursor-pointer">
    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
    </svg>
    <span>Kembali ke Daftar Periode</span>
   </Link>

   <div class="flex items-center gap-2">
    <span v-if="period.deadline_date" class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-semibold text-white/95 bg-white/10 border border-white/20 px-3 py-1 rounded-full backdrop-blur-sm">
     <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
     <span>Tenggat: {{ period.deadline_date }}</span>
    </span>
    <span v-if="period.status === 'proses'" class="inline-flex items-center text-[11px] font-bold uppercase tracking-wider text-emerald-300 bg-emerald-500/20 border border-emerald-400/30 px-3 py-1 rounded-full backdrop-blur-sm">
     AKTIF
    </span>
    <span v-else class="inline-flex items-center text-[11px] font-bold uppercase tracking-wider text-slate-300 bg-white/10 border border-white/20 px-3 py-1 rounded-full backdrop-blur-sm">
     NONAKTIF
    </span>
   </div>
  </div>

  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pt-1">
   <div>
    <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Detail Periode: {{ period.title }}</h2>
    <p class="text-xs text-white/70 mt-1.5">
     Dibuat pada {{ period.created_at_label }} oleh {{ period.creator_name }}
     <template v-if="period.deadline_date"> <span class="mx-1 text-white/40 sm:hidden">|</span> <span class="text-amber-200/90 sm:hidden">Tenggat: {{ period.deadline_date }}</span></template>
    </p>
   </div>

   <div class="flex items-center gap-2 flex-wrap shrink-0">
    <Link :href="route('admin.periods.edit', period.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 rounded-xl transition-all cursor-pointer backdrop-blur-sm shadow-xs active:scale-95">
     <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
     </svg>
     <span>Edit Periode</span>
    </Link>
    <a :href="exportExcelUrl" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 rounded-xl transition-all cursor-pointer backdrop-blur-sm shadow-xs active:scale-95">
     <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
     <span>{{ hasActiveFilters ? `Export Excel (${filteredServices.length})` : 'Export Excel' }}</span>
    </a>
    <a :href="exportPdfUrl" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 border border-white/20 rounded-xl transition-all cursor-pointer backdrop-blur-sm shadow-xs active:scale-95">
     <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
     <span>{{ hasActiveFilters ? `Export PDF (${filteredServices.length})` : 'Export PDF' }}</span>
    </a>
    <button type="button" @click="togglePeriodStatus" :disabled="isUpdatingStatus" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-primary bg-white hover:bg-slate-100 border border-transparent rounded-xl transition-all duration-150 cursor-pointer disabled:opacity-50 shadow-md active:scale-95">
     <svg v-if="period.status === 'proses'" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
     <svg v-else class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
     <span>{{ isUpdatingStatus ? 'Memproses...' : (period.status === 'proses' ? 'Selesaikan Periode' : 'Buka Kembali Periode') }}</span>
    </button>
   </div>
  </div>
 </div>
</div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 2. KPI CARDS (4 kolom, clean monokromatik) -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
 <!-- Total Layanan -->
 <div class="bg-white rounded-2xl border border-slate-200 p-5">
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">TOTAL LAYANAN</span>
 <div class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/></svg>
 </div>
 </div>
 <p class="text-3xl font-bold text-slate-900">{{ period.total_services }}</p>
 <p class="text-xs text-slate-400 mt-1">Layanan Terpadu</p>
 </div>

 <!-- Layanan Normal -->
 <div class="bg-white rounded-2xl border border-slate-200 p-5">
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">LAYANAN NORMAL</span>
 <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-500 flex items-center justify-center">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 </div>
 </div>
 <p class="text-3xl font-bold text-slate-900">{{ period.normal_services }}</p>
 <p class="text-xs text-teal-600 mt-1 flex items-center">
 Berjalan Optimal
 </p>
 </div>

 <!-- Terkendala -->
 <div
  @click="statusFilter = 'gangguan'; isTableVisible = true"
  class="bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer hover:border-rose-300 hover:bg-rose-50/20 transition-all group"
  title="Klik untuk melihat layanan terkendala"
 >
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 group-hover:text-rose-500 uppercase tracking-wider transition-colors">TERKENDALA</span>
 <div class="w-8 h-8 rounded-xl bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-500 flex items-center justify-center transition-colors">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 </div>
 </div>
 <p class="text-3xl font-bold text-slate-900 group-hover:text-rose-600 transition-colors">{{ period.issues_services }}</p>
 <p class="text-xs text-slate-400 group-hover:text-rose-500 mt-1 transition-colors">Perlu Penanganan</p>
 </div>

 <!-- Progress Cek -->
 <div class="bg-white rounded-2xl border border-slate-200 p-5">
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">PROGRESS CEK</span>
 <span class="text-xs font-semibold text-slate-500">{{ period.checked_services }}/{{ period.total_services }}</span>
 </div>
 <div class="flex items-baseline justify-between">
 <p class="text-3xl font-bold text-slate-900">{{ period.progress_percent }}%</p>
 <span class="text-[11px] text-slate-400">dari total layanan</span>
 </div>
 <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
 <div class="h-full bg-primary rounded-full transition-all duration-500" :style="{ width: `${period.progress_percent}%` }"></div>
 </div>
 </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 2.5 SECTION: LAYANAN TERKENDALA (Tugas 13)          -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="bg-white rounded-2xl border border-slate-200 p-6 overflow-hidden shadow-xs">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
   <div class="flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-200/60 shadow-xs">
     <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
     </svg>
    </div>
    <div>
     <div class="flex items-center gap-2">
      <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Layanan Terkendala</h3>
      <span
       class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
       :class="troubledList.length > 0 ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
      >
       {{ troubledList.length }} Layanan
      </span>
     </div>
     <p class="text-xs text-slate-400 mt-0.5">Daftar layanan yang dilaporkan mengalami kendala/gangguan pada periode ini</p>
    </div>
   </div>

   <div v-if="troubledList.length > 0" class="flex flex-wrap items-center gap-2.5 text-xs text-slate-500 font-medium">
    <span>
     Ditindaklanjuti:
     <strong class="text-emerald-600 font-bold">{{ troubledList.filter(t => t.has_solved).length }}</strong> / {{ troubledList.length }}
    </span>

    <span class="text-slate-200 hidden sm:inline">|</span>

    <!-- Checkbox Pilih Semua Kendala -->
    <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-semibold cursor-pointer select-none transition-colors">
     <input
      type="checkbox"
      :checked="isAllTroubledSelected"
      @change="toggleSelectAllTroubled"
      class="w-3.5 h-3.5 rounded border-slate-300 text-primary focus:ring-primary/30 cursor-pointer"
     />
     <span>Pilih Semua ({{ troubledList.length }})</span>
    </label>

    <button
     v-if="unhandledTroubledCount > 0 && selectedTroubledIds.length !== unhandledTroubledCount"
     type="button"
     @click="selectUnhandledTroubled"
     class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold transition-colors cursor-pointer"
    >
     Pilih Belum Ditindaklanjuti ({{ unhandledTroubledCount }})
    </button>
   </div>
  </div>

  <!-- Bulk Action Bar (Muncul saat ada layanan terkendala yang dicentang) -->
  <Transition
   enter-active-class="transition duration-200 ease-out"
   enter-from-class="opacity-0 -translate-y-1"
   enter-to-class="opacity-100 translate-y-0"
   leave-active-class="transition duration-150 ease-in"
   leave-from-class="opacity-100 translate-y-0"
   leave-to-class="opacity-0 -translate-y-1"
  >
   <div
    v-if="selectedTroubledIds.length > 0"
    class="mb-4 p-3.5 rounded-2xl bg-primary/5 border border-primary/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
   >
    <div class="flex items-center gap-2.5 text-xs">
     <span class="px-2.5 py-1 rounded-full bg-primary text-white font-extrabold text-[11px]">
      {{ selectedTroubledIds.length }} Layanan Dipilih
     </span>
     <span class="text-slate-600 font-medium">
      Siap ditindaklanjuti secara serentak dengan 1 catatan Admin.
     </span>
    </div>

    <div class="flex items-center gap-2 shrink-0">
     <button
      type="button"
      @click="selectedTroubledIds = []"
      class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-xs font-semibold transition-all cursor-pointer"
     >
      Batal Pilih
     </button>
     <button
      type="button"
      @click="openBulkSolveModal"
      class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
     >
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
      <span>Tindak Lanjuti Massal ({{ selectedTroubledIds.length }} Layanan)</span>
     </button>
    </div>
   </div>
  </Transition>

  <!-- Empty State jika tidak ada kendala -->
  <div v-if="troubledList.length === 0" class="p-8 rounded-xl bg-slate-50 border border-slate-200/80 text-center">
   <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 border border-emerald-100">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
   </div>
   <p class="text-sm font-bold text-slate-700">Tidak Ada Layanan Terkendala</p>
   <p class="text-xs text-slate-400 mt-1">Seluruh layanan yang diperiksa pada periode ini berfungsi normal atau belum ditemukan kendala.</p>
  </div>

  <!-- List Layanan Terkendala -->
  <div v-else class="space-y-4">
   <div class="space-y-3">
    <div
     v-for="(item, idx) in paginatedTroubledList"
     :key="item.id"
     class="p-4 sm:p-5 rounded-2xl border transition-all text-xs"
     :class="[
      selectedTroubledIds.includes(item.id)
       ? 'ring-2 ring-primary/30 border-primary/50 bg-emerald-50/20'
       : (item.has_solved ? 'bg-slate-50/70 border-slate-200 text-slate-700' : 'bg-rose-50/40 border-rose-200/90 text-slate-800 shadow-2xs')
     ]"
    >
     <!-- Baris Atas: Header Informasi Layanan & Tombol Tindak Lanjut -->
     <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2 flex-wrap font-medium">
       <input
        type="checkbox"
        v-model="selectedTroubledIds"
        :value="item.id"
        class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary/30 cursor-pointer shrink-0 mr-0.5"
        :aria-label="`Pilih ${item.service_name}`"
       />
       <span class="font-bold text-slate-900 w-5">{{ (troubledCurrentPage - 1) * troubledPerPage + idx + 1 }}.</span>
       <h4 class="text-sm font-bold text-slate-800">{{ item.service_name }}</h4>
       <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">{{ item.category }}</span>
       <span v-if="item.level && item.level !== '-'" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">{{ /^level\b/i.test(String(item.level).trim()) ? item.level : `Level ${item.level}` }}</span>

       <span class="text-slate-300 hidden sm:inline">|</span>
       <span class="text-slate-500">
        Tester: <strong class="text-slate-700">{{ item.tester_name }}</strong>
        <span v-if="item.tester_bidang && item.tester_bidang !== '-'" class="text-slate-400 font-normal"> ({{ item.tester_bidang }})</span>
       </span>

       <span class="text-slate-300 hidden sm:inline">|</span>
       <span class="text-slate-500">
        Diperiksa: <strong class="text-slate-700">{{ item.checked_at }}</strong>
       </span>

       <span class="text-slate-300 hidden sm:inline">|</span>
       <!-- Badge Status Tindak Lanjut -->
       <span
        class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase inline-flex items-center shadow-2xs"
        :class="{
         'bg-amber-100 text-amber-800 border border-amber-200': item.handling_status === 'perlu_pengecekan_ulang',
         'bg-emerald-100 text-emerald-800 border border-emerald-200': item.has_solved || item.handling_status === 'selesai',
         'bg-rose-100 text-rose-800 border border-rose-200': !item.has_solved && item.handling_status !== 'perlu_pengecekan_ulang',
        }"
       >
        {{ item.has_solved_label }}
       </span>
      </div>

      <!-- Tombol Tindak Lanjut & Tombol Detail (Ikon Mata) -->
      <div class="flex items-center gap-2 shrink-0">
       <button
        type="button"
        @click="openIssueDetail(item)"
        class="inline-flex items-center justify-center p-2 rounded-xl text-rose-700 bg-white border border-rose-200/80 hover:bg-rose-50 hover:border-rose-300 transition-all cursor-pointer active:scale-95 shadow-2xs"
        title="Lihat Detail Hasil Pengujian & Bukti Kendala"
       >
        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
       </button>

       <button
        type="button"
        @click="openSolveModal(item)"
        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all border cursor-pointer active:scale-95 flex items-center gap-1.5 shadow-xs"
        :class="item.has_solved
         ? 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200 shadow-2xs'
         : 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600'"
        :title="item.has_solved ? 'Perbarui catatan penanganan kendala' : 'Buka modal untuk mengisi catatan dan tandai selesai kendala'"
       >
        <svg v-if="item.has_solved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ item.has_solved ? 'Edit Catatan' : 'Tandai Selesai' }}
       </button>
      </div>
     </div>

     <!-- Catatan Penanganan Admin & Tester -->
     <div class="mt-3 pt-3 border-t border-slate-200/70 space-y-2">
      <!-- Catatan Kendala Tester -->
      <div class="p-2.5 rounded-xl bg-white/80 border border-rose-200/80 text-xs">
       <span class="font-bold text-rose-800">Catatan Tester:</span>
       <span class="text-slate-700 ml-1.5 whitespace-pre-wrap">{{ item.description || '-' }}</span>
      </div>

      <!-- Catatan Penanganan Admin -->
      <div v-if="item.admin_note" class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-xs">
       <div class="flex items-center gap-1 font-bold text-emerald-800 mb-0.5">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Catatan Penanganan Admin:</span>
        <span v-if="item.admin_handled_at" class="text-[10px] text-slate-400 font-normal">({{ item.admin_handled_at }})</span>
       </div>
       <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ item.admin_note }}</p>
      </div>
     </div>
    </div>
   </div>

   <!-- Pagination Bar Layanan Terkendala -->
   <div v-if="troubledList.length > troubledPerPage" class="pt-3 px-1 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200/80">
    <p class="text-xs text-slate-500 font-medium">
     Menampilkan <span class="font-bold text-slate-800">{{ (troubledCurrentPage - 1) * troubledPerPage + 1 }}</span> - <span class="font-bold text-slate-800">{{ Math.min(troubledCurrentPage * troubledPerPage, troubledList.length) }}</span> dari <span class="font-bold text-slate-800">{{ troubledList.length }}</span> layanan terkendala
    </p>

    <div class="flex items-center gap-1.5">
     <button
      type="button"
      @click="goToTroubledPage(troubledCurrentPage - 1)"
      :disabled="troubledCurrentPage === 1"
      class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-slate-600 transition-colors shadow-2xs cursor-pointer"
     >
      &laquo; Sebelumnya
     </button>

     <button
      v-for="p in totalTroubledPages"
      :key="p"
      type="button"
      @click="goToTroubledPage(p)"
      class="w-8 h-8 text-xs font-bold rounded-xl border transition-all cursor-pointer"
      :class="p === troubledCurrentPage ? 'bg-primary text-white border-primary shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 shadow-2xs'"
     >
      {{ p }}
     </button>

     <button
      type="button"
      @click="goToTroubledPage(troubledCurrentPage + 1)"
      :disabled="troubledCurrentPage === totalTroubledPages"
      class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-slate-600 transition-colors shadow-2xs cursor-pointer"
     >
      Berikutnya &raquo;
     </button>
    </div>
   </div>
  </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 3. DISTRIBUSI TESTER (clean card grid) -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="bg-white rounded-2xl border border-slate-200 p-6">
 <div class="flex items-center justify-between mb-5">
 <div>
 <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">DISTRIBUSI & BEBAN KERJA TESTER</h3>
 <p class="text-xs text-slate-400 mt-0.5">Penugasan pengecekan performa dan status layanan</p>
 </div>
 <span class="text-xs text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full">{{ distribution.length }} Personel</span>
 </div>

 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
 <div
 v-for="group in distribution"
 :key="group.tester_id"
 class="rounded-xl border border-slate-200 p-4"
 >
 <!-- Avatar + Name + Fraction -->
 <div class="flex items-center justify-between gap-3 mb-3">
 <div class="flex items-center gap-3 min-w-0">
 <div
 :class="[
 'w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0',
 getTesterProgress(group) > 0 ? 'bg-primary' : 'bg-slate-300'
 ]"
 >{{ group.initials }}</div>
 <div class="min-w-0">
 <p class="text-sm font-semibold text-slate-800 truncate">{{ group.name }}</p>
 <p class="text-xs text-slate-400 truncate">{{ group.email }}</p>
 </div>
 </div>
 <span class="text-sm font-bold text-slate-700 shrink-0">{{ group.checked_services }}/{{ group.total_services }}</span>
 </div>

 <!-- Progress bar -->
 <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden mb-2">
 <div
 :class="['h-full rounded-full transition-all duration-300', getTesterProgress(group) === 100 ? 'bg-emerald-500' : 'bg-primary']"
 :style="{ width: `${getTesterProgress(group)}%` }"
 ></div>
 </div>

 <!-- Status + Pct -->
 <div class="flex items-center justify-between">
 <span :class="['text-xs', getTesterProgress(group) === 100 ? 'text-emerald-600 font-semibold' : (getTesterProgress(group) > 0 ? 'text-primary' : 'text-slate-400')]">
 {{ getTesterStatusLabel(group) }}
 </span>
 <span :class="['text-xs font-semibold', getTesterProgress(group) === 100 ? 'text-emerald-600' : (getTesterProgress(group) > 0 ? 'text-primary' : 'text-slate-400')]">
 {{ getTesterProgress(group) }}%
 </span>
 </div>
 </div>
 </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 4. TABEL DAFTAR LAYANAN -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
 <!-- Toolbar -->
 <div class="px-6 py-4 border-b border-slate-100 space-y-3">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
   <button type="button" @click="isTableVisible = !isTableVisible" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800 hover:text-primary transition-colors cursor-pointer">
    Daftar Layanan
    <span class="text-xs font-normal text-slate-400">({{ filteredServices.length }} dari {{ allServices.length }})</span>
    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="isTableVisible ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
   </button>

   <div v-if="isTableVisible" class="flex items-center gap-2 flex-wrap">
    <div class="inline-flex p-0.5 bg-slate-100 rounded-lg text-xs text-slate-600">
     <button type="button" @click="statusFilter = 'all'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'all' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Semua</button>
     <button type="button" @click="statusFilter = 'normal'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'normal' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Normal</button>
     <button type="button" @click="statusFilter = 'gangguan'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'gangguan' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Kendala</button>
     <button type="button" @click="statusFilter = 'checked'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'checked' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Sudah Dicek</button>
     <button type="button" @click="statusFilter = 'pending'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'pending' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Belum Dicek</button>
    </div>
    <div class="relative">
     <input v-model="searchQuery" type="text" placeholder="Cari layanan, OPD, tester..." class="pl-7 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400 focus:outline-none focus:border-primary w-48 sm:w-56 transition-colors"/>
     <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>
   </div>
  </div>

  <!-- Baris Filter Lanjutan (OPD/Kategori, Level, Tester) & Tombol Ekspor Terfilter -->
  <div v-if="isTableVisible" class="pt-2 border-t border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
   <div class="flex flex-wrap items-center gap-2">
    <!-- Filter Kategori / OPD -->
    <select
     v-model="categoryFilter"
     class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:border-primary max-w-[240px] truncate cursor-pointer"
    >
     <option value="all">Semua OPD / Kategori ({{ availableCategories.length }})</option>
     <option v-for="cat in availableCategories" :key="cat" :value="cat">
      {{ cat }}
     </option>
    </select>

    <!-- Filter Level -->
    <select
     v-model="levelFilter"
     class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:border-primary cursor-pointer"
    >
     <option value="all">Semua Level</option>
     <option value="Level 1">Level 1</option>
     <option value="Level 2">Level 2</option>
     <option value="Level 3">Level 3</option>
    </select>

    <!-- Filter Tester -->
    <select
     v-model="testerFilter"
     class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:border-primary max-w-[200px] truncate cursor-pointer"
    >
     <option value="all">Semua Tester ({{ distribution.length }})</option>
     <option v-for="g in distribution" :key="g.tester_id" :value="g.tester_id">
      {{ g.name }}
     </option>
    </select>

    <!-- Tombol Reset Filter -->
    <button
     v-if="hasActiveFilters"
     type="button"
     @click="resetAllFilters"
     class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition-colors cursor-pointer"
    >
     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
     </svg>
     <span>Reset Filter</span>
    </button>
   </div>

   <!-- Status Filter Aktif jika ada filter yang diterapkan -->
   <div v-if="hasActiveFilters" class="flex items-center gap-2 shrink-0">
    <span class="text-[11px] font-semibold text-primary bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
     Filter Aktif: {{ filteredServices.length }} Layanan
    </span>
   </div>
  </div>
 </div>

 <!-- Table -->
 <div v-show="isTableVisible" class="overflow-x-auto">
 <table class="w-full text-left">
 <thead>
 <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
									<th class="py-3 px-6">Layanan</th>
									<th class="py-3 px-6">Level</th>
									<th class="py-3 px-6">Status</th>
									<th class="py-3 px-6 text-center w-28">Aksi</th>
								</tr>
 </thead>
 <tbody class="divide-y divide-slate-100 text-xs">
 <tr v-for="srv in paginatedServices" :key="srv.id" class="hover:bg-slate-50/50 transition-colors">
 <td class="py-3.5 px-6">
										<div class="flex items-start gap-3">
											<div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100 mt-0.5" v-html="getServiceIcon(srv.icon)"></div>
											<div class="min-w-0">
												<p class="font-semibold text-slate-800 leading-snug">{{ srv.service_name }}</p>
												<div v-if="srv.service_url" class="mt-0.5">
													<a :href="srv.service_url" target="_blank" class="text-[11px] text-primary hover:underline inline-flex items-center gap-1 max-w-[260px] truncate">
														<span class="truncate">{{ srv.service_url }}</span>
														<svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
													</a>
												</div>
												<!-- Kategori di bawah link layanan -->
												<div v-if="srv.category" class="mt-1">
													<span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium">{{ srv.category }}</span>
												</div>
											</div>
										</div>
									</td>
									<td class="py-3.5 px-6 align-middle">
										<span class="text-xs font-semibold text-slate-600">{{ srv.level || '-' }}</span>
									</td>
									<td class="py-3.5 px-6 align-middle">
										<span v-if="srv.status === 'normal'" class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
											Normal
										</span>
										<span v-else-if="srv.status === 'gangguan'" class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-100">
											Kendala
										</span>
										<span v-else class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
											Belum Dicek
										</span>
									</td>
									<td class="py-3.5 px-6 text-center align-middle">
										<div class="inline-flex items-center gap-1.5 justify-center">
											<button
												v-if="srv.status === 'gangguan'"
												type="button"
												@click="openIssueDetail(srv)"
												class="inline-flex items-center justify-center p-2 rounded-xl text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 hover:border-rose-300 transition-all cursor-pointer active:scale-95 shadow-2xs"
												title="Lihat Detail Hasil Pengujian & Bukti Kendala"
											>
												<svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
											</button>
											<button
												type="button"
												@click="openCheckModal(srv)"
												class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl font-bold text-xs shadow-xs transition-all border active:scale-95 cursor-pointer"
												:class="srv.status === 'pending' ? 'bg-primary text-white border-primary hover:bg-[#047857]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 hover:border-slate-300'"
												:title="srv.status === 'pending' ? 'Lakukan Pengecekan Layanan' : 'Ubah Hasil Pengecekan'"
											>
												<svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
												<span>{{ srv.status === 'pending' ? 'Cek' : 'Ubah' }}</span>
											</button>
										</div>
									</td>
 
 </tr>
 <tr v-if="filteredServices.length === 0">
 <td colspan="4" class="py-8 text-center text-slate-400 text-xs">Tidak ada layanan yang sesuai dengan filter.</td>
 </tr>
 </tbody>
 </table>
 </div>
 <ClientPagination :meta="paginationMeta" @update:page="currentPage = $event" @update:perPage="perPage = $event; currentPage = 1" class="px-6 py-4 border-t border-slate-100 bg-slate-50/50" />
 </div>

 </div>

  <!-- ════════════════════════════════════════════════════ -->
  <!-- Modal: Peringatan Deadline Melewati Batas            -->
  <!-- ════════════════════════════════════════════════════ -->
  <Teleport to="body">
  <Transition
   enter-active-class="transition-all duration-200 ease-out"
   enter-from-class="opacity-0"
   enter-to-class="opacity-100"
   leave-active-class="transition-all duration-150 ease-in"
   leave-from-class="opacity-100"
   leave-to-class="opacity-0"
  >
   <div v-if="showOverdueModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showOverdueModal = false"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm sm:max-w-md p-6 sm:p-7 z-10 text-center">
     
     <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-4 border border-amber-200 shadow-xs">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
     </div>

     <h3 class="text-lg font-bold text-slate-800">Tidak Dapat Mengaktifkan Periode</h3>
     <p class="text-sm text-slate-500 mt-2.5 leading-relaxed mb-6">
      Tenggat waktu (deadline) untuk periode 
      <span class="font-semibold text-slate-800">"{{ period.title }}"</span> 
      telah melewati batas (<span class="font-bold text-rose-600">{{ period.deadline_formatted || period.deadline_date }}</span>).
      <br><br>
      Periode tidak dapat diaktifkan kembali saat tenggat waktu telah terlewat. Silakan 
      <span class="font-semibold text-slate-700">perbarui/edit tenggat waktu</span> terlebih dahulu.
     </p>

     <div class="flex gap-3">
      <button
       type="button"
       @click="showOverdueModal = false"
       class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer"
      >
       Batal
      </button>
      <Link
       :href="route('admin.periods.edit', period.id)"
       class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-[#047857] text-white text-sm font-bold shadow-md shadow-primary/20 active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
      >
       <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
       </svg>
       Edit Deadline
      </Link>
     </div>
    </div>
   </div>
  </Transition>
 </Teleport>
 
	<!-- ════════════════════════════════════════════════════ -->
	<!-- Modal: Uji Layanan (Admin / Tester)                 -->
	<!-- ════════════════════════════════════════════════════ -->
	<Teleport to="body">
		<Transition
			enter-active-class="transition duration-300 ease-out"
			enter-from-class="opacity-0 scale-95 translate-y-4"
			enter-to-class="opacity-100 scale-100 translate-y-0"
			leave-active-class="transition duration-200 ease-in"
			leave-from-class="opacity-100 scale-100 translate-y-0"
			leave-to-class="opacity-0 scale-95 translate-y-4"
		>
			<div v-if="showCheckModal && selectedService" class="fixed inset-0 z-[130] flex items-center justify-center p-4 sm:p-6 bg-slate-900/40 backdrop-blur-sm">
				<div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg z-10 overflow-hidden flex flex-col max-h-[90vh]">
					<!-- Modal Header -->
					<div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white">
						<div class="flex items-center gap-3 min-w-0">
							<div
								class="w-8 h-8 rounded-xl flex items-center justify-center font-bold shrink-0 shadow-xs border"
								:class="isEditMode ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : 'bg-primary border-primary text-white'"
							>
								<svg v-if="!isEditMode" class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
								</svg>
								<svg v-else class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
								</svg>
							</div>
							<div class="min-w-0">
								<h3 class="text-sm sm:text-base font-extrabold text-slate-800 leading-snug">
									{{ isEditMode ? 'Edit Hasil Pengujian' : 'Uji Layanan' }}
								</h3>
								<p class="text-xs text-slate-500 font-medium truncate">
									{{ selectedService.service_name || selectedService.name }}
								</p>
							</div>
						</div>
						<button
							type="button"
							@click="showCheckModal = false"
							class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl p-1.5 transition-colors cursor-pointer"
							aria-label="Tutup modal"
						>
							<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
						</button>
					</div>

					<!-- Modal Body (Internal Scroll) -->
					<div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs">
						<!-- SECTION 1: Informasi Layanan (Compact Metadata Layout) -->
						<div class="bg-slate-50/80 rounded-2xl border border-slate-200/80 p-4 space-y-2.5">
							<div class="flex items-center justify-between gap-2 flex-wrap">
								<div class="flex items-center gap-1.5 flex-wrap">
									<span class="px-2.5 py-0.5 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold text-xs shadow-2xs">
										{{ selectedService.category || 'Umum' }}
									</span>
									<span
										v-if="selectedService.level && selectedService.level !== '-'"
										class="px-2.5 py-0.5 rounded-full bg-primary border border-primary text-white font-bold text-[11px]"
									>
										{{ selectedService.level }}
									</span>
								</div>
								<div v-if="selectedService.service_url || selectedService.url">
									<a
										:href="selectedService.service_url || selectedService.url"
										target="_blank"
										class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-white font-bold text-xs shadow-xs transition-all hover:bg-[#047857]"
									>
										Buka URL Layanan
										<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
									</a>
								</div>
							</div>

							<div v-if="selectedService.service_url || selectedService.url" class="flex items-center gap-1.5 text-slate-500 pt-0.5">
								<svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
								<a :href="selectedService.service_url || selectedService.url" target="_blank" class="text-primary hover:underline font-mono truncate text-[11px]">
									{{ selectedService.service_url || selectedService.url }}
								</a>
							</div>

							<p v-if="selectedService.deskripsi_layanan" class="text-slate-600 bg-white border border-slate-200/70 rounded-xl p-2.5 text-xs leading-relaxed">
								{{ selectedService.deskripsi_layanan }}
							</p>

							<div v-if="selectedService.tester_instruction" class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-amber-900 shadow-2xs">
								<p class="font-extrabold text-[11px] text-amber-800 uppercase tracking-wider mb-1 flex items-center gap-1.5">
									<svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
									Instruksi Khusus
								</p>
								<p class="text-xs leading-relaxed whitespace-pre-wrap">{{ selectedService.tester_instruction }}</p>
							</div>

							<!-- Previous Result Banner if Editing -->
							<div v-if="isEditMode && selectedService.checked_at" class="bg-white border border-slate-200 rounded-xl p-2.5 flex items-center justify-between gap-2 text-slate-500">
								<span>Pengecekan sebelumnya: <strong class="text-slate-700 font-semibold">{{ selectedService.checked_at }}</strong></span>
								<span
									class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase"
									:class="selectedService.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
								>
									{{ selectedService.status === 'normal' ? 'NORMAL' : 'TERKENDALA' }}
								</span>
							</div>
						</div>

						<!-- SECTION 2: Hasil Pengecekan -->
						<form @submit.prevent="submitCheck" class="space-y-4">
							<div>
								<label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
									Hasil Pengecekan <span class="text-rose-500">*</span>
								</label>
								
								<div class="grid grid-cols-2 gap-3">
									<!-- NORMAL OPTION -->
									<label class="relative cursor-pointer select-none">
										<input type="radio" v-model="checkForm.status" value="normal" class="peer sr-only">
										<div
											class="p-3.5 rounded-2xl border transition-all flex items-center gap-3 bg-white"
											:class="checkForm.status === 'normal'
												? 'border-emerald-500 bg-emerald-50/40 text-emerald-950 ring-2 ring-emerald-500/20 shadow-xs'
												: 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
										>
											<div
												class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 font-bold transition-colors"
												:class="checkForm.status === 'normal' ? 'bg-emerald-500 text-white shadow-xs' : 'bg-slate-100 text-slate-400 border border-slate-200'"
											>
												<svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
											</div>
											<div class="min-w-0">
												<p class="font-extrabold text-xs leading-none">NORMAL</p>
												<p class="text-[10px] mt-1 text-slate-500 font-medium truncate">Berfungsi normal</p>
											</div>
										</div>
									</label>

									<!-- KENDALA OPTION -->
									<label class="relative cursor-pointer select-none">
										<input type="radio" v-model="checkForm.status" value="gangguan" class="peer sr-only">
										<div
											class="p-3.5 rounded-2xl border transition-all flex items-center gap-3 bg-white"
											:class="checkForm.status === 'gangguan'
												? 'border-rose-500 bg-rose-50/40 text-rose-950 ring-2 ring-rose-500/20 shadow-xs'
												: 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
										>
											<div
												class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 font-bold transition-colors"
												:class="checkForm.status === 'gangguan' ? 'bg-rose-500 text-white shadow-xs' : 'bg-slate-100 text-slate-400 border border-slate-200'"
											>
												<svg class="w-5 h-5 stroke-[2.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
											</div>
											<div class="min-w-0">
												<p class="font-extrabold text-xs leading-none">KENDALA</p>
												<p class="text-[10px] mt-1 text-slate-500 font-medium truncate">Ada error / gangguan</p>
											</div>
										</div>
									</label>
								</div>
								<p v-if="checkForm.errors.status" class="mt-1 text-xs font-semibold text-rose-500">{{ checkForm.errors.status }}</p>
							</div>

							<!-- SECTION 3: Catatan Pengujian / Keterangan Kendala -->
							<div>
								<label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
									{{ checkForm.status === 'gangguan' ? 'Catatan / Keterangan Kendala' : 'Catatan Pengujian' }}
									<span class="text-rose-600 font-bold lowercase">(wajib)</span>
								</label>
								<textarea
									v-model="checkForm.description"
									rows="3"
									maxlength="5000"
									class="w-full rounded-xl bg-slate-50/70 border border-slate-200 text-xs focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary p-3 leading-relaxed placeholder:text-slate-400 outline-none transition-all"
									:placeholder="checkForm.status === 'gangguan' ? 'Jelaskan secara spesifik kendala yang dialami (misal: halaman 404, lambat diakses, SSL expired, gagal login, dsb.)...' : 'Tambahkan catatan pengujian (wajib)...'"
								></textarea>
								<p v-if="checkForm.errors.description" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.description }}</p>
							</div>

							<!-- SECTION 4: Lampiran / Bukti Kendala (Attachment) -->
							<div>
								<div class="flex items-center justify-between mb-1.5">
									<label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
										Lampiran / Bukti Kendala
										<span :class="checkForm.status === 'gangguan' ? 'text-rose-600' : 'text-slate-400'" class="font-bold lowercase">
											({{ checkForm.status === 'gangguan' ? 'wajib' : 'opsional' }})
										</span>
									</label>
									<span class="text-[10px] text-slate-400">File maks. 10MB</span>
								</div>

								<!-- Existing or newly selected attachment preview -->
								<div v-if="attachmentPreview || existingAttachment || attachmentFile" class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
									<div class="flex items-center gap-3 min-w-0">
										<div v-if="attachmentPreview" class="w-12 h-12 rounded-lg border border-slate-200 overflow-hidden shrink-0 bg-white">
											<img :src="attachmentPreview" alt="Preview" class="w-full h-full object-cover" />
										</div>
										<div v-else class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
											<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
										</div>
										<div class="min-w-0">
											<p class="text-xs font-semibold text-slate-800 truncate">
												{{ attachmentFile ? attachmentFile.name : (existingAttachmentName || 'Lampiran Kendala') }}
											</p>
											<p v-if="attachmentFile" class="text-[10px] text-slate-500">
												{{ (attachmentFile.size / 1024).toFixed(1) }} KB
											</p>
											<a v-else-if="existingAttachment" :href="existingAttachment" target="_blank" class="text-[10px] text-primary hover:underline inline-flex items-center gap-0.5">
												Lihat file saat ini
												<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
											</a>
										</div>
									</div>
									<div class="flex items-center gap-1.5 shrink-0">
										<button
											type="button"
											@click="attachmentInput?.click()"
											class="px-2.5 py-1 text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition-colors cursor-pointer"
										>
											Ganti
										</button>
										<button
											type="button"
											@click="removeAttachment"
											class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
											title="Hapus Lampiran"
										>
											<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
										</button>
									</div>
								</div>

								<!-- Upload Dropzone / Button -->
								<div
									v-else
									@click="attachmentInput?.click()"
									class="border-2 border-dashed border-slate-200 hover:border-primary/50 hover:bg-primary/5 rounded-xl p-4 text-center cursor-pointer transition-all group"
								>
									<svg class="w-6 h-6 mx-auto mb-1 text-slate-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
									</svg>
									<p class="text-xs font-semibold text-slate-700 group-hover:text-primary">
										Pilih atau unggah screenshot / bukti kendala
									</p>
									<p class="text-[10px] text-slate-400 mt-0.5">
										Mendukung file JPG, PNG, WEBP, PDF, DOCX, dsb. (Maksimal 10 MB)
									</p>
								</div>

								<input
									type="file"
									ref="attachmentInput"
									@change="handleFileChange"
									accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
									class="hidden"
								/>
								<p v-if="checkForm.errors.attachment" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.attachment }}</p>
							</div>
						</form>
					</div>

					<!-- Modal Footer -->
					<div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-2.5 rounded-b-3xl">
						<button
							type="button"
							@click="showCheckModal = false"
							class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition-all cursor-pointer"
						>
							Batal
						</button>
						<button
							type="button"
							@click="submitCheck"
							:disabled="checkForm.processing || !isCheckFormValid"
							:class="(!isCheckFormValid || checkForm.processing) 
								? 'opacity-40 cursor-not-allowed bg-slate-300 text-slate-500 shadow-none' 
								: 'bg-primary hover:bg-[#047857] text-white shadow-md shadow-primary/20 cursor-pointer active:scale-95'"
							class="px-5 py-2 rounded-xl font-bold text-xs transition-all flex items-center gap-1.5"
						>
							<svg v-if="checkForm.processing" class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
								<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
								<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
							</svg>
							<span>{{ checkForm.processing ? 'Menyimpan...' : 'Simpan Hasil Pengecekan' }}</span>
						</button>
					</div>
				</div>
			</div>
		</Transition>
	</Teleport>

		<!-- Modal Detail Kendala Layanan (Sub-Komponen) -->
		<AdminIssueDetailModal
			:show="showIssueDetailModal"
			:issue="selectedIssue"
			@close="closeIssueDetail"
		/>

		<!-- Modal Tandai Selesai & Edit Catatan Kendala (Sub-Komponen) -->
		<AdminSolveIssueModal
			:show="showSolveModal"
			:item="solveTargetItem"
			:form="solveForm"
			@close="closeSolveModal"
			@submit="submitSolveKendala"
		/>

		<!-- Modal Bulk Tindak Lanjut Kendala (Tandai Selesai / Cek Ulang Sekaligus) -->
		<Teleport to="body">
			<Transition
				enter-active-class="transition duration-200 ease-out"
				enter-from-class="opacity-0 scale-95"
				enter-to-class="opacity-100 scale-100"
				leave-active-class="transition duration-150 ease-in"
				leave-from-class="opacity-100 scale-100"
				leave-to-class="opacity-0 scale-95"
			>
				<div
					v-if="showBulkSolveModal"
					class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
				>
					<div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg z-10 overflow-hidden flex flex-col max-h-[90vh]">
						<!-- Header Modal -->
						<div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
							<div class="flex items-center gap-3">
								<div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
									</svg>
								</div>
								<div>
									<h3 class="text-sm font-bold text-slate-800">
										Tindak Lanjut Massal ({{ selectedTroubledIds.length }} Layanan)
									</h3>
									<p class="text-[11px] text-slate-400">
										Tandai selesai & minta pengecekan ulang secara serentak
									</p>
								</div>
							</div>
							<button
								type="button"
								@click="closeBulkSolveModal"
								class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
							>
								<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
								</svg>
							</button>
						</div>

						<!-- Body Modal -->
						<div class="p-6 space-y-4 overflow-y-auto">
							<!-- Daftar Layanan yang Dipilih -->
							<div>
								<label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">
									Layanan Terkendala yang Dipilih ({{ selectedTroubledItems.length }})
								</label>
								<div class="max-h-32 overflow-y-auto p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-wrap gap-1.5">
									<span
										v-for="svc in selectedTroubledItems"
										:key="svc.id"
										class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs"
									>
										<span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
										<span>{{ svc.service_name }}</span>
									</span>
								</div>
							</div>

							<!-- Input Catatan Tindak Lanjut Admin -->
							<div>
								<label class="block text-xs font-bold text-slate-700 mb-1.5">
									Catatan Tindak Lanjut Admin <span class="text-rose-500">(wajib)</span>
								</label>
								<textarea
									v-model="bulkSolveForm.description"
									rows="4"
									placeholder="Contoh: Server pusat telah selesai maintenance, silakan lakukan pengecekan ulang pada layanan yang ditugaskan..."
									class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"
								></textarea>
								<p v-if="bulkSolveForm.errors" class="mt-1.5 text-xs font-semibold text-rose-600">
									{{ bulkSolveForm.errors }}
								</p>
								<p class="mt-1.5 text-[11px] text-slate-400 leading-relaxed">
									Seluruh layanan terpilih akan otomatis berubah menjadi <strong>Perlu Pengecekan Ulang</strong> dan Tester terkait akan menerima notifikasi.
								</p>
							</div>
						</div>

						<!-- Footer Modal -->
						<div class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2.5">
							<button
								type="button"
								@click="closeBulkSolveModal"
								class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-600 transition-all cursor-pointer"
							>
								Batal
							</button>
							<button
								type="button"
								@click="submitBulkSolveKendala"
								:disabled="bulkSolveForm.processing"
								class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-all disabled:opacity-60 flex items-center gap-1.5 cursor-pointer active:scale-95"
							>
								<svg v-if="bulkSolveForm.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
								</svg>
								<span>
									{{ bulkSolveForm.processing ? 'Memproses...' : `Simpan & Tandai Selesai (${selectedTroubledIds.length} Layanan)` }}
								</span>
							</button>
						</div>
					</div>
				</div>
			</Transition>
		</Teleport>

	</AdminLayout>
</template>
