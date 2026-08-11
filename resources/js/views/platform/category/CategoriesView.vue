<script setup>
import CategoryDeleteDialog from '@/components/dialog/category/CategoryDeleteDialog.vue';
import CategoryFormDialog from '@/components/dialog/category/CategoryFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { useQueryFilters } from '@/composables/useQueryFilters';
import { showAlert } from '@/helpers/alert';
import categoryService from '@/services/category.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref } from 'vue';

const authStore = useAuthStore();
const categories = ref([]);
const category = ref(null);
const loading = ref(false);
const pagination = ref({});
const currentPage = ref(1);
const itemsPerPage = ref(7);
const dialogs = reactive({ form: false, delete: false });
const filters = reactive({
    global: { value: null, type: 'string' },
    status: { value: null, type: 'string' },
});
const statusOptions = [
    { label: 'Ativa', value: 'active' },
    { label: 'Inativa', value: 'inactive' },
];
const breadcrumbItens = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Categorias', to: '/platform/categorias' },
];
const skeletonColumns = [
    { headerWidth: '80px', bodyWidth: '150px' },
    { headerWidth: '45px', bodyWidth: '125px' },
    { headerWidth: '75px', bodyWidth: '230px' },
    { headerWidth: '50px', bodyWidth: '62px', height: '22px', borderRadius: '999px' },
    { headerWidth: '50px', bodyWidth: '72px', height: '28px', borderRadius: '999px', align: 'center' },
];
const canCreate = computed(() => authStore.hasPermission('categories.create'));
const canUpdate = computed(() => authStore.hasPermission('categories.update'));
const canDelete = computed(() => authStore.hasPermission('categories.delete'));
const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(filters, currentPage);

const fetchAll = async page => {
    syncToRoute(page);
    try {
        loading.value = true;
        const response = await categoryService.index(page, itemsPerPage.value, buildApiFilters());
        categories.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
};

const openDialog = (type, data = null) => {
    const allowed = type === 'delete' ? canDelete.value : (data ? canUpdate.value : canCreate.value);
    if (!allowed) return showAlert('warning', 'Você não possui permissão para realizar esta ação.');
    category.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const clearFilters = () => {
    Object.values(filters).forEach(filter => filter.value = null);
    fetchAll(1);
};

const onPage = event => {
    itemsPerPage.value = event.rows;
    fetchAll(event.page + 1);
};

onMounted(() => {
    applyFromRoute();
    fetchAll(currentPage.value);
});
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItens" />
        <div class="d-flex justify-content-end mb-3">
            <Button label="Nova categoria" icon="pi pi-plus" size="small" :disabled="!canCreate" @click="openDialog('form')" />
        </div>

        <Card class="mb-3"><template #content>
            <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                <div class="col-lg-4"><div class="field">
                    <label for="global">Buscar</label>
                    <IconField><InputIcon class="pi pi-search" /><InputText id="global" v-model="filters.global.value" placeholder="Nome, slug ou descrição" fluid /></IconField>
                </div></div>
                <div class="col-md-6 col-lg-2"><div class="field">
                    <label for="status">Status</label>
                    <Select id="status" v-model="filters.status.value" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todas" showClear fluid />
                </div></div>
                <div class="col-12 col-lg-auto ms-lg-auto d-grid d-sm-flex gap-2">
                    <Button type="button" label="Limpar" icon="pi pi-filter-slash" severity="secondary" outlined :loading="loading" @click="clearFilters" />
                    <Button type="submit" label="Filtrar" icon="pi pi-search" :loading="loading" />
                </div>
            </form>
        </template></Card>

        <Card><template #content>
            <TableSkeleton v-show="loading" :rows="itemsPerPage" :columns="skeletonColumns" class="mt-2 categories-table-skeleton" />
            <DataTable
                v-show="!loading && categories.length"
                :value="categories"
                :totalRecords="pagination.total"
                :first="(currentPage - 1) * itemsPerPage"
                :rows="itemsPerPage"
                :rowsPerPageOptions="[5, 7, 10, 20]"
                @page="onPage"
                lazy paginator scrollable stripedRows class="mt-2"
                currentPageReportTemplate="Exibindo {first} a {last} de {totalRecords} categorias"
            >
                <Column field="name" header="Categoria" style="min-width: 190px"><template #body="{ data }"><strong>{{ data.name }}</strong></template></Column>
                <Column field="slug" header="Slug" style="min-width: 170px"><template #body="{ data }"><code>{{ data.slug }}</code></template></Column>
                <Column field="description" header="Descrição" style="min-width: 260px; max-width: 300px"><template #body="{ data }"><span class="text-muted text-truncate d-block" v-tooltip.top="data.description || null">{{ data.description || '-' }}</span></template></Column>
                <Column field="status" header="Status" style="width: 100px"><template #body="{ data }"><Tag :value="data.status === 'active' ? 'Ativa' : 'Inativa'" :severity="data.status === 'active' ? 'success' : 'secondary'" /></template></Column>
                <Column style="width: 110px"><template #header><span class="w-100 text-center fw-semibold">Ações</span></template><template #body="{ data }">
                    <div class="d-flex justify-content-center gap-1">
                        <Button icon="pi pi-pen-to-square" variant="text" rounded :disabled="!canUpdate" @click="openDialog('form', data)" />
                        <Button icon="pi pi-trash" variant="text" severity="danger" rounded :disabled="!canDelete" v-tooltip.left="'Excluir categoria'" @click="openDialog('delete', data)" />
                    </div>
                </template></Column>
            </DataTable>
            <EmptyData v-if="!loading && !categories.length" @clean-filters="clearFilters" :show-btn-clean-filters="true" />
        </template></Card>

        <CategoryFormDialog v-model="dialogs.form" :category="category" @saved="fetchAll(currentPage)" />
        <CategoryDeleteDialog v-model="dialogs.delete" :category="category" @deleted="fetchAll(currentPage)" />
    </section>
</template>

<style scoped>
:deep(.categories-table-skeleton) {
    --table-skeleton-columns: minmax(190px, 1fr) minmax(170px, 0.9fr) minmax(260px, 1.4fr) 100px 110px;
    --table-skeleton-min-width: 890px;
}
</style>
