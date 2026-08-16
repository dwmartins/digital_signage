<script setup>
import DeleteDialog from '@/components/dialog/display-point/DisplayPointDeleteDialog.vue';
import FormDialog from '@/components/dialog/display-point/DisplayPointFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { formatDateTime } from '@/helpers/date';
import { showAlert } from '@/helpers/alert';
import displayPointService from '@/services/display-point.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

const authStore = useAuthStore();
const displayPoints = ref([]);
const displayPoint = ref(null);
const establishments = ref([]);
const loading = ref(false);
const refreshing = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(7);
const pagination = ref({});

const dialogs = reactive({ form: false, delete: false, filters: false });
const filters = reactive({ global: '', establishment_id: null, orientation: null, status: null });

const statusOptions = [
    { label: 'Ativo', value: 'active' },
    { label: 'Manutenção', value: 'maintenance' },
    { label: 'Inativo', value: 'inactive' },
];

const orientationOptions = [
    { label: 'Horizontal', value: 'landscape' },
    { label: 'Vertical', value: 'portrait' },
];

const breadcrumbItems = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Pontos de exibição' },
];

const skeletonColumns = [
    { headerWidth: '55px', bodyWidth: '165px' },
    { headerWidth: '105px', bodyWidth: '180px' },
    { headerWidth: '80px', bodyWidth: '175px' },
    { headerWidth: '75px', bodyWidth: '95px', height: '22px', borderRadius: '999px' },
    { headerWidth: '90px', bodyWidth: '190px', height: '22px', borderRadius: '999px' },
    { headerWidth: '50px', bodyWidth: '70px', height: '22px', borderRadius: '999px' },
    { headerWidth: '50px', bodyWidth: '72px', height: '28px', borderRadius: '999px', align: 'center' },
];

const canCreate = computed(() => authStore.hasPermission('display-points.create'));
const canUpdate = computed(() => authStore.hasPermission('display-points.update'));
const canDelete = computed(() => authStore.hasPermission('display-points.delete'));
const activeFiltersCount = computed(() => Object.values(filters)
    .filter(value => value !== null && value !== '')
    .length);
const filtersButtonLabel = computed(() => activeFiltersCount.value
    ? `Filtros (${activeFiltersCount.value})`
    : 'Filtros');

let refreshInterval = null;

const fetchAll = async (page = currentPage.value, silent = false) => {
    if (loading.value || refreshing.value) return;

    try {
        silent ? refreshing.value = true : loading.value = true;
        const response = await displayPointService.index(page, itemsPerPage.value, filters);
        displayPoints.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        if (!silent) showAlert('error', error.response?.data);
    } finally {
        silent ? refreshing.value = false : loading.value = false;
    }
};

const openDialog = (type, data = null) => {
    displayPoint.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const fetchFilterOptions = async () => {
    try {
        const response = await displayPointService.options();
        establishments.value = response.establishments ?? [];
    } catch (error) {
        showAlert('error', error.response?.data);
    }
};

const clearFilters = () => {
    filters.global = '';
    filters.establishment_id = null;
    filters.orientation = null;
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
const statusSeverity = status => ({ active: 'success', maintenance: 'warn', inactive: 'secondary' })[status] ?? 'secondary';
const orientationLabel = orientation => orientationOptions.find(option => option.value === orientation)?.label ?? '-';
const connectionDelay = seconds => {
    if (seconds < 60) return `${seconds} segundo${seconds === 1 ? '' : 's'}`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} minuto${minutes === 1 ? '' : 's'}`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} hora${hours === 1 ? '' : 's'}`;
    const days = Math.floor(hours / 24);
    return `${days} dia${days === 1 ? '' : 's'}`;
};
const connectionLabel = data => {
    if (!data.player) return 'Sem player (PC) vinculado';
    if (data.player.connection_status === 'online') return 'Online';
    if (data.player.connection_status === 'never_connected') return 'Nunca conectado';
    return `Sem conexão · última há ${connectionDelay(data.player.connection_delay_seconds)}`;
};
const connectionSeverity = data => {
    if (!data.player || data.player.connection_status === 'never_connected') return 'secondary';
    return data.player.connection_status === 'online' ? 'success' : 'danger';
};

onMounted(() => {
    fetchFilterOptions();
    fetchAll();
    refreshInterval = window.setInterval(() => fetchAll(currentPage.value, true), 60000);
});

onUnmounted(() => window.clearInterval(refreshInterval));
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItems" />

        <div class="d-flex justify-content-end gap-2 mb-3">
            <Button
                class="d-inline-flex d-md-none"
                :label="filtersButtonLabel"
                icon="pi pi-filter"
                severity="secondary"
                outlined
                size="small"
                @click="dialogs.filters = true"
            />
            <Button 
                label="Novo ponto" 
                icon="pi pi-plus" 
                :disabled="!canCreate" 
                @click="openDialog('form')" 
                size="small"
            />
        </div>

        <Card class="mb-3 d-none d-md-block">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Buscar</label>
                            <InputText v-model="filters.global" placeholder="Nome ou local de instalação" fluid />
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="field">
                            <label>Estabelecimento</label>
                            <Select v-model="filters.establishment_id" :options="establishments" optionLabel="name" optionValue="id" placeholder="Todos" showClear filter fluid>
                                <template #option="{ option }">
                                    <div class="d-flex flex-column">
                                        <span>#{{ option.id }} - {{ option.name }}</span>
                                        <small class="text-muted">{{ option.city?.name }} / {{ option.city?.state?.code }}</small>
                                    </div>
                                </template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="field">
                            <label>Orientação</label>
                            <Select
                                v-model="filters.orientation"
                                :options="orientationOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todas"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="field">
                            <label>Status operacional</label>
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
            header="Filtrar pontos de exibição"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form id="displayPointMobileFilters" class="row g-3" @submit.prevent="applyMobileFilters">
                <div class="col-12">
                    <div class="field">
                        <label>Buscar</label>
                        <InputText
                            v-model="filters.global"
                            placeholder="Nome ou local de instalação"
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Estabelecimento</label>
                        <Select
                            v-model="filters.establishment_id"
                            :options="establishments"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Todos"
                            showClear
                            filter
                            fluid
                        >
                            <template #option="{ option }">
                                <div class="d-flex flex-column">
                                    <span>#{{ option.id }} - {{ option.name }}</span>
                                    <small class="text-muted">
                                        {{ option.city?.name }} / {{ option.city?.state?.code }}
                                    </small>
                                </div>
                            </template>
                        </Select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Orientação</label>
                        <Select
                            v-model="filters.orientation"
                            :options="orientationOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todas"
                            showClear
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Status operacional</label>
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
                <Button
                    label="Limpar"
                    icon="pi pi-filter-slash"
                    severity="secondary"
                    outlined
                    @click="clearFilters"
                />
                <Button
                    label="Aplicar filtros"
                    icon="pi pi-search"
                    type="submit"
                    form="displayPointMobileFilters"
                />
            </template>
        </Dialog>

        <Card>
            <template #content>
                <TableSkeleton
                    v-show="loading"
                    :rows="itemsPerPage"
                    :columns="skeletonColumns"
                    class="mt-2 display-points-table-skeleton"
                />
                <DataTable
                    v-show="!loading && displayPoints.length"
                    :value="displayPoints"
                    :totalRecords="pagination.total"
                    :first="(currentPage - 1) * itemsPerPage"
                    :rows="itemsPerPage"
                    :rowsPerPageOptions="[5, 7, 10, 20]"
                    lazy paginator scrollable stripedRows
                    @page="onPage"
                >
                    <Column field="name" header="Ponto" style="min-width: 190px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column">
                                <strong>{{ data.name }}</strong>
                                <small class="text-muted">{{ data.location || 'Local não informado' }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Estabelecimento" style="min-width: 200px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column">
                                <span>#{{ data.establishment?.id }} - {{ data.establishment?.name }}</span>
                                <small class="text-muted">
                                    {{ data.establishment?.city?.name }} / {{ data.establishment?.city?.state?.code }}
                                </small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Equipamentos" style="min-width: 210px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column">
                                <span><i class="pi pi-desktop me-2"></i>{{ data.screen?.name || 'Sem tela' }}</span>
                                <small class="text-muted"><i class="pi pi-server me-2"></i>{{ data.player?.name || 'Sem player (PC)' }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Orientação" style="width: 125px">
                        <template #body="{ data }">
                            <Tag
                                :value="orientationLabel(data.orientation)"
                                :icon="data.orientation === 'portrait' ? 'pi pi-arrows-v' : 'pi pi-arrows-h'"
                                severity="info"
                            />
                        </template>
                    </Column>
                    <Column header="Conectividade" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column align-items-start gap-1">
                                <Tag :value="connectionLabel(data)" :severity="connectionSeverity(data)" />
                                <small v-if="data.player" class="text-muted">Última: {{ formatDateTime(data.player.last_seen_at) }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Status" style="width: 110px">
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
                <EmptyData v-if="!loading && !displayPoints.length" />
            </template>
        </Card>

        <FormDialog v-model="dialogs.form" :item="displayPoint" @saved="fetchAll()" />
        <DeleteDialog v-model="dialogs.delete" :item="displayPoint" @deleted="fetchAll()" />
    </section>
</template>

<style scoped>
:deep(.display-points-table-skeleton) {
    --table-skeleton-columns: minmax(190px, 1fr) minmax(200px, 1.1fr) minmax(210px, 1.1fr) 125px 220px 110px 110px;
    --table-skeleton-min-width: 1165px;
}
</style>
