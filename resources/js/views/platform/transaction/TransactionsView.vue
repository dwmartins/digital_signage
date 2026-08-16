<script setup>
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import EmptyData from "@/components/shared/EmptyData.vue";
import TableSkeleton from "@/components/shared/TableSkeleton.vue";
import { useQueryFilters } from "@/composables/useQueryFilters";
import { showAlert } from "@/helpers/alert";
import { formatDateTime } from "@/helpers/date";
import transactionService from "@/services/transaction.service";
import { onMounted, reactive, ref } from "vue";

const items = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const perPage = ref(7);
const pagination = ref({});

const filters = reactive({
    global: { value: null, type: "string" },
    status: { value: null, type: "string" },
    type: { value: null, type: "string" },
});

const statusOptions = [
    { label: "Pendente", value: "pending" },
    { label: "Paga", value: "paid" },
    { label: "Falhou", value: "failed" },
    { label: "Estornada", value: "refunded" },
    { label: "Cancelada", value: "cancelled" },
];

const typeOptions = [
    { label: "Cobrança", value: "charge" },
    { label: "Estorno", value: "refund" },
];

const paymentMethodOptions = [
    { label: "PIX", value: "pix", icon: "pi pi-qrcode" },
    { label: "Cartão de crédito", value: "credit_card", icon: "pi pi-credit-card" },
    { label: "Cartão de débito", value: "debit_card", icon: "pi pi-credit-card" },
    { label: "Boleto bancário", value: "bank_slip", icon: "pi pi-barcode" },
    { label: "Transferência bancária", value: "bank_transfer", icon: "pi pi-building-columns" },
    { label: "Dinheiro", value: "cash", icon: "pi pi-money-bill" },
];

const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(
    filters,
    currentPage,
);

const money = (v) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(v ?? 0);

const label = (s) => statusOptions.find((x) => x.value === s)?.label ?? s;
const paymentMethod = (value) => paymentMethodOptions.find((option) => option.value === value)
    ?? { label: "Não informado", icon: "pi pi-minus-circle" };

const severity = (s) =>
    ({
        pending: "warn",
        paid: "success",
        failed: "danger",
        refunded: "info",
        cancelled: "secondary",
    })[s] ?? "secondary";

const fetchAll = async (page = 1) => {
    syncToRoute(page);
    try {
        loading.value = true;
        const r = await transactionService.index(
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

const clear = () => {
    Object.values(filters).forEach((x) => (x.value = null));
    fetchAll(1);
};

onMounted(() => {
    applyFromRoute();
    fetchAll(currentPage.value);
});

</script>
<template>
    <section class="container">
        <Breadcrumb
            :items="[{ icon: 'pi pi-home', to: '/' }, { label: 'Transações' }]"
        /><Card class="my-3"
            ><template #content
                ><form
                    class="row g-3 align-items-end"
                    @submit.prevent="fetchAll(1)"
                >
                    <div class="col-lg-4">
                        <div class="field">
                            <label>Buscar</label
                            ><InputText
                                v-model="filters.global.value"
                                placeholder="Cliente, provedor ou ID externo"
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2">
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
                    <div class="col-md-4 col-lg-2">
                        <div class="field">
                            <label>Tipo</label
                            ><Select
                                v-model="filters.type.value"
                                :options="typeOptions"
                                optionLabel="label"
                                optionValue="value"
                                showClear
                                fluid
                            />
                        </div>
                    </div>
                    <div class="col-lg-auto ms-lg-auto d-flex gap-2">
                        <Button
                            label="Limpar"
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
        <Card
            ><template #content
                ><TableSkeleton
                    v-show="loading"
                    :rows="perPage"
                    :columns="[
                        { bodyWidth: '70px' },
                        { bodyWidth: '160px' },
                        { bodyWidth: '150px' },
                        { bodyWidth: '100px' },
                        { bodyWidth: '90px' },
                        { bodyWidth: '140px' },
                        { bodyWidth: '130px' },
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
                    ><Column header="#" style="width: 75px"
                        ><template #body="{ data }"
                            >#{{ data.id }}</template
                        ></Column
                    ><Column header="Cliente" style="min-width: 200px"
                        ><template #body="{ data }"
                            ><div class="d-flex flex-column">
                                <strong
                                    >{{ data.customer?.name }}
                                    {{ data.customer?.last_name }}</strong
                                ><small class="text-muted">{{
                                    data.customer?.email
                                }}</small>
                            </div></template
                        ></Column
                    ><Column header="Campanha / Plano" style="min-width: 210px"
                        ><template #body="{ data }"
                            ><div class="d-flex flex-column">
                                <span>{{
                                    data.invoice?.subscription?.campaign
                                        ?.name ?? "-"
                                }}</span
                                ><small class="text-muted"
                                    >{{
                                        data.invoice?.subscription?.plan
                                            ?.name ?? "-"
                                    }}
                                    · Fatura {{ data.invoice?.number }}</small
                                >
                            </div></template
                        ></Column
                    ><Column header="Valor" style="min-width: 120px"
                        ><template #body="{ data }"
                            ><strong>{{ money(data.amount) }}</strong></template
                        ></Column
                    ><Column header="Pagamento" style="min-width: 190px"
                        ><template #body="{ data }"
                            ><Tag
                                :value="paymentMethod(data.payment_method).label"
                                :icon="paymentMethod(data.payment_method).icon"
                                severity="info" /></template></Column
                    ><Column header="Status" style="width: 110px"
                        ><template #body="{ data }"
                            ><Tag
                                :value="label(data.status)"
                                :severity="
                                    severity(data.status)
                                " /></template></Column
                    ><Column header="Criada em" style="min-width: 170px"
                        ><template #body="{ data }">{{
                            formatDateTime(data.created_at)
                        }}</template></Column
                    ></DataTable
                ><EmptyData
                    v-if="!loading && !items.length"
                    :show-btn-clean-filters="true"
                    @clean-filters="clear" /></template
        ></Card>
    </section>
</template>
