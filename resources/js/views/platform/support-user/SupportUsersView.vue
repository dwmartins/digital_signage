<script setup>
import SupportUserDeleteDialog from '@/components/dialog/support-user/SupportUserDeleteDialog.vue';
import SupportUserFormDialog from '@/components/dialog/support-user/SupportUserFormDialog.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import EmptyData from '@/components/shared/EmptyData.vue';
import TableSkeleton from '@/components/shared/TableSkeleton.vue';
import { useQueryFilters } from '@/composables/useQueryFilters';
import { showAlert } from '@/helpers/alert';
import { formatDate } from '@/helpers/date';
import { copyItem } from '@/helpers/functions';
import supportUserService from '@/services/support-user.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router    = useRouter();

const breadcrumbItens = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Usuários suporte', to: '/platform/usuarios-suporte' },
];

const supportUsers = ref([]);
const loading      = ref(false);
const pagination   = ref({});
const currentPage  = ref(1);
const itemsPerPage = ref(7);

const supportUser = ref(null);

const dialogVisible = reactive({
    form: false,
    delete: false,
});

const filters = reactive({
    global: { value: null, type: 'string' },
    document: { value: null, type: 'string' },
    status: { value: null, type: 'string' },
});

const statusOptions = [
    { label: 'Ativo', value: 'active' },
    { label: 'Inativo', value: 'inactive' },
    { label: 'Bloqueado', value: 'blocked' },
];

const tableSkeletonColumns = [
    { headerWidth: '90px', bodyWidth: '190px' },
    { headerWidth: '90px', bodyWidth: '220px' },
    { headerWidth: '80px', bodyWidth: '130px' },
    { headerWidth: '80px', bodyWidth: '120px' },
    { headerWidth: '65px', bodyWidth: '88px', height: '22px', borderRadius: '999px', align: 'center' },
    { headerWidth: '80px', bodyWidth: '120px' },
    { headerWidth: '24px', shape: 'circle', size: '28px', align: 'center' },
];

const canCreateSupportUser = computed(() => authStore.hasPermission('support-users.create'));
const canUpdateSupportUser = computed(() => authStore.hasPermission('support-users.update'));
const canDeleteSupportUser = computed(() => authStore.hasPermission('support-users.delete'));

const { applyFromRoute, syncToRoute, buildApiFilters } = useQueryFilters(filters, currentPage);

onMounted(async () => {
    applyFromRoute();
    await fetchAll(currentPage.value);
});

const fetchAll = async (page) => {
    syncToRoute(page);

    try {
        loading.value = true;
        const response = await supportUserService.index(page, itemsPerPage.value, buildApiFilters());
        supportUsers.value = response.data ?? [];
        handlePagination(response);
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
}

const handlePagination = (response) => {
    pagination.value = response.pagination ?? {};
    currentPage.value = response.pagination?.current_page ?? 1;
};

const onPage = (event) => {
    const page = event.page + 1;
    itemsPerPage.value = event.rows;
    fetchAll(page);
};

const onSearch = () => {
    fetchAll(1);
};

const onClearSearch = () => {
    for (const key in filters) {
        filters[key].value = null;
    }

    fetchAll(1);
};

const statusLabel = (status) => {
    const labels = {
        active: 'Ativo',
        inactive: 'Inativo',
        blocked: 'Bloqueado',
    };

    return labels[status] ?? '-';
};

const statusSeverity = (status) => {
    const severities = {
        active: 'success',
        inactive: 'warn',
        blocked: 'danger',
    };

    return severities[status] ?? 'secondary';
};

const openPermissionsPage = (data) => {
    if (!canUpdateSupportUser.value) {
        return showAlert('warning', 'Você não possui permissão para configurar permissões.');
    }

    router.push({
        name: 'platform.support-users.permissions',
        params: { id: data.id },
    });
};

const openDialog = (dialogType, data = null) => {
    if (dialogType === 'form') {
        const allowed = data ? canUpdateSupportUser.value : canCreateSupportUser.value;

        if (!allowed) {
            return showAlert('warning', 'Você não possui permissão para realizar esta ação.');
        }
    }

    if (dialogType === 'delete' && !canDeleteSupportUser.value) {
        return showAlert('warning', 'Você não possui permissão para excluir usuários suporte.');
    }


    supportUser.value = null;
    supportUser.value = data ? { ...data } : null;
    dialogVisible[dialogType] = true;
};

const onCloseDialog = async () => {
    await fetchAll(currentPage.value);
};
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItens" />

        <div class="d-flex justify-content-end mb-3">
            <Button
                label="Novo usuário"
                icon="pi pi-plus"
                size="small"
                :disabled="!canCreateSupportUser"
                v-tooltip.bottom="!canCreateSupportUser ? 'Você não possui permissão para criar usuários suporte.' : null"
                @click="openDialog('form')"
            />
        </div>

        <Card class="mb-3">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="onSearch">
                    <div class="col-lg-4">
                        <div class="field">
                            <label for="global">Buscar</label>
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    id="global"
                                    v-model="filters.global.value"
                                    placeholder="Nome, sobrenome ou e-mail"
                                    fluid
                                />
                            </IconField>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="field">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <Select
                                id="status"
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
                    <div class="col-12 col-lg-auto ms-lg-auto d-grid d-sm-flex gap-2 justify-content-sm-end">
                        <Button
                            type="button"
                            label="Limpar"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            outlined
                            :loading="loading"
                            @click="onClearSearch"
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

        <Card>
            <template #content>
                <TableSkeleton
                    v-show="loading"
                    :rows="itemsPerPage"
                    :columns="tableSkeletonColumns"
                    class="mt-2 support-users-table-skeleton"
                />

                <DataTable
                    v-show="!loading && supportUsers.length"
                    :value="supportUsers"
                    :lazy="true"
                    :totalRecords="pagination.total"
                    :first="(currentPage - 1) * itemsPerPage"
                    :rows="itemsPerPage"
                    :paginatorDropdown="true"
                    :rowsPerPageOptions="[5, 7, 10, 20]"
                    @page="onPage"
                    paginator
                    scrollable
                    stripedRows
                    currentPageReportTemplate="Exibindo {first} a {last} de {totalRecords} usuários suporte"
                    class="mt-2"
                >
                    <Column field="name" header="Usuário" style="min-width: 230px">
                        <template #body="{ data }">
                            <div class="d-flex align-items-center gap-2">
                                <Avatar :image="data.avatar_url" shape="circle" />
                                <div class="d-flex flex-column">
                                    <strong>{{ data.full_name }}</strong>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="email" header="E-mail" style="min-width: 240px">
                        <template #body="{ data }">
                            <div class="d-flex align-items-center">
                                <Button
                                    icon="pi pi-copy"
                                    variant="text"
                                    aria-label="Copiar e-mail"
                                    severity="secondary"
                                    rounded
                                    @click="copyItem('E-mail', data.email)"
                                />
                                <span class="text-truncate d-inline-block" v-tooltip.top="data.email" style="max-width: 260px">
                                    {{ data.email }}
                                </span>
                            </div>
                        </template>
                    </Column>
                    <Column field="status" header="Status" style="width: 120px">
                        <template #body="{ data }">
                            <Tag
                                :value="statusLabel(data.status)"
                                :severity="statusSeverity(data.status)"
                            />
                        </template>
                    </Column>
                    <Column field="created_at" header="Criado em" style="min-width: 130px">
                        <template #body="{ data }">
                            {{ formatDate(data.created_at) }}
                        </template>
                    </Column>
                    <Column style="width: 152px">
                        <template #header>
                            <span class="w-100 text-center fw-semibold">Ações</span>
                        </template>
                        <template #body="{ data }">
                            <div class="d-flex align-items-center gap-1">
                                <Button
                                    icon="pi pi-pen-to-square"
                                    variant="text"
                                    rounded
                                    :disabled="!canUpdateSupportUser"
                                    v-tooltip.left="!canUpdateSupportUser ? 'Você não possui permissão para editar usuários suporte.' : 'Editar usuário'"
                                    @click="openDialog('form', data)"
                                />

                                <Button
                                    icon="pi pi-key"
                                    variant="text"
                                    rounded
                                    :disabled="!canUpdateSupportUser"
                                    v-tooltip.left="!canUpdateSupportUser ? 'Você não possui permissão para configurar permissões.' : 'Configurar permissões'"
                                    @click="openPermissionsPage(data)"
                                />

                                <Button
                                    icon="pi pi-trash"
                                    variant="text"
                                    severity="danger"
                                    rounded
                                    :disabled="!canDeleteSupportUser"
                                    v-tooltip.left="!canDeleteSupportUser ? 'Você não possui permissão para excluir usuários suporte.' : 'Excluir usuário'"
                                    @click="openDialog('delete', data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <EmptyData
                    v-if="!loading && !supportUsers.length"
                    @clean-filters="onClearSearch"
                    :show-btn-clean-filters="true"
                />
            </template>
        </Card>

        <SupportUserFormDialog
            v-model="dialogVisible.form"
            :support-user="supportUser"
            @saved="onCloseDialog"
        />

        <SupportUserDeleteDialog
            v-model="dialogVisible.delete"
            :support-user="supportUser"
            @deleted="onCloseDialog"
        />
    </section>
</template>
<style scoped>
:deep(.support-users-table-skeleton) {
    --table-skeleton-columns: minmax(230px, 1.2fr) 240px 140px 120px 120px 130px 80px;
    --table-skeleton-min-width: 1060px;
}
</style>