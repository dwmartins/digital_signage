<script setup>
import ScreenDeleteDialog from '@/components/dialog/screen/ScreenDeleteDialog.vue';
import ScreenFormDialog from '@/components/dialog/screen/ScreenFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { useQueryFilters } from '@/composables/useQueryFilters';
import { formatDateTime } from '@/helpers/date';
import { showAlert } from '@/helpers/alert';
import screenService from '@/services/screen.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

const authStore = useAuthStore();
const screens = ref([]);
const screen = ref(null);
const establishments = ref([]);
const loading = ref(false);
const refreshing = ref(false);
const pagination = ref({});
const currentPage = ref(1);
const itemsPerPage = ref(7);
const dialogs = reactive({ form: false, delete: false });
const filters = reactive({
    global: { value: null, type: 'string' },
    establishment_id: { value: null, type: 'number' },
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
    { headerWidth: '110px', bodyWidth: '180px' },
    { headerWidth: '80px', bodyWidth: '140px' },
    { headerWidth: '80px', bodyWidth: '130px' },
    { headerWidth: '80px', bodyWidth: '120px' },
    { headerWidth: '60px', bodyWidth: '75px', height: '22px', borderRadius: '999px' },
    { headerWidth: '80px', bodyWidth: '120px' },
    { headerWidth: '50px', bodyWidth: '72px', height: '28px', borderRadius: '999px', align: 'center' },
];
const canCreate = computed(() => authStore.hasPermission('screens.create'));
const canUpdate = computed(() => authStore.hasPermission('screens.update'));
const canDelete = computed(() => authStore.hasPermission('screens.delete'));
const selectedFilterEstablishment = computed(() => establishments.value.find(item => item.id === filters.establishment_id.value));
const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(filters, currentPage);
let refreshInterval = null;

const fetchAll = async (page, silent = false) => {
    if (loading.value || refreshing.value) return;

    if (!silent) syncToRoute(page);

    try {
        silent ? refreshing.value = true : loading.value = true;
        const response = await screenService.index(page, itemsPerPage.value, buildApiFilters());
        screens.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        if (!silent) showAlert('error', error.response?.data);
    } finally {
        silent ? refreshing.value = false : loading.value = false;
    }
};

const fetchEstablishments = async () => {
    try {
        const response = await screenService.establishmentOptions();
        establishments.value = response.data ?? [];
    } catch (error) {
        showAlert('error', error.response?.data);
    }
};

const openDialog = (type, data = null) => {
    const allowed = type === 'delete' ? canDelete.value : (data ? canUpdate.value : canCreate.value);
    if (!allowed) return showAlert('warning', 'Você não possui permissão para realizar esta ação.');
    screen.value = data ? { ...data } : null;
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

const statusLabel = status => statusOptions.find(option => option.value === status)?.label ?? '-';
const statusSeverity = status => ({ active: 'success', maintenance: 'warn', blocked: 'danger', stock: 'contrast' })[status] ?? 'secondary';
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
    if (data.connection_status === 'online') return 'Online';
    if (data.connection_status === 'never_connected') return 'Nunca conectada';
    return `Sem conexão há ${connectionDelay(data.connection_delay_seconds)}`;
};
const connectionSeverity = status => status === 'online' ? 'success' : (status === 'never_connected' ? 'secondary' : 'danger');

onMounted(() => {
    applyFromRoute();
    fetchEstablishments();
    fetchAll(currentPage.value);

    refreshInterval = window.setInterval(() => {
        fetchAll(currentPage.value, true);
    }, 60000);
});

onUnmounted(() => window.clearInterval(refreshInterval));
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItens" />
        <div class="d-flex justify-content-end mb-3">
            <Button label="Nova tela" icon="pi pi-plus" size="small" :disabled="!canCreate" @click="openDialog('form')" />
        </div>

        <Card class="mb-3">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Buscar</label>
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Nome, código ou local"
                                    fluid
                                />
                            </IconField>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
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
                            >
                                <template #option="{ option }">
                                    <div class="d-flex flex-column">
                                        <span>#{{ option.id }} - {{ option.name }}</span>
                                        <small class="text-muted">{{ option.city }} / {{ option.state }}</small>
                                    </div>
                                </template>
                                <template #value="{ value, placeholder }">
                                    <span v-if="value">#{{ selectedFilterEstablishment?.id }} - {{ selectedFilterEstablishment?.name }}</span>
                                    <span v-else>{{ placeholder }}</span>
                                </template>
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
                <Column header="Estabelecimento" style="min-width: 210px">
                    <template #body="{ data }">
                        <div class="d-flex flex-column">
                            <span>{{ data.establishment?.name || '-' }}</span>
                            <small class="text-muted">{{ data.location || 'Local não informado' }}</small>
                        </div>
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
                <Column header="Comunicação" style="min-width: 145px">
                    <template #body="{ data }">
                        A cada {{ data.heartbeat_interval }} segundos
                    </template>
                </Column>
                <Column field="status" header="Status" style="width: 120px">
                    <template #body="{ data }">
                        <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                    </template>
                </Column>
                <Column header="Conectividade" style="min-width: 210px">
                    <template #body="{ data }">
                        <div class="d-flex flex-column align-items-start gap-1">
                            <Tag :value="connectionLabel(data)" :severity="connectionSeverity(data.connection_status)" />
                            <small class="text-muted">Última: {{ formatDateTime(data.last_seen_at) }}</small>
                        </div>
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
    --table-skeleton-columns: minmax(210px, 1.2fr) minmax(210px, 1.1fr) 155px 155px 145px 120px 210px 110px;
    --table-skeleton-min-width: 1275px;
}
</style>
