<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
 title: { type: String, default: 'Dashboard' },
 subtitle: { type: String, default: 'Selamat datang di Majadigi Checker' },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Inisial nama untuk avatar
const initials = computed(() => {
 const names = user.value?.name?.split(' ') ?? [];
 if (names.length >= 2) return (names[0][0] + names[1][0]).toUpperCase();
 return (names[0]?.[0] ?? 'A').toUpperCase();
});

// Avatar color based on name
const avatarColors = [
 'bg-slate-500', 'bg-slate-600', 'bg-slate-700', 'bg-primary',
 'bg-primary', 'bg-primary', 'bg-slate-400'
];
const avatarColor = computed(() => {
 const idx = (user.value?.name?.charCodeAt(0) ?? 0) % avatarColors.length;
 return avatarColors[idx];
});

// Menu navigasi admin
const adminNavItems = [
 {
 label: 'Dashboard',
 route: 'dashboard',
 activeRoute: 'dashboard',
 href: '/dashboard',
 icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
 <rect x="3" y="3" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 <rect x="14" y="3" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 <rect x="3" y="14" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 <rect x="14" y="14" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 </svg>`,
 },
 {
 label: 'Manajemen User',
 route: 'admin.users.index',
 activeRoute: 'admin.users.*',
 href: '/admin/users',
 icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
 <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
 </svg>`,
 },
 {
 label: 'Periode Pengecekan',
 route: 'admin.periods.index',
 activeRoute: 'admin.periods.*',
 href: '/admin/periods',
 icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
 <rect x="3" y="4" width="18" height="18" rx="2" stroke-linecap="round"/>
 <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/>
 </svg>`,
 },
 {
 label: 'Master Layanan',
 route: 'admin.services.index',
 activeRoute: 'admin.services.*',
 href: '/admin/services',
 icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
 <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2"/>
 <rect x="2" y="3" width="20" height="14" rx="2"/>
 <path stroke-linecap="round" d="M8 21h8M12 17v4"/>
 </svg>`,
 },
];

// Menu navigasi tester: Dashboard & Layanan Ditugaskan
const testerNavItems = [
 {
 label: 'Dashboard',
 route: 'dashboard',
 activeRoute: 'dashboard',
 href: '/dashboard',
 icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
 <rect x="3" y="3" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 <rect x="14" y="3" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 <rect x="3" y="14" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 <rect x="14" y="14" width="7" height="7" rx="1.5" stroke-linecap="round"/>
 </svg>`,
 },
 {
 label: 'Layanan Ditugaskan',
 route: 'tester.services.index',
 activeRoute: 'tester.*',
 href: '/tester/services',
 icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
 <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2"/>
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
 </svg>`,
 },
];

const navItems = computed(() => {
 return user.value?.role === 'admin' ? adminNavItems : testerNavItems;
});

const isSidebarOpen = ref(typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);

function closeSidebarOnMobile() {
 if (typeof window !== 'undefined' && window.innerWidth < 1024) {
 isSidebarOpen.value = false;
 }
}

const showLogoutModal = ref(false);

function confirmLogout() {
 router.post(route('logout'));
}

// ─── Header User Profile Dropdown State & Handlers ──────────────────────
const isProfileOpen = ref(false);
const profileDropdownRef = ref(null);

function toggleProfileDropdown() {
 isProfileOpen.value = !isProfileOpen.value;
 if (isProfileOpen.value) {
  isNotifOpen.value = false;
 }
}

// ─── In-App Notification Bell State & Handlers ──────────────────────────
const isNotifOpen = ref(false);
const notifDropdownRef = ref(null);

const notifications = computed(() => {
 return page.props.notifications ?? { unread_count: 0, total_count: 0, items: [] };
});

const unreadNotifCount = computed(() => notifications.value?.unread_count ?? 0);
const notifItems = computed(() => notifications.value?.items ?? []);

function toggleNotifDropdown() {
 isNotifOpen.value = !isNotifOpen.value;
 if (isNotifOpen.value) {
  isProfileOpen.value = false;
 }
}

function handleOutsideNotifClick(event) {
 if (isNotifOpen.value && notifDropdownRef.value && !notifDropdownRef.value.contains(event.target)) {
  isNotifOpen.value = false;
 }
 if (isProfileOpen.value && profileDropdownRef.value && !profileDropdownRef.value.contains(event.target)) {
  isProfileOpen.value = false;
 }
}

function markAllNotificationsRead() {
 router.post(route('notifications.mark_read'), {}, {
  preserveScroll: true,
  preserveState: true,
 });
}

function openNotificationItem(item) {
 isNotifOpen.value = false;
 if (item?.is_unread) {
  router.post(route('notifications.mark_read'), {}, {
   preserveScroll: true,
   preserveState: true,
   onFinish: () => {
    if (item?.url) router.visit(item.url);
   },
  });
 } else if (item?.url) {
  router.visit(item.url);
 }
}

function notifBadgeClass(severity) {
 if (severity === 'danger') return 'bg-rose-50 text-rose-700 border-rose-200';
 if (severity === 'warning') return 'bg-amber-50 text-amber-700 border-amber-200';
 return 'bg-blue-50 text-blue-700 border-blue-200';
}

function notifIconClass(severity) {
 if (severity === 'danger') return 'bg-rose-50 text-rose-600 border-rose-100';
 if (severity === 'warning') return 'bg-amber-50 text-amber-600 border-amber-100';
 return 'bg-blue-50 text-primary border-blue-100';
}

// ─── Real-Time Inactivity Timeout (Auto Logout 1 Jam) ───────────────────
const INACTIVITY_TIMEOUT_MS = 60 * 60 * 1000; // 1 Jam (60 Menit)
let lastActivityTime = Date.now();
let inactivityInterval = null;

function performAutoLogout() {
	if (inactivityInterval) {
		clearInterval(inactivityInterval);
		inactivityInterval = null;
	}
	try {
		localStorage.setItem('session_timeout', '1');
	} catch (e) {}
	// Hard redirect langsung ke login dengan query timeout=1 tanpa refresh manual
	window.location.href = '/?timeout=1';
}

function handleUserActivity() {
	const elapsed = Date.now() - lastActivityTime;
	// Jika waktu inaktivitas sudah melampaui 1 jam saat event baru masuk, langsung logout!
	if (elapsed >= INACTIVITY_TIMEOUT_MS) {
		performAutoLogout();
		return;
	}
	lastActivityTime = Date.now();
}

onMounted(() => {
	lastActivityTime = Date.now();

	// Periksa selisih waktu setiap 1 detik secara real-time
	inactivityInterval = setInterval(() => {
		if (Date.now() - lastActivityTime >= INACTIVITY_TIMEOUT_MS) {
			performAutoLogout();
		}
	}, 1000);

	window.addEventListener('mousemove', handleUserActivity, { passive: true });
	window.addEventListener('keydown', handleUserActivity, { passive: true });
	window.addEventListener('click', handleUserActivity, { passive: true });
	window.addEventListener('click', handleOutsideNotifClick);
	window.addEventListener('scroll', handleUserActivity, { passive: true });
	window.addEventListener('touchstart', handleUserActivity, { passive: true });
});

onUnmounted(() => {
	if (inactivityInterval) {
		clearInterval(inactivityInterval);
		inactivityInterval = null;
	}
	window.removeEventListener('mousemove', handleUserActivity);
	window.removeEventListener('keydown', handleUserActivity);
	window.removeEventListener('click', handleUserActivity);
	window.removeEventListener('click', handleOutsideNotifClick);
	window.removeEventListener('scroll', handleUserActivity);
	window.removeEventListener('touchstart', handleUserActivity);
});
</script>

<template>
 <div class="flex min-h-screen bg-secondary font-sans antialiased text-slate-800">

 <!-- ─── Mobile Backdrop Overlay ───────────────────────── -->
 <div
 v-if="isSidebarOpen"
 @click="isSidebarOpen = false"
 class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-300 lg:hidden"
 ></div>

 <!-- ─── Sidebar ──────────────────────────────────────── -->
 <aside
 class="fixed inset-y-0 left-0 w-64 flex flex-col
 bg-primary border-r border-primary/50
 shadow-2xl select-none transition-transform duration-300 ease-in-out"
 :class="isSidebarOpen ? 'translate-x-0 z-50' : '-translate-x-full z-30'"
 >

 <!-- Logo / Brand identical with Auth Page -->
 <div class="px-5 pt-6 pb-5 border-b border-white/10 flex flex-col items-start gap-2.5 relative">
 <!-- Close Button -->
 <button
 @click="isSidebarOpen = false"
 type="button"
 class="absolute top-5 right-4 p-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white transition-all duration-150 flex items-center justify-center"
 aria-label="Tutup sidebar"
 >
 <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
 </svg>
 </button>

 <!-- Official Logo Card in White Pill -->
 <div class="px-4 py-2 rounded-2xl bg-white shadow-md shadow-primary/20 border border-white/60 flex items-center justify-center">
 <img
 src="/images/logo_majadigi.png"
 alt="Majadigi - Majapahit Digital"
 class="h-7 w-auto object-contain"
 />
 </div>

 <!-- Sub-title Badge Below Logo -->
 <div class="inline-flex items-center px-3 py-1 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 shadow-sm">
 <span class="text-[10.5px] font-bold tracking-widest text-cyan-200 uppercase">CHECKER SYSTEM</span>
 </div>
 </div>

 <!-- Navigation Menu -->
 <nav class="flex-1 px-3.5 py-5 space-y-1.5 overflow-y-auto">
 <template v-for="item in navItems" :key="item.label">
 <Link
 :href="item.href"
 @click="closeSidebarOnMobile"
 class="group relative flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all duration-200"
 :class="(item.activeRoute && route().current(item.activeRoute)) || (item.route && route().current(item.route))
 ? 'bg-white text-primary shadow-lg'
 : item.href === '#'
 ? 'text-white/70 hover:bg-white/10 hover:text-white'
 : 'text-white/70 hover:bg-white/10 hover:text-white'"
 >
 <!-- Icon -->
 <span
 class="shrink-0 w-5 h-5 transition-transform group-hover:scale-105"
 :class="(item.activeRoute && route().current(item.activeRoute)) || (item.route && route().current(item.route)) ? 'text-primary' : 'text-white/70'"
 v-html="item.icon"
 ></span>
 <!-- Label -->
 <span class="truncate font-medium">{{ item.label }}</span>
 </Link>
 </template>
 </nav>

 <!-- User Profile Bottom Area -->
 <div class="px-3.5 pb-6">
 <div class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 p-3.5 shadow-inner">
 <div class="flex items-center gap-3 mb-3">
 <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow shrink-0', avatarColor]">
 {{ initials }}
 </div>
 <div class="min-w-0">
 <p class="text-white text-sm font-bold truncate leading-snug">{{ user?.name }}</p>
 <p class="text-white/80 text-[11px] font-medium truncate capitalize">
 {{ user?.role }}
 </p>
 </div>
 </div>
 <button
 @click="showLogoutModal = true"
 type="button"
 class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold text-secondary/90 hover:text-white hover:bg-white/10 transition-all duration-150"
 >
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
 </svg>
 Keluar
 </button>
 </div>
 </div>
 </aside>

 <!-- ─── Main Content Area ─────────────────────────── -->
 <div 
 class="flex-1 flex flex-col min-w-0 transition-all duration-300"
 :class="isSidebarOpen ? 'lg:ml-64 ml-0' : 'ml-0'"
 >

 <!-- Top bar -->
 <header class="sticky top-0 z-20 bg-white/85 backdrop-blur-md border-b border-slate-200/70 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
 <div class="flex items-center gap-3 sm:gap-4">
 <!-- Hamburger / Bar Toggle Button -->
 <button
 @click="isSidebarOpen = !isSidebarOpen"
 type="button"
 class="p-2 rounded-xl text-slate-700 bg-slate-100/80 hover:text-secondary hover:bg-primary active:scale-95 border border-slate-200/80 transition-all duration-150 shadow-xs flex items-center justify-center"
 :class="isSidebarOpen ? 'lg:hidden' : ''"
 aria-label="Buka Menu Navigasi"
 >
 <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
 <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
 </svg>
 </button>

 <div>
 <h1 class="text-base sm:text-lg font-bold text-slate-800 leading-none truncate">{{ title }}</h1>
 <p class="text-[11px] sm:text-xs text-slate-400 mt-1 font-medium truncate">{{ subtitle }}</p>
 </div>
 </div>

 <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
  <!-- Lonceng Notifikasi In-App -->
  <div ref="notifDropdownRef" class="relative">
   <button
    @click.stop="toggleNotifDropdown"
    type="button"
    class="relative p-2 rounded-xl bg-slate-100/80 hover:bg-slate-200/70 text-slate-700 border border-slate-200/80 transition-all duration-150 flex items-center justify-center focus:outline-none cursor-pointer"
    aria-label="Notifikasi"
    title="Notifikasi Sistem"
   >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
    </svg>

    <!-- Badge Jumlah Belum Dibaca -->
    <span
     v-if="unreadNotifCount > 0"
     class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center shadow-sm ring-2 ring-white"
    >
     {{ unreadNotifCount > 9 ? '9+' : unreadNotifCount }}
    </span>
    <span
     v-else-if="notifItems.length > 0"
     class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-white"
    ></span>
   </button>

   <!-- Dropdown Panel Notifikasi -->
   <Transition
    enter-active-class="transition ease-out duration-150"
    enter-from-class="opacity-0 translate-y-1 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 translate-y-1 scale-95"
   >
    <div
     v-if="isNotifOpen"
     class="fixed sm:absolute right-3 sm:right-0 top-16 sm:top-auto sm:mt-2.5 w-[calc(100vw-1.5rem)] sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden z-50"
    >
     <!-- Header Dropdown -->
     <div class="px-4 py-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
       <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Notifikasi</h3>
       <span
        v-if="unreadNotifCount > 0"
        class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-bold"
       >
        {{ unreadNotifCount }} Baru
       </span>
      </div>
      <button
       v-if="unreadNotifCount > 0"
       @click.stop="markAllNotificationsRead"
       type="button"
       class="text-[11px] font-bold text-primary hover:underline cursor-pointer"
      >
       Tandai Dibaca
      </button>
     </div>

     <!-- Daftar Item Notifikasi -->
     <div v-if="notifItems.length > 0" class="max-h-80 overflow-y-auto divide-y divide-slate-100">
      <button
       v-for="item in notifItems"
       :key="item.id"
       type="button"
       @click="openNotificationItem(item)"
       class="w-full text-left px-4 py-3.5 hover:bg-slate-50/90 transition-colors flex items-start gap-3 cursor-pointer"
       :class="item.is_unread ? 'bg-blue-50/30' : 'bg-white'"
      >
       <!-- Icon Berdasarkan Severity -->
       <div
        class="w-8 h-8 rounded-xl border flex items-center justify-center shrink-0 mt-0.5"
        :class="notifIconClass(item.severity)"
       >
        <svg v-if="item.severity === 'danger'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
         <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <svg v-else-if="item.severity === 'warning'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
         <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
         <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
       </div>

       <!-- Konten Notifikasi -->
       <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between gap-2 mb-1">
         <span
          class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border"
          :class="notifBadgeClass(item.severity)"
         >
          {{ item.badge }}
         </span>
         <span class="text-[10px] text-slate-400 font-medium shrink-0">{{ item.time_label }}</span>
        </div>
        <p class="text-xs font-bold text-slate-800 truncate">{{ item.title }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2 leading-relaxed">{{ item.description }}</p>
       </div>

       <!-- Indikator Unread -->
       <span
        v-if="item.is_unread"
        class="w-2 h-2 rounded-full bg-rose-500 shrink-0 mt-2"
        title="Belum dibaca"
       ></span>
      </button>
     </div>

     <!-- Empty State -->
     <div v-else class="px-5 py-8 text-center">
      <div class="w-11 h-11 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2.5 text-slate-300">
       <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
       </svg>
      </div>
      <p class="text-xs font-bold text-slate-700">Tidak ada notifikasi aktif</p>
      <p class="text-[11px] text-slate-400 mt-0.5">Semua pemberitahuan kendala & pengingat tenggat waktu akan muncul di sini.</p>
     </div>
    </div>
   </Transition>
  </div>

  <!-- Profil User yang Sedang Login + Detail User Dropdown -->
  <div ref="profileDropdownRef" class="relative shrink-0">
   <button
    type="button"
    @click="toggleProfileDropdown"
    class="flex items-center gap-2.5 pl-1.5 pr-3 py-1 rounded-full bg-slate-50 hover:bg-slate-100 border border-slate-200/90 text-xs font-semibold text-slate-700 shadow-2xs transition-all cursor-pointer active:scale-95"
    aria-label="Profil Pengguna"
   >
    <span class="w-7 h-7 rounded-full bg-primary text-white text-[11px] font-bold flex items-center justify-center shrink-0">
     {{ initials }}
    </span>
    <span class="hidden sm:flex flex-col items-start leading-tight text-left max-w-[140px]">
     <span class="font-bold text-slate-800 truncate w-full">{{ user?.name || 'Pengguna' }}</span>
     <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ user?.role === 'admin' ? 'Admin' : 'Tester' }}</span>
    </span>
    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150" :class="isProfileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
   </button>

   <Transition
    enter-active-class="transition ease-out duration-150"
    enter-from-class="opacity-0 translate-y-1 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 translate-y-1 scale-95"
   >
    <div
     v-if="isProfileOpen"
     class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden"
    >
     <!-- Header Profil -->
     <div class="p-4 bg-gradient-to-br from-primary to-[#1e3a8a] text-white flex items-center gap-3">
      <div class="w-11 h-11 rounded-full bg-white/20 border border-white/30 flex items-center justify-center text-sm font-extrabold shrink-0">
       {{ initials }}
      </div>
      <div class="min-w-0 flex-1">
       <p class="text-sm font-bold truncate">{{ user?.name || 'Pengguna' }}</p>
       <p class="text-[11px] text-white/80 truncate">{{ user?.email || '-' }}</p>
       <div class="flex items-center gap-1.5 mt-1.5">
        <span class="px-2 py-0.5 rounded-md bg-white/20 text-[10px] font-bold uppercase tracking-wider">
         {{ user?.role === 'admin' ? 'Administrator' : 'Tester' }}
        </span>
        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider" :class="user?.status === 'aktif' ? 'bg-emerald-400/25 text-emerald-100' : 'bg-rose-400/25 text-rose-100'">
         {{ user?.status === 'aktif' ? 'Aktif' : (user?.status || 'Aktif') }}
        </span>
       </div>
      </div>
     </div>

     <!-- Detail Informasi User -->
     <div class="p-4 space-y-2.5 text-xs border-b border-slate-100">
      <div class="flex items-center justify-between gap-2">
       <span class="text-slate-400 font-medium">Username</span>
       <span class="font-semibold text-slate-700 truncate">{{ user?.username || '-' }}</span>
      </div>
      <div class="flex items-center justify-between gap-2">
       <span class="text-slate-400 font-medium">Email</span>
       <span class="font-semibold text-slate-700 truncate">{{ user?.email || '-' }}</span>
      </div>
      <div v-if="user?.nip" class="flex items-center justify-between gap-2">
       <span class="text-slate-400 font-medium">NIP</span>
       <span class="font-semibold text-slate-700 truncate">{{ user.nip }}</span>
      </div>
      <div v-if="user?.bidang" class="flex items-center justify-between gap-2">
       <span class="text-slate-400 font-medium">Bidang</span>
       <span class="font-semibold text-slate-700 truncate text-right max-w-[170px]">{{ user.bidang }}</span>
      </div>
     </div>

     <!-- Aksi Cepat -->
     <div class="p-2.5 bg-slate-50/70 flex items-center gap-2">
      <Link
       :href="route('profile.edit')"
       @click="isProfileOpen = false"
       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition-colors"
      >
       <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
       </svg>
       <span>Edit Profil</span>
      </Link>
      <button
       type="button"
       @click="isProfileOpen = false; showLogoutModal = true"
       class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200/70 text-xs font-semibold text-rose-600 transition-colors cursor-pointer"
      >
       <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
       </svg>
       <span>Keluar</span>
      </button>
     </div>
    </div>
   </Transition>
  </div>
 </div>
 </header>

 <!-- Page Content -->
 <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-secondary">
 <slot />
 </main>
 </div>

 </div>

 <!-- ════════════════════════════════════════════════════ -->
 <!-- Modal Konfirmasi Logout -->
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
 <div v-if="showLogoutModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
 <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showLogoutModal = false"></div>
 <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
 <div class="flex flex-col items-center text-center mb-6">
 <div class="w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center mb-4 text-rose-500 shadow-inner">
 <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
 </svg>
 </div>
 <h3 class="text-lg font-bold text-slate-800 mb-1">Keluar dari sistem?</h3>
 <p class="text-sm text-slate-500">
 Anda harus masuk kembali untuk mengakses MAJADIGI CHECKER.
 </p>
 </div>
 <div class="flex gap-3">
 <button
 @click="showLogoutModal = false"
 class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-secondary transition-all"
 >
 Batal
 </button>
 <button
 @click="confirmLogout"
 class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold shadow-sm transition-all shadow-rose-500/20"
 >
 Ya, Keluar
 </button>
 </div>
 </div>
 </div>
 </Transition>
 </Teleport>
</template>
