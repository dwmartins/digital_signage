<script setup>
import CustomerUserDeleteDialog from '@/components/dialog/customer-user/CustomerUserDeleteDialog.vue';
import CustomerUserFormDialog from '@/components/dialog/customer-user/CustomerUserFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { useQueryFilters } from '@/composables/useQueryFilters';
import { showAlert } from '@/helpers/alert';
import { formatDate } from '@/helpers/date';
import { copyItem } from '@/helpers/functions';
import customerUserService from '@/services/customer-user.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref } from 'vue';

const authStore = useAuthStore();
const customerUsers = ref([]);
const customerUser = ref(null);
const loading = ref(false);
const pagination = ref({});
const currentPage = ref(1);
const itemsPerPage = ref(7);
const dialogs = reactive({ form: false, delete: false });

const breadcrumbItens = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Clientes anunciantes', to: '/platform/clientes' },
];

const filters = reactive({
    global: { value: null, type: 'string' },
    status: { value: null, type: 'string' },
});

const statusOptions = [
    { label: 'Ativo', value: 'active' },
    { label: 'Inativo', value: 'inactive' },
    { label: 'Bloqueado', value: 'blocked' },
];

const skeletonColumns = [
    { headerWidth: '90px', bodyWidth: '190px' },
    { headerWidth: '90px', bodyWidth: '220px' },
    { headerWidth: '80px', bodyWidth: '130px' },
    { headerWidth: '65px', bodyWidth: '88px', height: '22px', borderRadius: '999px', align: 'center' },
    { headerWidth: '80px', bodyWidth: '120px' },
    { headerWidth: '24px', shape: 'circle', size: '28px', align: 'center' },
];

const canCreate = computed(() => authStore.hasPermission('customers.create'));
const canUpdate = computed(() => authStore.hasPermission('customers.update'));
const canDelete = computed(() => authStore.hasPermission('customers.delete'));
const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(filters, currentPage);

const fetchAll = async (page) => {
    syncToRoute(page);

    try {
        loading.value = true;
        const response = await customerUserService.index(page, itemsPerPage.value, buildApiFilters());
        customerUsers.value = response.data ?? [];
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

    customerUser.value = data ? { ...data } : null;
    dialogs[type] = true;
};

const onPage = event => {
    itemsPerPage.value = event.rows;
    fetchAll(event.page + 1);
};

const clearFilters = () => {
    Object.values(filters).forEach(filter => filter.value = null);
    fetchAll(1);
};

const statusLabel = status => ({ active: 'Ativo', inactive: 'Inativo', blocked: 'Bloqueado' })[status] ?? '-';
const statusSeverity = status => ({ active: 'success', inactive: 'warn', blocked: 'danger' })[status] ?? 'secondary';

onMounted(() => {
    applyFromRoute();
    fetchAll(currentPage.value);
});
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItens" />

        <div class="d-flex justify-content-end mb-3">
            <Button label="Novo cliente" icon="pi pi-plus" size="small" :disabled="!canCreate" @click="openDialog('form')" />
        </div>

        <Card class="mb-3"><template #content>
            <form class="row g-3 align-items-end" @submit.prevent="fetchAll(1)">
                <div class="col-lg-4"><div class="field">
                    <label for="global">Buscar</label>
                    <IconField><InputIcon class="pi pi-search" /><InputText id="global" v-model="filters.global.value" placeholder="Nome, sobrenome ou e-mail" fluid /></IconField>
                </div></div>
                <div class="col-md-6 col-lg-2"><div class="field">
                    <label for="status">Status</label>
                    <Select id="status" v-model="filters.status.value" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos" showClear fluid />
                </div></div>
                <div class="col-12 col-lg-auto ms-lg-auto d-grid d-sm-flex gap-2">
                    <Button type="button" label="Limpar" icon="pi pi-filter-slash" severity="secondary" outlined :loading="loading" @click="clearFilters" />
                    <Button type="submit" label="Filtrar" icon="pi pi-search" :loading="loading" />
                </div>
            </form>
        </template></Card>

        <Card><template #content>
            <TableSkeleton v-show="loading" :rows="itemsPerPage" :columns="skeletonColumns" class="mt-2" />

            <DataTable
                v-show="!loading && customerUsers.length"
                :value="customerUsers"
                :lazy="true"
                :totalRecords="pagination.total"
                :first="(currentPage - 1) * itemsPerPage"
                :rows="itemsPerPage"
                :rowsPerPageOptions="[5, 7, 10, 20]"
                @page="onPage"
                paginator
                scrollable
                stripedRows
                currentPageReportTemplate="Exibindo {first} a {last} de {totalRecords} clientes"
                class="mt-2"
            >
                <Column field="name" header="Cliente" style="min-width: 230px"><template #body="{ data }">
                    <div class="d-flex align-items-center gap-2">
                        <Avatar :image="data.avatar_url" shape="circle" />
                        <strong>{{ data.full_name }}</strong>
                    </div>
                </template></Column>
                <Column field="email" header="E-mail" style="min-width: 240px"><template #body="{ data }">
                    <div class="d-flex align-items-center">
                        <Button icon="pi pi-copy" variant="text" severity="secondary" rounded @click="copyItem('E-mail', data.email)" />
                        <span>{{ data.email }}</span>
                    </div>
                </template></Column>
                <Column field="phone" header="Telefone" style="min-width: 140px" />
                <Column field="status" header="Status" style="width: 120px"><template #body="{ data }">
                    <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                </template></Column>
                <Column field="created_at" header="Criado em" style="min-width: 130px"><template #body="{ data }">{{ formatDate(data.created_at) }}</template></Column>
                <Column style="width: 110px"><template #header><span class="w-100 text-center fw-semibold">Ações</span></template><template #body="{ data }">
                    <div class="d-flex justify-content-center gap-1">
                        <Button icon="pi pi-pen-to-square" variant="text" rounded :disabled="!canUpdate" v-tooltip.left="'Editar cliente'" @click="openDialog('form', data)" />
                        <Button icon="pi pi-trash" variant="text" severity="danger" rounded :disabled="!canDelete" v-tooltip.left="'Excluir cliente'" @click="openDialog('delete', data)" />
                    </div>
                </template></Column>
            </DataTable>

            <EmptyData v-if="!loading && !customerUsers.length" @clean-filters="clearFilters" :show-btn-clean-filters="true" />
        </template></Card>

        <CustomerUserFormDialog v-model="dialogs.form" :customer-user="customerUser" @saved="fetchAll(currentPage)" />
        <CustomerUserDeleteDialog v-model="dialogs.delete" :customer-user="customerUser" @deleted="fetchAll(currentPage)" />
    </section>
</template>
