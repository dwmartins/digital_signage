<script setup>
import Skeleton from 'primevue/skeleton';
import { computed } from 'vue';

const props = defineProps({
    rows: {
        type: Number,
        default: 7,
    },
    columns: {
        type: Array,
        default: () => [
            { width: '180px' },
            { width: '130px' },
            { width: '120px' },
            { width: '90px' },
            { width: '110px' },
            { width: '80px', align: 'center' },
        ],
    },
});

const skeletonRows = computed(() => Array.from({ length: props.rows }));
</script>

<template>
    <div class="skeleton-table">
        <div class="sk-row skeleton-header">
            <div
                v-for="(column, index) in columns"
                :key="`header-${index}`"
                class="sk-cell"
                :class="{ 'sk-cell-center': column.align === 'center' }"
            >
                <Skeleton :width="column.headerWidth || column.width || '100px'" height="13px" />
            </div>
        </div>

        <div
            v-for="(_, rowIndex) in skeletonRows"
            :key="rowIndex"
            class="sk-row"
            :class="{ 'skeleton-row--striped': rowIndex % 2 !== 0 }"
        >
            <div
                v-for="(column, columnIndex) in columns"
                :key="`${rowIndex}-${columnIndex}`"
                class="sk-cell"
                :class="{ 'sk-cell-center': column.align === 'center' }"
            >
                <Skeleton
                    v-if="column.shape === 'circle'"
                    shape="circle"
                    :size="column.size || '28px'"
                />

                <Skeleton
                    v-else
                    :width="column.bodyWidth || column.width || '100px'"
                    :height="column.height || '13px'"
                    :border-radius="column.borderRadius || '4px'"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.skeleton-table {
    width: 100%;
    overflow-x: auto;
}

.sk-row {
    display: grid;
    grid-template-columns: var(--table-skeleton-columns);
    align-items: center;
    gap: 12px;
    min-width: var(--table-skeleton-min-width, 860px);
    padding: 10px 16px;
    border-bottom: 0.5px solid var(--p-datatable-body-cell-border-color, #f3f4f6);
}

.skeleton-header {
    border-bottom: 1px solid var(--p-datatable-header-cell-border-color, #e5e7eb);
    opacity: 0.45;
}

.skeleton-row--striped {
    background-color: var(--p-datatable-row-striped-background, #f9fafb);
}

.sk-cell {
    display: flex;
    min-width: 0;
}

.sk-cell-center {
    justify-content: center;
}
</style>
