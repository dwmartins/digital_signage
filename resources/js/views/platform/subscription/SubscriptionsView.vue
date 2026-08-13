<script setup>
import SubscriptionFormDialog from "@/components/dialog/subscription/SubscriptionFormDialog.vue";
import SubscriptionApprovalDialog from "@/components/dialog/subscription/SubscriptionApprovalDialog.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import EmptyData from "@/components/shared/EmptyData.vue";
import TableSkeleton from "@/components/shared/TableSkeleton.vue";
import { useQueryFilters } from "@/composables/useQueryFilters";
import { showAlert } from "@/helpers/alert";
import { formatDate } from "@/helpers/date";
import subscriptionService from "@/services/subscription.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, reactive, ref } from "vue";
const auth = useAuthStore(),
    items = ref([]),
    subscription = ref(null),
    campaigns = ref([]),
    plans = ref([]),
    statusOptions = ref([]),
    loading = ref(false),
    currentPage = ref(1),
    perPage = ref(7),
    pagination = ref({});
const dialogs = reactive({ form: false, approval: false, filters: false });
const filters = reactive({
    global: { value: null, type: "string" },
    campaign_id: { value: null, type: "number" },
    plan_id: { value: null, type: "number" },
    status: { value: null, type: "string" },
});
const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(
    filters,
    currentPage,
);
const canUpdate = computed(() => auth.hasPermission("subscriptions.update")),
    canApprove = computed(() => auth.hasPermission("subscriptions.approve")),
    canCancel = computed(() => auth.hasPermission("subscriptions.cancel"));
const money = (v) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(v ?? 0);
const label = (s) => statusOptions.value.find((x) => x.value === s)?.label ?? s;
const severity = (s) =>
    ({
        pending: "warn",
        active: "success",
        expired: "secondary",
        cancelled: "danger",
    })[s] ?? "secondary";
const fetchOptions = async () => {
    const r = await subscriptionService.options();
    campaigns.value = (r.campaigns ?? []).map((x) => ({
        ...x,
        label: `#${x.id} - ${x.name} · ${x.customer?.name ?? ""} ${x.customer?.last_name ?? ""}`,
    }));
    plans.value = r.plans ?? [];
    statusOptions.value = r.statuses ?? [];
};
const fetchAll = async (page = 1) => {
    syncToRoute(page);
    try {
        loading.value = true;
        const r = await subscriptionService.index(
            page,
            perPage.value,
            buildApiFilters(),
        );
        items.value = r.data ?? [];
        pagination.value = r.pagination ?? {};
        currentPage.value = r.pagination?.current_page ?? 1;
    } catch (e) {
        showAlert("error", e.response?.data);
    } finally {
        loading.value = false;
    }
};
const open = (data) => {
    if (!data || !canUpdate.value)
        return showAlert(
            "warning",
            "Você não possui permissão para esta ação.",
        );
    subscription.value = data ? { ...data } : null;
    dialogs.form = true;
};
const openApproval = (data) => {
    if (!canApprove.value)
        return showAlert(
            "warning",
            "Você não possui permissão para aprovar assinaturas.",
        );
    subscription.value = { ...data };
    dialogs.approval = true;
};
const cancel = async (data) => {
    try {
        const r = await subscriptionService.cancel(data.id);
        showAlert("success", r.message);
        fetchAll(currentPage.value);
    } catch (e) {
        showAlert("error", e.response?.data);
    }
};
const clear = () => {
    Object.values(filters).forEach((x) => (x.value = null));
    dialogs.filters = false;
    fetchAll(1);
};
onMounted(async () => {
    applyFromRoute();
    try {
        await fetchOptions();
    } catch (e) {
        showAlert("error", e.response?.data);
    }
    fetchAll(currentPage.value);
});
</script>
<template>
    <section class="container">
        <div class="d-flex justify-content-between mb-3">
            <Breadcrumb
                :items="[
                    { icon: 'pi pi-home', to: '/' },
                    { label: 'Assinaturas' },
                ]"
            />
            <div class="d-flex gap-2">
                <Button
                    class="d-md-none"
                    label="Filtros"
                    icon="pi pi-filter"
                    outlined
                    @click="dialogs.filters = true"
                />
            </div>
        </div>
        <Card class="mb-3 d-none d-md-block"
            ><template #content
                ><form
                    class="row g-3 align-items-end"
                    @submit.prevent="fetchAll(1)"
                >
                    <div class="col-lg-3">
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
                            <label>Campanha</label
                            ><Select
                                v-model="filters.campaign_id.value"
                                :options="campaigns"
                                optionLabel="label"
                                optionValue="id"
                                filter
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="field">
                            <label>Plano</label
                            ><Select
                                v-model="filters.plan_id.value"
                                :options="plans"
                                optionLabel="name"
                                optionValue="id"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-2">
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
                    <div class="col-lg-2 d-flex gap-2">
                        <Button
                            icon="pi pi-filter-slash"
                            outlined
                            severity="secondary"
                            @click="clear"
                        /><Button
                            label="Filtrar"
                            icon="pi pi-search"
                            type="submit"
                        />
                    </div></form></template
        ></Card>
        <Dialog
            v-model:visible="dialogs.filters"
            modal
            header="Filtrar assinaturas"
            :style="{ width: '32rem' }"
            :breakpoints="{ '768px': '94vw' }"
            ><form
                id="subscriptionFilters"
                class="row g-3"
                @submit.prevent="
                    dialogs.filters = false;
                    fetchAll(1);
                "
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
                        v-model="filters.campaign_id.value"
                        :options="campaigns"
                        optionLabel="label"
                        optionValue="id"
                        placeholder="Campanha"
                        filter
                        showClear
                        fluid
                    />
                </div>
                <div class="col-12">
                    <Select
                        v-model="filters.plan_id.value"
                        :options="plans"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Plano"
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
                ><Button label="Limpar" outlined @click="clear" /><Button
                    label="Aplicar"
                    type="submit"
                    form="subscriptionFilters" /></template
        ></Dialog>
        <Card
            ><template #content
                ><TableSkeleton
                    v-show="loading"
                    :rows="perPage"
                    :columns="[
                        { bodyWidth: '170px' },
                        { bodyWidth: '130px' },
                        { bodyWidth: '90px' },
                        { bodyWidth: '100px' },
                        { bodyWidth: '130px' },
                        { bodyWidth: '150px' },
                    ]" /><DataTable
                    v-show="!loading && items.length"
                    :value="items"
                    lazy
                    paginator
                    scrollable
                    stripedRows
                    :totalRecords="pagination.total"
                    :first="(currentPage - 1) * perPage"
                    :rows="perPage"
                    @page="
                        (e) => {
                            perPage = e.rows;
                            fetchAll(e.page + 1);
                        }
                    "
                    ><Column header="Campanha" style="min-width: 210px"
                        ><template #body="{ data }"
                            ><div class="d-flex flex-column">
                                <strong
                                    >#{{ data.campaign?.id }} -
                                    {{ data.campaign?.name }}</strong
                                ><small class="text-muted"
                                    >{{ data.campaign?.customer?.name }}
                                    {{
                                        data.campaign?.customer?.last_name
                                    }}</small
                                >
                            </div></template
                        ></Column
                    ><Column header="Plano" style="min-width: 150px"
                        ><template #body="{ data }">{{
                            data.plan?.name
                        }}</template></Column
                    ><Column header="Valor" style="min-width: 120px"
                        ><template #body="{ data }"
                            ><strong>{{ money(data.price) }}</strong></template
                        ></Column
                    ><Column header="Status" style="width: 110px"
                        ><template #body="{ data }"
                            ><Tag
                                :value="label(data.status)"
                                :severity="
                                    severity(data.status)
                                " /></template></Column
                    ><Column header="Vigência" style="min-width: 160px"
                        ><template #body="{ data }"
                            >{{ formatDate(data.starts_at) }} —
                            {{ formatDate(data.ends_at) }}</template
                        ></Column
                    ><Column style="width: 160px"
                        ><template #header
                            ><span class="w-100 text-center"
                                >Ações</span
                            ></template
                        ><template #body="{ data }"
                            ><div class="d-flex justify-content-center">
                                <Button
                                    icon="pi pi-pencil"
                                    text
                                    rounded
                                    :disabled="!canUpdate"
                                    @click="open(data)"
                                /><Button
                                    icon="pi pi-check"
                                    text
                                    rounded
                                    severity="success"
                                    :disabled="
                                        !canApprove || data.status !== 'pending'
                                    "
                                    v-tooltip.top="'Aprovar e gerar cobrança'"
                                    @click="openApproval(data)"
                                /><Button
                                    icon="pi pi-ban"
                                    text
                                    rounded
                                    severity="danger"
                                    :disabled="
                                        !canCancel ||
                                        data.status === 'cancelled'
                                    "
                                    @click="cancel(data)"
                                /></div></template></Column></DataTable
                ><EmptyData
                    v-if="!loading && !items.length"
                    :show-btn-clean-filters="true"
                    @clean-filters="clear" /></template></Card
        ><SubscriptionFormDialog
            v-model="dialogs.form"
            :subscription="subscription"
            :campaigns="campaigns"
            :plans="plans"
            :statuses="statusOptions"
            @saved="fetchAll(currentPage)"
        />
        <SubscriptionApprovalDialog
            v-model="dialogs.approval"
            :subscription="subscription"
            @approved="fetchAll(currentPage)"
        />
    </section>
</template>
