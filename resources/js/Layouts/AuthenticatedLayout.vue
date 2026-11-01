<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// ─── Real-Time Inactivity Timeout (Auto Logout 5 Menit) ─────────────────
const INACTIVITY_TIMEOUT_MS = 5 * 60 * 1000; // 5 Menit
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
	// Jika waktu inaktivitas sudah melampaui 5 menit saat event baru masuk, langsung logout!
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
	window.removeEventListener('scroll', handleUserActivity);
	window.removeEventListener('touchstart', handleUserActivity);
});
</script>

<template>
 <div>
 <div class="min-h-screen bg-secondary">
 <nav
 class="border-b border-gray-100 bg-white"
 >
 <!-- Primary Navigation Menu -->
 <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
 <div class="flex h-16 justify-between">
 <div class="flex">
 <!-- Logo -->
 <div class="flex shrink-0 items-center">
 <Link :href="route('dashboard')">
 <ApplicationLogo
 class="block h-9 w-auto fill-current text-gray-800"
 />
 </Link>
 </div>

 <!-- Navigation Links -->
 <div
 class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
 >
 <NavLink
 :href="route('dashboard')"
 :active="route().current('dashboard')"
 >
 Dashboard
 </NavLink>
 </div>
 </div>

 <div class="hidden sm:ms-6 sm:flex sm:items-center">
 <!-- Settings Dropdown -->
 <div class="relative ms-3">
 <Dropdown align="right" width="48">
 <template #trigger>
 <span class="inline-flex rounded-md">
 <button
 type="button"
 class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
 >
 {{ $page.props.auth.user.name }}

 <svg
 class="-me-0.5 ms-2 h-4 w-4"
 xmlns="http://www.w3.org/2000/svg"
 viewBox="0 0 20 20"
 fill="currentColor"
 >
 <path
 fill-rule="evenodd"
 d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
 clip-rule="evenodd"
 />
 </svg>
 </button>
 </span>
 </template>

 <template #content>
 <DropdownLink
 :href="route('profile.edit')"
 >
 Profile
 </DropdownLink>
 <DropdownLink
 :href="route('logout')"
 method="post"
 as="button"
 >
 Log Out
 </DropdownLink>
 </template>
 </Dropdown>
 </div>
 </div>

 <!-- Hamburger -->
 <div class="-me-2 flex items-center sm:hidden">
 <button
 @click="
 showingNavigationDropdown =
 !showingNavigationDropdown
 "
 class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-secondary hover:text-gray-500 focus:bg-secondary focus:text-gray-500 focus:outline-none"
 >
 <svg
 class="h-6 w-6"
 stroke="currentColor"
 fill="none"
 viewBox="0 0 24 24"
 >
 <path
 :class="{
 hidden: showingNavigationDropdown,
 'inline-flex':
 !showingNavigationDropdown,
 }"
 stroke-linecap="round"
 stroke-linejoin="round"
 stroke-width="2"
 d="M4 6h16M4 12h16M4 18h16"
 />
 <path
 :class="{
 hidden: !showingNavigationDropdown,
 'inline-flex':
 showingNavigationDropdown,
 }"
 stroke-linecap="round"
 stroke-linejoin="round"
 stroke-width="2"
 d="M6 18L18 6M6 6l12 12"
 />
 </svg>
 </button>
 </div>
 </div>
 </div>

 <!-- Responsive Navigation Menu -->
 <div
 :class="{
 block: showingNavigationDropdown,
 hidden: !showingNavigationDropdown,
 }"
 class="sm:hidden"
 >
 <div class="space-y-1 pb-3 pt-2">
 <ResponsiveNavLink
 :href="route('dashboard')"
 :active="route().current('dashboard')"
 >
 Dashboard
 </ResponsiveNavLink>
 </div>

 <!-- Responsive Settings Options -->
 <div
 class="border-t border-gray-200 pb-1 pt-4"
 >
 <div class="px-4">
 <div
 class="text-base font-medium text-gray-800"
 >
 {{ $page.props.auth.user.name }}
 </div>
 <div class="text-sm font-medium text-gray-500">
 {{ $page.props.auth.user.email }}
 </div>
 </div>

 <div class="mt-3 space-y-1">
 <ResponsiveNavLink :href="route('profile.edit')">
 Profile
 </ResponsiveNavLink>
 <ResponsiveNavLink
 :href="route('logout')"
 method="post"
 as="button"
 >
 Log Out
 </ResponsiveNavLink>
 </div>
 </div>
 </div>
 </nav>

 <!-- Page Heading -->
 <header
 class="bg-white shadow"
 v-if="$slots.header"
 >
 <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
 <slot name="header" />
 </div>
 </header>

 <!-- Page Content -->
 <main>
 <slot />
 </main>
 </div>
 </div>
</template>
