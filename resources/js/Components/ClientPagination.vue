<script setup>
import { computed } from 'vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
        // Expected structure: { total: 50, per_page: 5, current_page: 1, last_page: 10 }
    },
});

const emit = defineEmits(['update:page', 'update:perPage']);

// Options untuk dropdown baris per halaman
const perPageOptions = [5, 10, 25, 50, 100];

// Fungsi untuk mengganti per_page
function onPerPageChange(event) {
    const newPerPage = Number(event.target.value);
    emit('update:perPage', newPerPage);
}

function changePage(pageNumber) {
    if (pageNumber >= 1 && pageNumber <= (props.meta.last_page || 1)) {
        emit('update:page', pageNumber);
    }
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
            page: i
        });
    }
    return links;
});

const prevPage = computed(() => {
    const currentPage = props.meta.current_page || 1;
    return currentPage > 1 ? currentPage - 1 : null;
});

const nextPage = computed(() => {
    const currentPage = props.meta.current_page || 1;
    const lastPage = props.meta.last_page || 1;
    return currentPage < lastPage ? currentPage + 1 : null;
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
            <button 
                v-if="prevPage" 
                @click="changePage(prevPage)"
                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                aria-label="Previous Page"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
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
                <button
                    v-if="!link.active"
                    @click="changePage(link.page)"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition-colors text-sm font-medium"
                >
                    {{ link.label }}
                </button>
                <span 
                    v-else-if="link.active"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white text-sm font-bold"
                >
                    {{ link.label }}
                </span>
            </template>

            <!-- Next Button -->
            <button 
                v-if="nextPage" 
                @click="changePage(nextPage)"
                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors"
                aria-label="Next Page"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
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
