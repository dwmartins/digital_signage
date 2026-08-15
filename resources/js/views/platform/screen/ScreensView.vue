<script setup>
import ScreenDeleteDialog from '@/components/dialog/screen/ScreenDeleteDialog.vue';
import ScreenFormDialog from '@/components/dialog/screen/ScreenFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { useQueryFilters } from '@/composables/useQueryFilters';
import { showAlert } from '@/helpers/alert';
import screenService from '@/services/screen.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref } from 'vue';

const authStore = useAuthStore();
const screens = ref([]);
const screen = ref(null);
const establishments = ref([]);
const displayPoints = ref([]);
const loading = ref(false);
const pagination = ref({});
const currentPage = ref(1);
const itemsPerPage = ref(7);

const dialogs = reactive({ form: false, delete: false, filters: false });

const filters = reactive({
    global: { value: null, type: 'string' },
    establishment_id: { value: null, type: 'number' },
    display_point_id: { value: null, type: 'number' },
    orientation: { value: null, type: 'string' },
    status: { value: null, type: 'string' },
});

const statusOptions = [
    { label: 'Ativa', value: 'active' },
    { label: 'Manutenção', value: 'maintenance' },
    { label: 'Bloqueada', value: 'blocked' },
    { label: 'Estoque', value: 'stock' },
];

const orientationOptions = [
    { label: 'Horizontal', value: 'landscape' },
    { label: 'Vertical', value: 'portrait' },
];

const breadcrumbItens = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Telas', to: '/platform/telas' },
];

const skeletonColumns = [
    { headerWidth: '80px', bodyWidth: '180px' },
    { headerWidth: '80px', bodyWidth: '140px' },
    { headerWidth: '80px', bodyWidth: '130px' },
    { headerWidth: '60px', bodyWidth: '75px', height: '22px', borderRadius: '999px' },
    { headerWidth: '50px', bodyWidth: '72px', height: '28px', borderRadius: '999px', align: 'center' },
];

const canCreate = computed(() => authStore.hasPermission('screens.create'));
const canUpdate = computed(() => authStore.hasPermission('screens.update'));
const canDelete = computed(() => authStore.hasPermission('screens.delete'));

const activeFiltersCount = computed(() => Object.values(filters)
    .filter(filter => filter.value !== null && filter.value !== undefined && filter.value !== '')
    .length);

const filtersButtonLabel = computed(() => activeFiltersCount.value ? `Filtros (${activeFiltersCount.value})` : 'Filtros');

const filteredDisplayPoints = computed(() => {
    if (!filters.establishment_id.value) return displayPoints.value;
    return displayPoints.value.filter(item => item.establishment_id === filters.establishment_id.value);
});

const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(filters, currentPage);

const fetchAll = async page => {
    syncToRoute(page);
    try {
        loading.value = true;
        const response = await screenService.index(page, itemsPerPage.value, buildApiFilters());
        screens.value = response.data ?? [];
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
    screen.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const fetchFilterOptions = async () => {
    try {
        const response = await screenService.filterOptions();
        establishments.value = response.establishments ?? [];
        displayPoints.value = response.display_points ?? [];
    } catch (error) {
        showAlert('error', error.response?.data);
    }
};

const onEstablishmentChange = () => {
    const selectedPoint = displayPoints.value.find(item => item.id === filters.display_point_id.value);
    if (selectedPoint && selectedPoint.establishment_id !== filters.establishment_id.value) {
        filters.display_point_id.value = null;
    }
};

const clearFilters = () => {
    Object.values(filters).forEach(filter => filter.value = null);
    dialogs.filters = false;
    fetchAll(1);
};

const applyMobileFilters = () => {
    dialogs.filters = false;
    fetchAll(1);
};

const onPage = event => {
    itemsPerPage.value = event.rows;
    fetchAll(event.page + 1);
};

const statusLabel = status => statusOptions.find(option => option.value === status)?.label ?? '-';
const statusSeverity = status => ({ active: 'success', maintenance: 'warn', blocked: 'danger', stock: 'contrast' })[status] ?? 'secondary';
const orientationLabel = orientation => orientationOptions.find(option => option.value === orientation)?.label ?? '-';

onMounted(() => {
    applyFromRoute();
    fetchFilterOptions();
    fetchAll(currentPage.value);
});
</script>

<template>
    <section class="container">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
            <Breadcrumb :items="breadcrumbItens" />
            <div class="d-flex align-items-center gap-2 ms-auto">
                <Button
                    class="d-inline-flex d-md-none"
                    :label="filtersButtonLabel"
                    icon="pi pi-filter"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="dialogs.filters = true"
                />
                <Button label="Nova tela" icon="pi pi-plus" size="small" :disabled="!canCreate" @click="openDialog('form')" />
            </div>
        </div>

        <Card class="mb-3 d-none d-md-block">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Buscar</label>
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Nome, código, marca ou modelo"
                                    fluid
                                />
                            </IconField>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="field">
                            <label>Estabelecimento</label>
                            <Select
                                v-model="filters.establishment_id.value"
                                :options="establishments"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Todos"
                                showClear
                                filter
                                fluid
                                @change="onEstablishmentChange"
                            >
                                <template #option="{ option }">
                                    <div class="d-flex flex-column">
                                        <span>#{{ option.id }} - {{ option.name }}</span>
                                        <small class="text-muted">{{ option.city?.name }} / {{ option.city?.state?.code }}</small>
                                    </div>
                                </template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="field">
                            <label>Ponto de exibição</label>
                            <Select
                                v-model="filters.display_point_id.value"
                                :options="filteredDisplayPoints"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Todos"
                                showClear
                                filter
                                fluid
                            >
                                <template #option="{ option }">#{{ option.id }} - {{ option.name }}</template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="field">
                            <label>Status</label>
                            <Select
                                v-model="filters.status.value"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todos"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="field">
                            <label>Orientação</label>
                            <Select
                                v-model="filters.orientation.value"
                                :options="orientationOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todas"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-12 col-lg-auto ms-lg-auto d-grid d-sm-flex gap-2">
                        <Button
                            type="button"
                            label="Limpar"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            outlined
                            :loading="loading"
                            @click="clearFilters"
                        />
                        <Button
                            type="submit"
                            label="Filtrar"
                            icon="pi pi-search"
                            :loading="loading"
                        />
                    </div>
                </form>
            </template>
        </Card>

        <Dialog
            v-model:visible="dialogs.filters"
            modal
            header="Filtrar telas"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form id="screenMobileFiltersForm" class="row g-3" @submit.prevent="applyMobileFilters">
                <div class="col-12">
                    <div class="field">
                        <label>Buscar</label>
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters.global.value" placeholder="Nome, código, marca ou modelo" fluid />
                        </IconField>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Estabelecimento</label>
                        <Select v-model="filters.establishment_id.value" :options="establishments" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid @change="onEstablishmentChange">
                            <template #option="{ option }">
                                <div class="d-flex flex-column">
                                    <span>#{{ option.id }} - {{ option.name }}</span>
                                    <small class="text-muted">{{ option.city?.name }} / {{ option.city?.state?.code }}</small>
                                </div>
                            </template>
                        </Select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Ponto de exibição</label>
                        <Select v-model="filters.display_point_id.value" :options="filteredDisplayPoints" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid>
                            <template #option="{ option }">#{{ option.id }} - {{ option.name }}</template>
                        </Select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Status</label>
                        <Select v-model="filters.status.value" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos" showClear fluid />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Orientação</label>
                        <Select v-model="filters.orientation.value" :options="orientationOptions" optionLabel="label" optionValue="value" placeholder="Todas" showClear fluid />
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Limpar" icon="pi pi-filter-slash" severity="secondary" outlined :loading="loading" @click="clearFilters" />
                <Button label="Aplicar filtros" icon="pi pi-search" :loading="loading" type="submit" form="screenMobileFiltersForm" />
            </template>
        </Dialog>

        <Card><template #content>
            <TableSkeleton v-show="loading" :rows="itemsPerPage" :columns="skeletonColumns" class="mt-2 screens-table-skeleton" />
            <DataTable
                v-show="!loading && screens.length"
                :value="screens"
                :totalRecords="pagination.total"
                :first="(currentPage - 1) * itemsPerPage"
                :rows="itemsPerPage"
                :rowsPerPageOptions="[5, 7, 10, 20]"
                @page="onPage"
                lazy paginator scrollable stripedRows class="mt-2"
                currentPageReportTemplate="Exibindo {first} a {last} de {totalRecords} telas"
            >
                <Column field="name" header="Tela" style="min-width: 210px">
                    <template #body="{ data }">
                        <div class="d-flex flex-column">
                            <strong>{{ data.name }}</strong>
                            <small class="text-muted">{{ data.code }}</small>
                        </div>
                    </template>
                </Column>
                <Column header="Ponto de exibição" style="min-width: 190px">
                    <template #body="{ data }">
                        {{ data.display_point?.name || 'Não vinculada' }}
                    </template>
                </Column>
                <Column header="Configuração" style="min-width: 155px">
                    <template #body="{ data }">
                        <div class="d-flex flex-column">
                            <span>{{ orientationLabel(data.orientation) }}</span>
                            <small class="text-muted">{{ data.resolution_width }} × {{ data.resolution_height }}</small>
                        </div>
                    </template>
                </Column>
                <Column field="status" header="Status" style="width: 120px">
                    <template #body="{ data }">
                        <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                    </template>
                </Column>
                <Column style="width: 110px">
                    <template #header>
                        <span class="w-100 text-center fw-semibold">Ações</span>
                    </template>
                    <template #body="{ data }">
                        <div class="d-flex justify-content-center gap-1">
                            <Button icon="pi pi-pen-to-square" variant="text" rounded :disabled="!canUpdate" @click="openDialog('form', data)" />
                            <Button icon="pi pi-trash" variant="text" severity="danger" rounded :disabled="!canDelete" v-tooltip.left="'Excluir tela'" @click="openDialog('delete', data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
            <EmptyData v-if="!loading && !screens.length" @clean-filters="clearFilters" :show-btn-clean-filters="true" />
        </template></Card>

        <ScreenFormDialog v-model="dialogs.form" :screen="screen" @saved="fetchAll(currentPage)" />
        <ScreenDeleteDialog v-model="dialogs.delete" :screen="screen" @deleted="fetchAll(currentPage)" />
    </section>
</template>

<style scoped>
:deep(.screens-table-skeleton) {
    --table-skeleton-columns: minmax(210px, 1.2fr) 190px 155px 155px 120px 110px;
    --table-skeleton-min-width: 940px;
}
</style>
