<script setup>
import PlayerDeleteDialog from '@/components/dialog/player/PlayerDeleteDialog.vue';
import PlayerFormDialog from '@/components/dialog/player/PlayerFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { showAlert } from '@/helpers/alert';
import playerService from '@/services/player.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref } from 'vue';

const authStore = useAuthStore();
const players = ref([]);
const player = ref(null);
const establishments = ref([]);
const displayPoints = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(7);
const pagination = ref({});

const dialogs = reactive({ form: false, delete: false, filters: false });
const filters = reactive({ global: '', establishment_id: null, display_point_id: null, status: null });

const statusOptions = [
    { label: 'Ativo', value: 'active' },
    { label: 'Manutenção', value: 'maintenance' },
    { label: 'Bloqueado', value: 'blocked' },
    { label: 'Estoque', value: 'stock' },
];

const breadcrumbItems = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Players (PC)' },
];

const skeletonColumns = [
    { headerWidth: '60px', bodyWidth: '175px' },
    { headerWidth: '75px', bodyWidth: '120px' },
    { headerWidth: '85px', bodyWidth: '165px' },
    { headerWidth: '65px', bodyWidth: '145px' },
    { headerWidth: '50px', bodyWidth: '75px', height: '22px', borderRadius: '999px' },
    { headerWidth: '50px', bodyWidth: '72px', height: '28px', borderRadius: '999px', align: 'center' },
];

const canCreate = computed(() => authStore.hasPermission('players.create'));
const canUpdate = computed(() => authStore.hasPermission('players.update'));
const canDelete = computed(() => authStore.hasPermission('players.delete'));

const activeFiltersCount = computed(() => Object.values(filters)
    .filter(value => value !== null && value !== undefined && value !== '')
    .length);

const filtersButtonLabel = computed(() => activeFiltersCount.value ? `Filtros (${activeFiltersCount.value})` : 'Filtros');

const filteredDisplayPoints = computed(() => {
    if (!filters.establishment_id) return displayPoints.value;
    return displayPoints.value.filter(item => item.establishment_id === filters.establishment_id);
});

const fetchAll = async (page = currentPage.value) => {
    try {
        loading.value = true;
        const response = await playerService.index(page, itemsPerPage.value, filters);
        players.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
};

const openDialog = (type, data = null) => {
    player.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const fetchFilterOptions = async () => {
    try {
        const response = await playerService.filterOptions();
        establishments.value = response.establishments ?? [];
        displayPoints.value = response.display_points ?? [];
    } catch (error) {
        showAlert('error', error.response?.data);
    }
};

const onEstablishmentChange = () => {
    const selectedPoint = displayPoints.value.find(item => item.id === filters.display_point_id);
    if (selectedPoint && selectedPoint.establishment_id !== filters.establishment_id) {
        filters.display_point_id = null;
    }
};

const clearFilters = () => {
    filters.global = '';
    filters.establishment_id = null;
    filters.display_point_id = null;
    filters.status = null;
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

onMounted(() => {
    fetchFilterOptions();
    fetchAll();
});
</script>

<template>
    <section class="container">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
            <Breadcrumb :items="breadcrumbItems" />
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
                <Button 
                    label="Novo player (PC)" 
                    icon="pi pi-plus" 
                    :disabled="!canCreate" 
                    @click="openDialog('form')" 
                    size="small"
                />
            </div>
        </div>

        <Card class="mb-3 d-none d-md-block">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                    <div class="col-lg-5">
                        <div class="field">
                            <label>Buscar</label>
                            <InputText v-model="filters.global" placeholder="Nome, código ou hostname" fluid />
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="field">
                            <label>Estabelecimento</label>
                            <Select v-model="filters.establishment_id" :options="establishments" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid @change="onEstablishmentChange">
                                <template #option="{ option }">
                                    <div class="d-flex flex-column">
                                        <span>#{{ option.id }} - {{ option.name }}</span>
                                        <small class="text-muted">{{ option.city }} / {{ option.state }}</small>
                                    </div>
                                </template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="field">
                            <label>Ponto de exibição</label>
                            <Select v-model="filters.display_point_id" :options="filteredDisplayPoints" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid>
                                <template #option="{ option }">#{{ option.id }} - {{ option.name }}</template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-md-5 col-lg-3">
                        <div class="field">
                            <label>Status</label>
                            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos" showClear fluid />
                        </div>
                    </div>
                    <div class="col-12 col-lg-auto ms-lg-auto d-flex gap-2">
                        <Button label="Limpar" icon="pi pi-filter-slash" severity="secondary" outlined @click="clearFilters" />
                        <Button label="Filtrar" icon="pi pi-search" type="submit" />
                    </div>
                </form>
            </template>
        </Card>

        <Dialog
            v-model:visible="dialogs.filters"
            modal
            header="Filtrar players (PC)"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form id="playerMobileFiltersForm" class="row g-3" @submit.prevent="applyMobileFilters">
                <div class="col-12">
                    <div class="field">
                        <label>Buscar</label>
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters.global" placeholder="Nome, código ou hostname" fluid />
                        </IconField>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Estabelecimento</label>
                        <Select v-model="filters.establishment_id" :options="establishments" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid @change="onEstablishmentChange">
                            <template #option="{ option }">
                                <div class="d-flex flex-column">
                                    <span>#{{ option.id }} - {{ option.name }}</span>
                                    <small class="text-muted">{{ option.city }} / {{ option.state }}</small>
                                </div>
                            </template>
                        </Select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Ponto de exibição</label>
                        <Select v-model="filters.display_point_id" :options="filteredDisplayPoints" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid>
                            <template #option="{ option }">#{{ option.id }} - {{ option.name }}</template>
                        </Select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Status</label>
                        <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos" showClear fluid />
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Limpar" icon="pi pi-filter-slash" severity="secondary" outlined :loading="loading" @click="clearFilters" />
                <Button label="Aplicar filtros" icon="pi pi-search" :loading="loading" type="submit" form="playerMobileFiltersForm" />
            </template>
        </Dialog>

        <Card>
            <template #content>
                <TableSkeleton
                    v-show="loading"
                    :rows="itemsPerPage"
                    :columns="skeletonColumns"
                    class="mt-2 players-table-skeleton"
                />
                <DataTable
                    v-show="!loading && players.length"
                    :value="players"
                    :totalRecords="pagination.total"
                    :first="(currentPage - 1) * itemsPerPage"
                    :rows="itemsPerPage"
                    :rowsPerPageOptions="[5, 7, 10, 20]"
                    lazy paginator scrollable stripedRows
                    @page="onPage"
                >
                    <Column field="name" header="Player (PC)" style="min-width: 210px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column">
                                <strong>{{ data.name }}</strong>
                                <small class="text-muted">{{ data.code }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column field="hostname" header="Hostname" style="min-width: 160px">
                        <template #body="{ data }">{{ data.hostname || '-' }}</template>
                    </Column>
                    <Column header="Equipamento" style="min-width: 190px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column">
                                <span>{{ data.brand || '-' }}{{ data.model ? ` - ${data.model}` : '' }}</span>
                                <small class="text-muted">{{ data.operating_system || '-' }} · {{ data.architecture || '-' }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Uso atual" style="min-width: 170px">
                        <template #body="{ data }">
                            {{ data.display_point?.name || 'Disponível para vínculo' }}
                        </template>
                    </Column>
                    <Column header="Status" style="width: 120px">
                        <template #body="{ data }">
                            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column style="width: 110px">
                        <template #header><span class="w-100 text-center">Ações</span></template>
                        <template #body="{ data }">
                            <div class="d-flex justify-content-center gap-1">
                                <Button icon="pi pi-pencil" text rounded :disabled="!canUpdate" @click="openDialog('form', data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" :disabled="!canDelete" @click="openDialog('delete', data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <EmptyData v-if="!loading && !players.length" />
            </template>
        </Card>

        <PlayerFormDialog v-model="dialogs.form" :player="player" @saved="fetchAll()" />
        <PlayerDeleteDialog v-model="dialogs.delete" :player="player" @deleted="fetchAll()" />
    </section>
</template>

<style scoped>
:deep(.players-table-skeleton) {
    --table-skeleton-columns: minmax(210px, 1.2fr) 160px minmax(190px, 1fr) 170px 120px 110px;
    --table-skeleton-min-width: 960px;
}
</style>
