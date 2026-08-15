<script setup>
import MediaDeleteDialog from "@/components/dialog/media/MediaDeleteDialog.vue";
import MediaApprovalDialog from "@/components/dialog/media/MediaApprovalDialog.vue";
import MediaFormDialog from "@/components/dialog/media/MediaFormDialog.vue";
import MediaPreviewDialog from "@/components/dialog/media/MediaPreviewDialog.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import EmptyData from "@/components/shared/EmptyData.vue";
import TableSkeleton from "@/components/shared/TableSkeleton.vue";
import { useQueryFilters } from "@/composables/useQueryFilters";
import { showAlert } from "@/helpers/alert";
import mediaService from "@/services/media.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, reactive, ref } from "vue";

const authStore = useAuthStore();
const mediaAssets = ref([]);
const media = ref(null);
const customers = ref([]);
const loading = ref(false);
const loadingOptions = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(7);
const pagination = ref({});

const dialogs = reactive({
    form: false,
    preview: false,
    approval: false,
    delete: false,
    filters: false,
});

const filters = reactive({
    media_id: { value: null, type: "number" },
    global: { value: null, type: "string" },
    user_id: { value: null, type: "number" },
    type: { value: null, type: "string" },
    approval_status: { value: null, type: "string" },
});

const typeOptions = [
    { label: "Imagem", value: "image" },
    { label: "Vídeo", value: "video" },
];

const approvalOptions = [
    { label: "Aguardando aprovação", value: "pending_approval" },
    { label: "Aguardando assinatura", value: "awaiting_subscription" },
    { label: "Aprovada", value: "approved" },
    { label: "Rejeitada", value: "rejected" },
];

const breadcrumbItems = [
    { icon: "pi pi-home", to: "/" },
    { label: "Biblioteca de mídias" },
];

const skeletonColumns = [
    { headerWidth: "70px", bodyWidth: "230px" },
    {
        headerWidth: "55px",
        bodyWidth: "90px",
        height: "22px",
        borderRadius: "999px",
    },
    {
        headerWidth: "50px",
        bodyWidth: "65px",
        height: "22px",
        borderRadius: "999px",
    },
    { headerWidth: "80px", bodyWidth: "120px" },
    {
        headerWidth: "55px",
        bodyWidth: "70px",
        height: "22px",
        borderRadius: "999px",
    },
    {
        headerWidth: "50px",
        bodyWidth: "100px",
        height: "28px",
        borderRadius: "999px",
        align: "center",
    },
];

const canCreate = computed(() => authStore.hasPermission("media.create"));
const canUpdate = computed(() => authStore.hasPermission("media.update"));
const canApprove = computed(() => authStore.hasPermission("media.approve"));
const canDelete = computed(() => authStore.hasPermission("media.delete"));

const activeFiltersCount = computed(
    () =>
        Object.values(filters).filter(
            (filter) => filter.value !== null && filter.value !== "",
        ).length,
);

const filtersButtonLabel = computed(() =>
    activeFiltersCount.value
        ? `Filtros (${activeFiltersCount.value})`
        : "Filtros",
);

const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(
    filters,
    currentPage,
);

const fetchAll = async (page) => {
    syncToRoute(page);
    try {
        loading.value = true;
        const response = await mediaService.index(
            page,
            itemsPerPage.value,
            buildApiFilters(),
        );
        mediaAssets.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const fetchOptions = async () => {
    try {
        loadingOptions.value = true;
        const response = await mediaService.customerOptions();
        customers.value = (response.data ?? []).map((customer) => ({
            ...customer,
            full_name: `${customer.name} ${customer.last_name}`.trim(),
        }));
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loadingOptions.value = false;
    }
};

const openDialog = (type, data = null) => {
    const allowed =
        type === "preview" ||
        (type === "approval"
            ? canApprove.value
            : type === "delete"
              ? canDelete.value
              : data
                ? canUpdate.value
                : canCreate.value);
    if (!allowed)
        return showAlert(
            "warning",
            "Você não possui permissão para realizar esta ação.",
        );
    media.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const clearFilters = () => {
    Object.values(filters).forEach((filter) => (filter.value = null));
    dialogs.filters = false;
    fetchAll(1);
};
const applyMobileFilters = () => {
    dialogs.filters = false;
    fetchAll(1);
};
const onPage = (event) => {
    itemsPerPage.value = event.rows;
    fetchAll(event.page + 1);
};
const formatSize = (bytes) =>
    bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.ceil(bytes / 1024)} KB`;
const approvalLabel = (value) =>
    approvalOptions.find((option) => option.value === value)?.label ?? "-";
const approvalSeverity = (value) =>
    ({
        pending_approval: "warn",
        awaiting_subscription: "info",
        approved: "success",
        rejected: "danger",
    })[value] ?? "secondary";
const formatSpecifications = (data) => {
    if (data.width && data.height) return `${data.width} × ${data.height}px`;
    if (data.duration_seconds) return `${data.duration_seconds}s`;
    return "-";
};

onMounted(() => {
    applyFromRoute();
    fetchOptions();
    fetchAll(currentPage.value);
});
</script>

<template>
    <section class="container">
        <div
            class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3"
        >
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
                    label="Nova mídia"
                    icon="pi pi-plus"
                    size="small"
                    :disabled="!canCreate"
                    @click="openDialog('form')"
                />
            </div>
        </div>

        <Card class="mb-3 d-none d-md-block"
            ><template #content>
                <form
                    class="row g-3 align-items-end"
                    @submit.prevent="fetchAll(1)"
                >
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Buscar</label
                            ><InputText
                                v-model="filters.global.value"
                                placeholder="Nome, arquivo ou anunciante"
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="field">
                            <label>Anunciante</label
                            ><Select
                                v-model="filters.user_id.value"
                                :options="customers"
                                optionLabel="full_name"
                                optionValue="id"
                                placeholder="Todos"
                                showClear
                                filter
                                :loading="loadingOptions"
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="field">
                            <label>Tipo</label
                            ><Select
                                v-model="filters.type.value"
                                :options="typeOptions"
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
                            <label>Status</label
                            ><Select
                                v-model="filters.approval_status.value"
                                :options="approvalOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todos"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <Button
                            label="Limpar"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            outlined
                            @click="clearFilters"
                        /><Button
                            label="Filtrar"
                            icon="pi pi-search"
                            type="submit"
                        />
                    </div>
                </form> </template
        ></Card>

        <Dialog
            v-model:visible="dialogs.filters"
            modal
            header="Filtrar mídias"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form
                id="mediaMobileFiltersForm"
                class="row g-3"
                @submit.prevent="applyMobileFilters"
            >
                <div class="col-12">
                    <div class="field">
                        <label>Buscar</label
                        ><InputText v-model="filters.global.value" fluid />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Anunciante</label
                        ><Select
                            v-model="filters.user_id.value"
                            :options="customers"
                            optionLabel="full_name"
                            optionValue="id"
                            placeholder="Todos"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Tipo</label
                        ><Select
                            v-model="filters.type.value"
                            :options="typeOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todos"
                            showClear
                            fluid
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Status</label
                        ><Select
                            v-model="filters.approval_status.value"
                            :options="approvalOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todos"
                            showClear
                            fluid
                        />
                    </div>
                </div>
            </form>
            <template #footer
                ><Button
                    label="Limpar"
                    icon="pi pi-filter-slash"
                    severity="secondary"
                    outlined
                    @click="clearFilters" /><Button
                    label="Aplicar filtros"
                    icon="pi pi-search"
                    type="submit"
                    form="mediaMobileFiltersForm"
            /></template>
        </Dialog>

        <Card>
            <template #content>
                <TableSkeleton
                    v-show="loading"
                    :rows="itemsPerPage"
                    :columns="skeletonColumns"
                    class="media-table-skeleton"
                />
                <DataTable
                    v-show="!loading && mediaAssets.length"
                    :value="mediaAssets"
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
                    <Column header="Mídia" style="min-width: 280px">
                        <template #body="{ data }">
                            <div class="d-flex align-items-center gap-3">
                                <div class="media-thumb">
                                    <img
                                        v-if="data.type === 'image'"
                                        :src="data.content_url"
                                        :alt="data.name"
                                    />
                                    <i v-else class="pi pi-video"></i>
                                </div>
                                <div class="d-flex flex-column">
                                    <strong>{{ data.name }}</strong>
                                    <small class="text-muted">
                                        {{ data.original_name }} · {{ formatSize(data.size_bytes) }}
                                    </small>
                                    <small class="advertiser-name">
                                        <i class="pi pi-user me-1"></i>
                                        {{ data.customer?.name }} {{ data.customer?.last_name }}
                                    </small>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Tipo" style="width: 100px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.type === 'image' ? 'Imagem' : 'Vídeo'"
                                :icon="data.type === 'image' ? 'pi pi-image' : 'pi pi-video'"
                                severity="info"
                            />
                        </template>
                    </Column>

                    <Column header="Detalhes" style="min-width: 140px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column">
                                <span>{{ formatSpecifications(data) }}</span>
                                <small class="text-muted">{{ data.mime_type }}</small>
                            </div>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 180px">
                        <template #body="{ data }">
                            <div class="d-flex flex-column align-items-start gap-1">
                                <Tag
                                    :value="approvalLabel(data.approval_status)"
                                    :severity="approvalSeverity(data.approval_status)"
                                />
                                <small
                                    v-if="data.rejection_reason"
                                    class="text-danger text-truncate rejection-reason"
                                    :title="data.rejection_reason"
                                >
                                    {{ data.rejection_reason }}
                                </small>
                            </div>
                        </template>
                    </Column>

                    <Column header="Vínculo" style="min-width: 150px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.campaigns_count > 0
                                    ? `Vinculada a\n${data.campaigns_count} ${data.campaigns_count === 1 ? 'campanha' : 'campanhas'}`
                                    : 'Sem vínculo'"
                                :severity="data.campaigns_count > 0 ? 'info' : 'secondary'"
                                :icon="data.campaigns_count > 0 ? 'pi pi-link' : 'pi pi-minus-circle'"
                                v-tooltip.top="data.campaigns_count > 0 ? `Vinculada a ${data.campaigns_count} campanha(s)` : 'Não está vinculada a nenhuma campanha'"
                                class="campaign-link-tag"
                            />
                        </template>
                    </Column>

                    <Column style="width: 185px">
                        <template #header>
                            <span class="w-100 text-center">Ações</span>
                        </template>
                        <template #body="{ data }">
                            <div class="d-flex justify-content-center gap-1">
                                <Button
                                    icon="pi pi-eye"
                                    text
                                    rounded
                                    @click="openDialog('preview', data)"
                                />
                                <Button
                                    icon="pi pi-check-circle"
                                    text
                                    rounded
                                    severity="success"
                                    :disabled="
                                        !canApprove ||
                                        data.processing_status !== 'ready'
                                    "
                                    v-tooltip.top="'Analisar mídia'"
                                    @click="openDialog('approval', data)"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    text
                                    rounded
                                    :disabled="!canUpdate"
                                    @click="openDialog('form', data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    severity="danger"
                                    :disabled="!canDelete"
                                    @click="openDialog('delete', data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <EmptyData
                    v-if="!loading && !mediaAssets.length"
                    :show-btn-clean-filters="true"
                    @clean-filters="clearFilters"
                />
            </template>
        </Card>

        <MediaFormDialog
            v-model="dialogs.form"
            :media="media"
            :customers="customers"
            @saved="fetchAll(currentPage)"
        />
        <MediaPreviewDialog v-model="dialogs.preview" :media="media" />
        <MediaApprovalDialog
            v-model="dialogs.approval"
            :media="media"
            @saved="fetchAll(currentPage)"
        />
        <MediaDeleteDialog
            v-model="dialogs.delete"
            :media="media"
            @deleted="fetchAll(currentPage)"
        />
    </section>
</template>

<style scoped>
.media-thumb {
    width: 58px;
    height: 42px;
    display: grid;
    place-items: center;
    overflow: hidden;
    flex: 0 0 auto;
    border-radius: 6px;
    background: var(--p-surface-100);
    font-size: 1.4rem;
}
.media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rejection-reason {
    max-width: 150px;
}
.advertiser-name {
    margin-top: 0.2rem;
    color: var(--p-primary-color);
}
:deep(.campaign-link-tag) {
    min-width: 105px;
    justify-content: center;
    text-align: center;
}
:deep(.campaign-link-tag .p-tag-label) {
    white-space: pre-line;
    line-height: 1.15;
}
:deep(.media-table-skeleton) {
    --table-skeleton-columns: minmax(280px, 1.4fr) 125px 100px 140px 180px 185px;
    --table-skeleton-min-width: 1015px;
}
</style>
