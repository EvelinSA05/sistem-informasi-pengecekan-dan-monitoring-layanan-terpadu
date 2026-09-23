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
    'bg-rose-500', 'bg-pink-500', 'bg-violet-500', 'bg-blue-500',
    'bg-cyan-500', 'bg-teal-500', 'bg-green-500', 'bg-amber-500',
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
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6-4a3 3 0 11-6 0 3 3 0 016 0zM3 16a3 3 0 116 0"/>
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

// ─── Real-Time Inactivity Timeout ────────────────────────────────
let inactivityTimeout;

const resetInactivityTimeout = () => {
    clearTimeout(inactivityTimeout);
    inactivityTimeout = setTimeout(() => {
        // Trigger logout otomatis dengan indikator timeout
        router.post(route('logout'), { timeout: true });
    }, 5 * 60 * 1000); // 5 Menit
};

onMounted(() => {
    window.addEventListener('mousemove', resetInactivityTimeout);
    window.addEventListener('keydown', resetInactivityTimeout);
    window.addEventListener('click', resetInactivityTimeout);
    window.addEventListener('scroll', resetInactivityTimeout);
    resetInactivityTimeout();
});

onUnmounted(() => {
    window.removeEventListener('mousemove', resetInactivityTimeout);
    window.removeEventListener('keydown', resetInactivityTimeout);
    window.removeEventListener('click', resetInactivityTimeout);
    window.removeEventListener('scroll', resetInactivityTimeout);
    clearTimeout(inactivityTimeout);
});
</script>

<template>
    <div class="flex min-h-screen bg-[#f8fafc] font-sans antialiased text-slate-800">

        <!-- ─── Mobile Backdrop Overlay ───────────────────────── -->
        <div
            v-if="isSidebarOpen"
            @click="isSidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-300 lg:hidden"
        ></div>

        <!-- ─── Sidebar ──────────────────────────────────────── -->
        <aside
            class="fixed inset-y-0 left-0 w-64 flex flex-col
                   bg-gradient-to-b from-[#1e40af] via-[#1d4ed8] to-[#1e3a8a]
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
                <div class="px-4 py-2 rounded-2xl bg-white shadow-md shadow-blue-950/20 border border-white/60 flex items-center justify-center">
                    <img
                        src="/images/logo_majadigi.png"
                        alt="Majadigi - Majapahit Digital"
                        class="h-7 w-auto object-contain"
                    />
                </div>

                <!-- Sub-title Badge Below Logo -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
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
                            ? 'bg-white text-blue-900 shadow-lg shadow-blue-950/10'
                            : item.href === '#'
                            ? 'text-blue-100/70 hover:bg-white/10 hover:text-white'
                            : 'text-blue-100/80 hover:bg-white/10 hover:text-white'"
                    >
                        <!-- Active indicator pill / dot -->
                        <span
                            v-if="(item.activeRoute && route().current(item.activeRoute)) || (item.route && route().current(item.route))"
                            class="absolute right-3.5 w-2 h-2 rounded-full bg-blue-600 shadow-sm"
                        ></span>
                        <!-- Icon -->
                        <span
                            class="shrink-0 w-5 h-5 transition-transform group-hover:scale-105"
                            :class="(item.activeRoute && route().current(item.activeRoute)) || (item.route && route().current(item.route)) ? 'text-blue-700' : 'text-blue-200/90'"
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
                            <p class="text-blue-200/80 text-[11px] font-medium truncate capitalize">
                                {{ user?.role }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showLogoutModal = true"
                        type="button"
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold text-blue-100/90 hover:text-white hover:bg-white/10 transition-all duration-150"
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
                        class="p-2 rounded-xl text-slate-700 bg-slate-100/80 hover:text-blue-600 hover:bg-blue-50 active:scale-95 border border-slate-200/80 transition-all duration-150 shadow-xs flex items-center justify-center"
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

                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200/80 text-xs font-semibold text-emerald-600 shadow-sm shrink-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="hidden sm:inline">Sistem Aktif</span>
                    <span class="sm:hidden text-[10px]">Aktif</span>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
                <slot />
            </main>
        </div>

    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- Modal Konfirmasi Logout                              -->
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
                            class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all"
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
