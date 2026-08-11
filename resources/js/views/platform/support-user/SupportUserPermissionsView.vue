<script setup>
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import Spinner from '@/components/shared/Spinner.vue';
import { showAlert } from '@/helpers/alert';
import supportUserService from '@/services/support-user.service';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const supportUser = ref(null);
const loading = ref(false);
const saving = ref(false);
const catalog = ref({});
const selectedPermissions = ref([]);
const selectedPermission = ref(null);

const breadcrumbItens = computed(() => [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Usuários suporte', to: '/platform/usuarios-suporte' },
    { label: 'Permissões', to: route.fullPath },
]);

const groupedPermissions = computed(() => {
    return Object.entries(catalog.value).reduce((groups, [slug, permission]) => {
        const group = permission.group ?? 'outros';

        if (!groups[group]) groups[group] = [];

        groups[group].push({
            slug,
            name: permission.name,
            description: permission.description,
            group_label: permission.group_label,
        });

        return groups;
    }, {});
});

const selectedCount = computed(() => selectedPermissions.value.length);

const permissionDetailsVisible = computed({
    get: () => !!selectedPermission.value,
    set: (value) => {
        if (!value) selectedPermission.value = null;
    },
});

const fetchPermissions = async () => {
    try {
        loading.value = true;
        const response = await supportUserService.permissions(route.params.id);

        supportUser.value = response.data?.user ?? null;
        catalog.value = response.data?.catalog ?? {};
        selectedPermissions.value = response.data?.selected ?? [];
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
};

const allPermissions = () => Object.values(groupedPermissions.value).flat();

const groupSelectedCount = (permissions) => {
    return permissions.filter(permission => selectedPermissions.value.includes(permission.slug)).length;
};

const groupName = (group, permissions) => permissions[0]?.group_label ?? group;

const allSelected = (permissions) => {
    return permissions.length > 0
        && permissions.every(permission => selectedPermissions.value.includes(permission.slug));
};

const togglePermissions = (permissions) => {
    const slugs = permissions.map(permission => permission.slug);

    if (slugs.every(slug => selectedPermissions.value.includes(slug))) {
        selectedPermissions.value = selectedPermissions.value.filter(slug => !slugs.includes(slug));
        return;
    }

    selectedPermissions.value = Array.from(new Set([...selectedPermissions.value, ...slugs]));
};

const onSubmit = async () => {
    try {
        saving.value = true;
        const response = await supportUserService.updatePermissions(
            route.params.id,
            selectedPermissions.value,
        );

        showAlert('success', response.message);
        await fetchPermissions();
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

onMounted(fetchPermissions);
</script>

<template>
    <section class="container d-flex flex-column">
        <Breadcrumb :items="breadcrumbItens" />

        <div class="d-flex justify-content-end gap-2 mb-3">
            <Button
                label="Voltar"
                icon="pi pi-arrow-left"
                severity="secondary"
                size="small"
                outlined
                @click="router.push({ name: 'platform.support-users' })"
            />
            <Button
                :label="saving ? 'Aguarde...' : 'Salvar permissões'"
                icon="pi pi-check"
                :loading="saving"
                :disabled="loading"
                size="small"
                @click="onSubmit"
            />
        </div>

        <Card class="mb-3">
            <template #content>
                <div class="d-flex align-items-center gap-3">
                    <Avatar :image="supportUser?.avatar_url" shape="circle" size="large" />
                    <div class="d-flex flex-column">
                        <strong>{{ supportUser?.full_name || supportUser?.name || 'Usuário suporte' }}</strong>
                        <small class="text-muted">{{ supportUser?.email }}</small>
                    </div>
                    <Tag class="ms-auto" :value="`${selectedCount} permissões`" severity="info" />
                </div>
            </template>
        </Card>

        <div v-if="loading" class="d-flex justify-content-center py-5">
            <Spinner />
        </div>

        <Panel v-else toggleable>
            <template #header>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 w-100 pe-2">
                    <span class="fs-6 fw-semibold">Permissões da plataforma</span>
                    <Button
                        type="button"
                        :label="allSelected(allPermissions()) ? 'Desmarcar todos' : 'Marcar todos'"
                        size="small"
                        severity="primary"
                        outlined
                        icon="pi pi-list-check"
                        @click.stop="togglePermissions(allPermissions())"
                    />
                </div>
            </template>

            <div class="row g-3">
                <section
                    v-for="(permissions, group) in groupedPermissions"
                    :key="group"
                    class="col-12 col-lg-6 col-xxl-4"
                >
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">{{ groupName(group, permissions) }}</h2>
                                <small class="text-muted">
                                    {{ groupSelectedCount(permissions) }} de {{ permissions.length }} selecionadas
                                </small>
                            </div>

                            <Button
                                type="button"
                                :label="allSelected(permissions) ? 'Desmarcar todos' : 'Marcar todos'"
                                size="small"
                                severity="secondary"
                                outlined
                                @click="togglePermissions(permissions)"
                            />
                        </div>

                        <div class="row g-3">
                            <div v-for="permission in permissions" :key="permission.slug" class="col-12">
                                <div class="d-flex align-items-center gap-2 h-100 p-2 border rounded">
                                    <Checkbox
                                        v-model="selectedPermissions"
                                        :inputId="`permission-${permission.slug}`"
                                        :value="permission.slug"
                                    />
                                    <label
                                        class="flex-grow-1 min-w-0 cursor-pointer mb-0"
                                        :for="`permission-${permission.slug}`"
                                    >
                                        <span class="min-w-0 fs-7">{{ permission.name }}</span>
                                    </label>
                                    <Button
                                        type="button"
                                        icon="pi pi-eye"
                                        variant="text"
                                        severity="secondary"
                                        rounded
                                        size="small"
                                        aria-label="Ver detalhes da permissão"
                                        v-tooltip.top="'Ver detalhes'"
                                        @click="selectedPermission = permission"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </Panel>

        <Dialog
            v-model:visible="permissionDetailsVisible"
            modal
            :style="{ width: '34rem' }"
            :breakpoints="{ '576px': '94vw' }"
            :draggable="false"
            :header="selectedPermission?.name || 'Detalhes da permissão'"
        >
            <Panel :header="selectedPermission?.name || 'Detalhes da permissão'">
                <p class="m-0">
                    {{ selectedPermission?.description || 'Nenhuma descrição cadastrada para esta permissão.' }}
                </p>
            </Panel>

            <template #footer>
                <Button label="Fechar" severity="secondary" @click="permissionDetailsVisible = false" />
            </template>
        </Dialog>
    </section>
</template>
