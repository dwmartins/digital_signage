<script setup>
import LocalityDeleteDialog from '@/components/dialog/locality/LocalityDeleteDialog.vue';
import LocalityFormDialog from '@/components/dialog/locality/LocalityFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { showAlert } from '@/helpers/alert';
import localityService from '@/services/locality.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const authStore = useAuthStore();
const localities = ref([]);
const locality = ref(null);
const states = ref([]);
const cities = ref([]);
const activeType = ref('states');
const loading = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(7);
const pagination = ref({});

const dialogs = reactive({
    form: false,
    delete: false,
    filters: false,
});

const filters = reactive({
    global: null,
    status: null,
    state_id: null,
    city_id: null,
});

const typeOptions = [
    { label: 'Estados', value: 'states', icon: 'pi pi-map' },
    { label: 'Cidades', value: 'cities', icon: 'pi pi-building' },
    { label: 'Bairros', value: 'neighborhoods', icon: 'pi pi-map-marker' },
];

const statusOptions = [
    { label: 'Ativa', value: 'active' },
    { label: 'Inativa', value: 'inactive' },
];

const breadcrumbItems = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Configurações' },
    { label: 'Localidades' },
];

const skeletonColumns = [
    { bodyWidth: '190px' },
    { bodyWidth: '150px' },
    { bodyWidth: '80px', height: '22px', borderRadius: '999px' },
    { bodyWidth: '100px', align: 'center' },
];

const canCreate = computed(() => authStore.hasPermission('localities.create'));
const canUpdate = computed(() => authStore.hasPermission('localities.update'));
const canDelete = computed(() => authStore.hasPermission('localities.delete'));
const activeTypeData = computed(() => typeOptions.find(type => type.value === activeType.value));
const availableCities = computed(() => cities.value.filter(
    city => !filters.state_id || city.state_id === filters.state_id,
));

const fetchOptions = async () => {
    const response = await localityService.options({ include_inactive: true });
    states.value = response.states ?? [];
    cities.value = response.cities ?? [];
};

const fetchAll = async (page = 1) => {
    try {
        loading.value = true;
        const response = await localityService.index(
            activeType.value,
            page,
            itemsPerPage.value,
            Object.fromEntries(
                Object.entries(filters).filter(([, value]) => value !== null && value !== ''),
            ),
        );
        localities.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    Object.assign(filters, {
        global: null,
        status: null,
        state_id: null,
        city_id: null,
    });
    dialogs.filters = false;
    fetchAll(1);
};

const applyFilters = () => {
    dialogs.filters = false;
    fetchAll(1);
};

const openDialog = (type, data = null) => {
    const allowed = type === 'delete'
        ? canDelete.value
        : data
            ? canUpdate.value
            : canCreate.value;

    if (!allowed) {
        return showAlert('warning', 'Você não possui permissão para realizar esta ação.');
    }

    locality.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const refresh = async () => {
    await fetchOptions();
    fetchAll(currentPage.value);
};

const onPage = event => {
    itemsPerPage.value = event.rows;
    fetchAll(event.page + 1);
};

watch(activeType, () => {
    Object.assign(filters, {
        global: null,
        status: null,
        state_id: null,
        city_id: null,
    });
    fetchAll(1);
});

watch(() => filters.state_id, (stateId, previousStateId) => {
    if (previousStateId && stateId !== previousStateId) {
        filters.city_id = null;
    }
});

onMounted(async () => {
    try {
        await fetchOptions();
        fetchAll();
    } catch (error) {
        showAlert('error', error.response?.data);
    }
});
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItems" />

        <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-3">
            <div>
                <h2 class="mb-1">Localidades</h2>
                <p class="text-muted mb-0">
                    Gerencie estados, cidades e bairros utilizados nos endereços da plataforma.
                </p>
            </div>
            <Button
                :label="`Adicionar ${activeTypeData.label.slice(0, -1).toLowerCase()}`"
                icon="pi pi-plus"
                size="small"
                :disabled="!canCreate"
                @click="openDialog('form')"
            />
        </div>

        <div class="row g-3 mb-3">
            <div v-for="type in typeOptions" :key="type.value" class="col-4">
                <button
                    type="button"
                    class="d-flex flex-column flex-sm-row align-items-center justify-content-center gap-2 w-100 h-100 p-3 border rounded-3 locality-type-button"
                    :class="{ active: activeType === type.value }"
                    @click="activeType = type.value"
                >
                    <i :class="type.icon"></i>
                    <strong>{{ type.label }}</strong>
                </button>
            </div>
        </div>

        <Card class="mb-3 d-none d-md-block">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Buscar</label>
                            <InputText v-model="filters.global" placeholder="Nome da localidade" fluid />
                        </div>
                    </div>
                    <div v-if="activeType !== 'states'" class="col-lg-3">
                        <div class="field">
                            <label>Estado</label>
                            <Select
                                v-model="filters.state_id"
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
                    <div v-if="activeType === 'neighborhoods'" class="col-lg-3">
                        <div class="field">
                            <label>Cidade</label>
                            <Select
                                v-model="filters.city_id"
                                :options="availableCities"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Todas"
                                :disabled="!filters.state_id"
                                showClear
                                filter
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="field">
                            <label>Status</label>
                            <Select
                                v-model="filters.status"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todos"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-12 col-lg-auto ms-lg-auto d-flex gap-2">
                        <Button
                            type="button"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            outlined
                            @click="clearFilters"
                        />
                        <Button label="Filtrar" icon="pi pi-search" type="submit" />
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
            header="Filtrar localidades"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form id="localityFilters" class="row g-3" @submit.prevent="applyFilters">
                <div class="col-12">
                    <div class="field">
                        <label>Buscar</label>
                        <InputText v-model="filters.global" placeholder="Nome da localidade" fluid />
                    </div>
                </div>
                <div v-if="activeType !== 'states'" class="col-12">
                    <div class="field">
                        <label>Estado</label>
                        <Select
                            v-model="filters.state_id"
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
                <div v-if="activeType === 'neighborhoods'" class="col-12">
                    <div class="field">
                        <label>Cidade</label>
                        <Select
                            v-model="filters.city_id"
                            :options="availableCities"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Todas"
                            :disabled="!filters.state_id"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Status</label>
                        <Select
                            v-model="filters.status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todos"
                            showClear
                            fluid
                        />
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Limpar" severity="secondary" outlined @click="clearFilters" />
                <Button label="Aplicar" type="submit" form="localityFilters" />
            </template>
        </Dialog>

        <Card>
            <template #content>
                <TableSkeleton
                    v-show="loading"
                    :rows="itemsPerPage"
                    :columns="skeletonColumns"
                    class="localities-table-skeleton"
                />
                <DataTable
                    v-show="!loading && localities.length"
                    :value="localities"
                    :totalRecords="pagination.total"
                    :first="(currentPage - 1) * itemsPerPage"
                    :rows="itemsPerPage"
                    :rowsPerPageOptions="[5, 7, 10, 20]"
                    lazy
                    paginator
                    scrollable
                    stripedRows
                    @page="onPage"
                >
                    <Column field="name" :header="activeTypeData.label.slice(0, -1)" style="min-width: 210px">
                        <template #body="{ data }">
                            <div class="d-flex align-items-center gap-2">
                                <i :class="activeTypeData.icon" class="text-primary"></i>
                                <strong>{{ data.name }}</strong>
                            </div>
                        </template>
                    </Column>
                    <Column v-if="activeType === 'states'" field="code" header="UF" style="width: 100px">
                        <template #body="{ data }">
                            <Tag :value="data.code" severity="info" />
                        </template>
                    </Column>
                    <Column v-if="activeType === 'cities'" header="Estado" style="min-width: 180px">
                        <template #body="{ data }">
                            {{ data.state?.name }} ({{ data.state?.code }})
                        </template>
                    </Column>
                    <Column v-if="activeType === 'neighborhoods'" header="Cidade / Estado" style="min-width: 220px">
                        <template #body="{ data }">
                            {{ data.city?.name }} / {{ data.city?.state?.code }}
                        </template>
                    </Column>
                    <Column field="status" header="Status" style="width: 110px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status === 'active' ? 'Ativa' : 'Inativa'"
                                :severity="data.status === 'active' ? 'success' : 'secondary'"
                            />
                        </template>
                    </Column>
                    <Column style="width: 120px">
                        <template #header>
                            <span class="w-100 text-center">Ações</span>
                        </template>
                        <template #body="{ data }">
                            <div class="d-flex justify-content-center gap-1">
                                <Button
                                    icon="pi pi-pencil"
                                    text
                                    rounded
                                    :disabled="!canUpdate"
                                    v-tooltip.top="'Editar localidade'"
                                    @click="openDialog('form', data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    severity="danger"
                                    :disabled="!canDelete"
                                    v-tooltip.top="'Excluir localidade'"
                                    @click="openDialog('delete', data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <EmptyData
                    v-if="!loading && !localities.length"
                    :show-btn-clean-filters="true"
                    @clean-filters="clearFilters"
                />
            </template>
        </Card>

        <LocalityFormDialog
            v-model="dialogs.form"
            :type="activeType"
            :locality="locality"
            :states="states"
            :cities="cities"
            @saved="refresh"
        />
        <LocalityDeleteDialog
            v-model="dialogs.delete"
            :type="activeType"
            :locality="locality"
            @deleted="refresh"
        />
    </section>
</template>

<style scoped>
.locality-type-button {
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition: border-color 0.2s, background 0.2s;
}

.locality-type-button:hover,
.locality-type-button.active {
    border-color: var(--p-primary-color) !important;
    color: var(--p-primary-color);
    background: var(--p-primary-50);
}

:deep(.localities-table-skeleton) {
    --table-skeleton-columns: minmax(210px, 1fr) 180px 110px 120px;
    --table-skeleton-min-width: 620px;
}
</style>
