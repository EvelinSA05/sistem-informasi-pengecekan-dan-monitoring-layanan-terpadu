<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
        // The structure expected is the standard Laravel length-aware paginator output
        // { links: [], from: 1, to: 10, total: 50, per_page: 5, current_page: 1, path: '...' }
    },
});

// Options untuk dropdown baris per halaman
const perPageOptions = [5, 10, 25, 50];

// Fungsi untuk mengganti per_page
function onPerPageChange(event) {
    const newPerPage = event.target.value;
    
    // Get current query params from the URL (without page, because we reset to page 1)
    const currentUrl = new URL(window.location.href);
    const searchParams = new URLSearchParams(currentUrl.search);
    
    searchParams.set('per_page', newPerPage);
    searchParams.delete('page'); // Reset to first page
    
    // Melakukan navigasi Inertia
    router.get(window.location.pathname + '?' + searchParams.toString(), {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function getPageUrl(pageNumber) {
    const currentUrl = new URL(window.location.href);
    const searchParams = new URLSearchParams(currentUrl.search);
    
    searchParams.set('page', pageNumber);
    if (props.meta.per_page) {
        searchParams.set('per_page', props.meta.per_page);
    }
    
    return window.location.pathname + '?' + searchParams.toString();
}

const pageLinks = computed(() => {
    const windowSize = 5;
    const currentPage = props.meta.current_page || 1;
    const lastPage = props.meta.last_page || 1;
    
    let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
    let endPage = startPage + windowSize - 1;

    if (endPage > lastPage) {
        endPage = lastPage;
        startPage = Math.max(1, endPage - windowSize + 1);
    }

    const links = [];
    for (let i = startPage; i <= endPage; i++) {
        links.push({
            label: String(i),
            active: i === currentPage,
            url: getPageUrl(i)
        });
    }
    return links;
});

const prevLink = computed(() => {
    const currentPage = props.meta.current_page || 1;
    if (currentPage > 1) {
        return { url: getPageUrl(currentPage - 1) };
    }
    return null;
});

const nextLink = computed(() => {
    const currentPage = props.meta.current_page || 1;
    const lastPage = props.meta.last_page || 1;
    if (currentPage < lastPage) {
        return { url: getPageUrl(currentPage + 1) };
    }
    return null;
});
</script>

<template>
    <div v-if="meta.total > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3 text-sm">
        <!-- Bagian Kiri: Rows per page & Total -->
        <div class="flex items-center gap-3 text-slate-600">
            <span>Baris per halaman</span>
            <select 
                :value="meta.per_page" 
                @change="onPerPageChange"
                class="bg-white border border-slate-300 text-slate-700 py-1 pl-3 pr-8 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary cursor-pointer text-sm"
            >
                <option v-for="option in perPageOptions" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>
            <span>• Total {{ meta.total }}</span>
        </div>

        <!-- Bagian Kanan: Pagination Navigation -->
        <div class="flex items-center gap-1.5">
            <!-- Prev Button -->
            <Link 
                v-if="prevLink && prevLink.url" 
                :href="prevLink.url"
                preserve-scroll
                preserve-state
                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                aria-label="Previous Page"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <span 
                v-else 
                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-300 cursor-not-allowed"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </span>

            <!-- Page Numbers -->
            <template v-for="(link, index) in pageLinks" :key="index">
                <Link
                    v-if="link.url && !link.active"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition-colors text-sm font-medium"
                >
                    {{ link.label }}
                </Link>
                <span 
                    v-else-if="link.active"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white text-sm font-bold"
                >
                    {{ link.label }}
                </span>
                <span 
                    v-else
                    class="w-8 h-8 flex items-center justify-center text-slate-400 text-sm"
                >
                    ...
                </span>
            </template>

            <!-- Next Button -->
            <Link 
                v-if="nextLink && nextLink.url" 
                :href="nextLink.url"
                preserve-scroll
                preserve-state
                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                aria-label="Next Page"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>
            <span 
                v-else 
                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-300 cursor-not-allowed"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        </div>
    </div>
</template>
