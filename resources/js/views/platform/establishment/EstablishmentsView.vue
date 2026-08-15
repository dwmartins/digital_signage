<script setup>
import EstablishmentDeleteDialog from '@/components/dialog/establishment/EstablishmentDeleteDialog.vue';
import EstablishmentFormDialog from '@/components/dialog/establishment/EstablishmentFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { useQueryFilters } from '@/composables/useQueryFilters';
import { showAlert } from '@/helpers/alert';
import establishmentService from '@/services/establishment.service';
import localityService from '@/services/locality.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const authStore = useAuthStore();
const establishments = ref([]);
const establishment = ref(null);
const states = ref([]);
const cities = ref([]);
const neighborhoods = ref([]);
const loading = ref(false);
const pagination = ref({});
const currentPage = ref(1);
const itemsPerPage = ref(7);

const dialogs = reactive({ form: false, delete: false, filters: false });

const filters = reactive({
    global: { value: null, type: 'string' },
    status: { value: null, type: 'string' },
    state_id: { value: null, type: 'number' },
    city_id: { value: null, type: 'number' },
    neighborhood_id: { value: null, type: 'number' },
});

const statusOptions = [
    { label: 'Ativo', value: 'active' },
    { label: 'Inativo', value: 'inactive' },
    { label: 'Bloqueado', value: 'blocked' },
];

const breadcrumbItens = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Estabelecimentos', to: '/platform/estabelecimentos' },
];

const skeletonColumns = [
    { headerWidth: '20px', bodyWidth: '30px' },
    { headerWidth: '100px', bodyWidth: '180px' },
    { headerWidth: '80px', bodyWidth: '150px' },
    { headerWidth: '80px', bodyWidth: '180px' },
    { headerWidth: '70px', bodyWidth: '130px' },
    { headerWidth: '50px', bodyWidth: '70px', height: '22px', borderRadius: '999px' },
    { headerWidth: '50px', bodyWidth: '72px', height: '28px', borderRadius: '999px', align: 'center' },
];

const canCreate = computed(() => authStore.hasPermission('establishments.create'));
const canUpdate = computed(() => authStore.hasPermission('establishments.update'));
const canDelete = computed(() => authStore.hasPermission('establishments.delete'));
const availableCities = computed(() => cities.value.filter(
    city => !filters.state_id.value || city.state_id === filters.state_id.value,
));
const availableNeighborhoods = computed(() => neighborhoods.value.filter(
    neighborhood => !filters.city_id.value || neighborhood.city_id === filters.city_id.value,
));

const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(filters, currentPage);

const fetchAll = async page => {
    syncToRoute(page);
    try {
        loading.value = true;
        const response = await establishmentService.index(page, itemsPerPage.value, buildApiFilters());
        establishments.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
};

const fetchLocalities = async () => {
    const response = await localityService.options();
    states.value = response.states ?? [];
    cities.value = response.cities ?? [];
    neighborhoods.value = response.neighborhoods ?? [];
};

const openDialog = (type, data = null) => {
    const allowed = type === 'delete' ? canDelete.value : (data ? canUpdate.value : canCreate.value);
    if (!allowed) return showAlert('warning', 'Você não possui permissão para realizar esta ação.');
    establishment.value = data ? { ...data } : null;
    dialogs[type] = true;
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

const statusLabel = status => ({ active: 'Ativo', inactive: 'Inativo', blocked: 'Bloqueado' })[status] ?? '-';
const statusSeverity = status => ({ active: 'success', inactive: 'warn', blocked: 'danger' })[status] ?? 'secondary';
const location = data => [data.city?.name, data.city?.state?.code].filter(Boolean).join(' / ');

watch(() => filters.state_id.value, (stateId, previousStateId) => {
    if (previousStateId && stateId !== previousStateId) {
        filters.city_id.value = null;
        filters.neighborhood_id.value = null;
    }
});

watch(() => filters.city_id.value, (cityId, previousCityId) => {
    if (previousCityId && cityId !== previousCityId) {
        filters.neighborhood_id.value = null;
    }
});

onMounted(async () => {
    applyFromRoute();
    await fetchLocalities();
    fetchAll(currentPage.value);
});
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItens" />
        <div class="d-flex justify-content-end mb-3">
            <Button label="Novo estabelecimento" icon="pi pi-plus" size="small" :disabled="!canCreate" @click="openDialog('form')" />
        </div>

        <Card class="mb-3 d-none d-md-block">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                    <div class="col-lg-2">
                        <div class="field">
                            <label>Buscar</label>
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Nome, CNPJ ou responsável"
                                    fluid
                                />
                            </IconField>
                        </div>
                    </div>
                    <div class="col-lg-2">
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
                    <div class="col-lg-2">
                        <div class="field">
                            <label>Estado</label>
                            <Select
                                v-model="filters.state_id.value"
                                :options="states"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Todos"
                                showClear
                                filter
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="field">
                            <label>Cidade</label>
                            <Select
                                v-model="filters.city_id.value"
                                :options="availableCities"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Todas"
                                :disabled="!filters.state_id.value"
                                showClear
                                filter
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="field">
                            <label>Bairro</label>
                            <Select
                                v-model="filters.neighborhood_id.value"
                                :options="availableNeighborhoods"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Todos"
                                :disabled="!filters.city_id.value"
                                showClear
                                filter
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <Button
                            type="button"
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

        <div class="d-flex justify-content-end mb-3 d-md-none">
            <Button
                label="Filtros"
                icon="pi pi-filter"
                outlined
                @click="dialogs.filters = true"
            />
        </div>

        <Dialog
            v-model:visible="dialogs.filters"
            modal
            header="Filtrar estabelecimentos"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form id="establishmentFilters" class="row g-3" @submit.prevent="applyMobileFilters">
                <div class="col-12">
                    <div class="field">
                        <label>Buscar</label>
                        <InputText v-model="filters.global.value" fluid />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Status</label>
                        <Select
                            v-model="filters.status.value"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            showClear
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Estado</label>
                        <Select
                            v-model="filters.state_id.value"
                            :options="states"
                            optionLabel="name"
                            optionValue="id"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Cidade</label>
                        <Select
                            v-model="filters.city_id.value"
                            :options="availableCities"
                            optionLabel="name"
                            optionValue="id"
                            :disabled="!filters.state_id.value"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Bairro</label>
                        <Select
                            v-model="filters.neighborhood_id.value"
                            :options="availableNeighborhoods"
                            optionLabel="name"
                            optionValue="id"
                            :disabled="!filters.city_id.value"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Limpar" severity="secondary" outlined @click="clearFilters" />
                <Button label="Aplicar" type="submit" form="establishmentFilters" />
            </template>
        </Dialog>

        <Card><template #content>
            <TableSkeleton v-show="loading" :rows="itemsPerPage" :columns="skeletonColumns" class="mt-2 establishments-table-skeleton" />
            <DataTable
                v-show="!loading && establishments.length"
                :value="establishments"
                :totalRecords="pagination.total"
                :first="(currentPage - 1) * itemsPerPage"
                :rows="itemsPerPage"
                :rowsPerPageOptions="[5, 7, 10, 20]"
                @page="onPage"
                lazy paginator scrollable stripedRows class="mt-2"
                currentPageReportTemplate="Exibindo {first} a {last} de {totalRecords} estabelecimentos"
            >
                <Column field="id" header="ID" style="width: 80px">
                    <template #body="{ data }">
                        #{{ data.id }}
                    </template>
                </Column>
                <Column field="name" header="Estabelecimento" style="min-width: 220px">
                    <template #body="{ data }">
                        <div class="d-flex flex-column">
                            <strong>{{ data.name }}</strong>
                            <small class="text-muted">{{ data.legal_name }}</small>
                        </div>
                    </template>
                </Column>
                <Column field="document" header="CNPJ" style="min-width: 160px" />
                <Column header="Contato" style="min-width: 210px">
                    <template #body="{ data }">
                        <div class="d-flex flex-column">
                            <span>{{ data.contact_name || '-' }}</span>
                            <small class="text-muted">{{ data.phone || data.email }}</small>
                        </div>
                    </template>
                </Column>
                <Column header="Localização" style="min-width: 160px">
                    <template #body="{ data }">
                        {{ location(data) }}
                    </template>
                </Column>
                <Column field="status" header="Status" style="width: 110px">
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
                            <Button icon="pi pi-trash" variant="text" severity="danger" rounded :disabled="!canDelete" v-tooltip.left="'Excluir estabelecimento'" @click="openDialog('delete', data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
            <EmptyData v-if="!loading && !establishments.length" @clean-filters="clearFilters" :show-btn-clean-filters="true" />
        </template></Card>

        <EstablishmentFormDialog v-model="dialogs.form" :establishment="establishment" @saved="fetchAll(currentPage)" />
        <EstablishmentDeleteDialog v-model="dialogs.delete" :establishment="establishment" @deleted="fetchAll(currentPage)" />
    </section>
</template>

<style scoped>
:deep(.establishments-table-skeleton) {
    --table-skeleton-columns: 80px minmax(220px, 1.2fr) 160px minmax(210px, 1fr) 160px 110px 110px;
    --table-skeleton-min-width: 1100px;
}
</style>
