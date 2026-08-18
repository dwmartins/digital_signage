<script setup>
import CampaignDeleteDialog from "@/components/dialog/campaign/CampaignDeleteDialog.vue";
import CampaignDetailsDialog from "@/components/dialog/campaign/CampaignDetailsDialog.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import EmptyData from "@/components/shared/EmptyData.vue";
import TableSkeleton from "@/components/shared/TableSkeleton.vue";
import { useQueryFilters } from "@/composables/useQueryFilters";
import { showAlert } from "@/helpers/alert";
import campaignService from "@/services/campaign.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

const auth = useAuthStore();
const router = useRouter();
const campaigns = ref([]);
const campaign = ref(null);
const customers = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(7);
const pagination = ref({});

const dialogs = reactive({ details: false, delete: false, filters: false });

const filters = reactive({
    global: { value: null, type: "string" },
    user_id: { value: null, type: "number" },
    status: { value: null, type: "string" },
});

const statusOptions = [
    { label: "Ativa", value: "active" },
    { label: "Pendente", value: "pending" },
    { label: "Pausada", value: "paused" },
    { label: "Cancelada", value: "cancelled" },
];

const skeletonColumns = [
    { bodyWidth: "190px" },
    { bodyWidth: "170px" },
    { bodyWidth: "160px" },
    { bodyWidth: "130px" },
    { bodyWidth: "110px" },
    { bodyWidth: "150px", align: "center" },
];

const canCreate = computed(() => auth.hasPermission("campaigns.create"));
const canUpdate = computed(() => auth.hasPermission("campaigns.update"));
const canDelete = computed(() => auth.hasPermission("campaigns.delete"));

const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(
    filters,
    currentPage,
);

const fetchOptions = async () => {
    const response = await campaignService.options();
    customers.value = (response.customers ?? []).map((item) => ({
        ...item,
        full_name: `${item.name} ${item.last_name ?? ""}`.trim(),
    }));
};

const fetchAll = async (page = 1) => {
    syncToRoute(page);
    try {
        loading.value = true;
        const response = await campaignService.index(
            page,
            itemsPerPage.value,
            buildApiFilters(),
        );
        campaigns.value = response.data ?? [];
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const openDialog = (type, data = null) => {
    if (type === "details") {
        campaign.value = { ...data };
        dialogs.details = true;

        return;
    }

    const allowed =
        type === "delete"
            ? canDelete.value
            : data
              ? canUpdate.value
              : canCreate.value;
    if (!allowed)
        return showAlert(
            "warning",
            "Você não possui permissão para esta ação.",
        );
    if (type === "form") {
        return router.push({
            name: data ? "platform.campaigns.edit" : "platform.campaigns.create",
            params: data ? { id: data.id } : {},
        });
    }
    campaign.value = data ? { ...data } : null;
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
const statusLabel = (value) =>
    statusOptions.find((option) => option.value === value)?.label ?? value;
const statusSeverity = (value) =>
    ({
        active: "success",
        pending: "warn",
        paused: "secondary",
        cancelled: "danger",
    })[value] ?? "secondary";

onMounted(async () => {
    applyFromRoute();
    try {
        await fetchOptions();
    } catch (error) {
        showAlert("error", error.response?.data);
    }
    fetchAll(currentPage.value);
});
</script>

<template>
    <section class="container">
        <div
            class="d-flex justify-content-between align-items-start gap-2 mb-3"
        >
            <Breadcrumb
                :items="[
                    { icon: 'pi pi-home', to: '/' },
                    { label: 'Campanhas' },
                ]"
            />
            <div class="d-flex gap-2">
                <Button
                    class="d-md-none"
                    label="Filtros"
                    icon="pi pi-filter"
                    outlined
                    @click="dialogs.filters = true"
                /><Button
                    label="Nova campanha"
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
                    <div class="col-lg-4">
                        <div class="field">
                            <label>Buscar</label
                            ><InputText
                                v-model="filters.global.value"
                                placeholder="Campanha ou cliente"
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Cliente</label
                            ><Select
                                v-model="filters.user_id.value"
                                :options="customers"
                                optionLabel="full_name"
                                optionValue="id"
                                filter
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="field">
                            <label>Status</label
                            ><Select
                                v-model="filters.status.value"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2 d-flex justify-content-end gap-2">
                        <Button
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
            header="Filtrar campanhas"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            :draggable="false"
        >
            <form
                id="campaignFilters"
                class="row g-3"
                @submit.prevent="applyMobileFilters"
            >
                <div class="col-12">
                    <InputText
                        v-model="filters.global.value"
                        placeholder="Campanha ou cliente"
                        fluid
                    />
                </div>
                <div class="col-12">
                    <Select
                        v-model="filters.user_id.value"
                        :options="customers"
                        optionLabel="full_name"
                        optionValue="id"
                        placeholder="Cliente"
                        filter
                        showClear
                        fluid
                    />
                </div>
                <div class="col-12">
                    <Select
                        v-model="filters.status.value"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Status"
                        showClear
                        fluid
                    />
                </div>
            </form>
            <template #footer
                ><Button label="Limpar" outlined @click="clearFilters" /><Button
                    label="Aplicar"
                    type="submit"
                    form="campaignFilters"
            /></template>
        </Dialog>

        <Card
            ><template #content>
                <TableSkeleton
                    v-show="loading"
                    :rows="itemsPerPage"
                    :columns="skeletonColumns"
                />
                <DataTable
                    v-show="!loading && campaigns.length"
                    :value="campaigns"
                    lazy
                    paginator
                    scrollable
                    stripedRows
                    :totalRecords="pagination.total"
                    :first="(currentPage - 1) * itemsPerPage"
                    :rows="itemsPerPage"
                    :rowsPerPageOptions="[5, 7, 10, 20]"
                    @page="
                        (event) => {
                            itemsPerPage = event.rows;
                            fetchAll(event.page + 1);
                        }
                    "
                >
                    <Column header="Campanha" style="min-width: 220px"
                        ><template #body="{ data }"
                            ><div class="d-flex flex-column">
                                <strong>#{{ data.id }} - {{ data.name }}</strong
                                ><small
                                    class="text-muted text-truncate campaign-description"
                                    >{{
                                        data.description || "Sem observação"
                                    }}</small
                                >
                            </div></template
                        ></Column
                    >
                    <Column header="Cliente" style="min-width: 200px"
                        ><template #body="{ data }"
                            ><div class="d-flex flex-column">
                                <span
                                    >{{ data.customer?.name }}
                                    {{ data.customer?.last_name }}</span
                                ><small class="text-muted">{{
                                    data.customer?.email
                                }}</small>
                            </div></template
                        ></Column
                    >
                    <Column header="Plano / Mídia" style="min-width: 190px"
                        ><template #body="{ data }"
                            ><div class="d-flex flex-column">
                                <strong>{{
                                    data.subscription?.plan?.name
                                }}</strong
                                ><small class="text-muted">{{
                                    data.media_assets?.length
                                        ? `${data.media_assets.length} mídia(s) vinculada(s)`
                                        : "Sem mídia"
                                }}</small>
                            </div></template
                        ></Column
                    >
                    <Column header="Status" style="width: 150px"
                        ><template #body="{ data }"
                            ><Tag
                                :value="statusLabel(data.status)"
                                :severity="
                                    statusSeverity(data.status)
                                " /></template
                    ></Column>
                    <Column style="width: 150px">
                        <template #header>
                            <span class="w-100 text-center">Ações</span>
                        </template>
                        <template #body="{ data }">
                            <div class="d-flex justify-content-center gap-1">
                                <Button
                                    icon="pi pi-eye"
                                    text
                                    rounded
                                    v-tooltip.top="'Visualizar detalhes'"
                                    @click="openDialog('details', data)"
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
                    v-if="!loading && !campaigns.length"
                    :show-btn-clean-filters="true"
                    @clean-filters="clearFilters"
                /> </template
        ></Card>

        <CampaignDetailsDialog
            v-model="dialogs.details"
            :campaign-id="campaign?.id"
        />

        <CampaignDeleteDialog
            v-model="dialogs.delete"
            :campaign="campaign"
            @deleted="fetchAll(currentPage)"
        />
    </section>
</template>

<style scoped>
.campaign-description {
    max-width: 210px;
}
</style>
