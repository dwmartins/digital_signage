<script setup>
import AlertBox from "@/components/shared/AlertBox.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import Spinner from "@/components/shared/Spinner.vue";
import { showAlert } from "@/helpers/alert";
import storageSettingService from "@/services/storage-setting.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, reactive, ref } from "vue";

const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const r2SecretConfigured = ref(false);
const awsSecretConfigured = ref(false);

const providers = [
    {
        value: "local",
        label: "Armazenamento local",
        description: "Mantém os arquivos no servidor da aplicação.",
        icon: "pi pi-server",
    },
    {
        value: "r2",
        label: "Cloudflare R2",
        description: "Armazenamento de objetos compatível com S3.",
        icon: "pi pi-cloud",
    },
    {
        value: "s3",
        label: "Amazon S3",
        description: "Armazena os arquivos em um bucket da AWS.",
        icon: "pi pi-box",
    },
];

const form = reactive({
    driver: "local",
    r2_account_id: "",
    r2_access_key_id: "",
    r2_secret_access_key: "",
    r2_bucket: "",
    r2_endpoint: "",
    aws_access_key_id: "",
    aws_secret_access_key: "",
    aws_region: "us-east-1",
    aws_bucket: "",
    aws_endpoint: "",
    aws_url: "",
    aws_use_path_style_endpoint: false,
});

const canUpdate = computed(() => auth.hasPermission("storage-settings.update"));
const selectedProvider = computed(() =>
    providers.find((provider) => provider.value === form.driver),
);
const generatedR2Endpoint = computed(() =>
    form.r2_account_id
        ? `https://${form.r2_account_id}.r2.cloudflarestorage.com`
        : "Informe o ID da conta para gerar o endpoint",
);

const fetchSetting = async () => {
    try {
        loading.value = true;
        const response = await storageSettingService.show();
        const setting = response.setting ?? {};

        Object.assign(form, {
            driver: setting.driver ?? "local",
            r2_account_id: setting.r2_account_id ?? "",
            r2_access_key_id: setting.r2_access_key_id ?? "",
            r2_secret_access_key: "",
            r2_bucket: setting.r2_bucket ?? "",
            r2_endpoint: setting.r2_endpoint ?? "",
            aws_access_key_id: setting.aws_access_key_id ?? "",
            aws_secret_access_key: "",
            aws_region: setting.aws_region ?? "us-east-1",
            aws_bucket: setting.aws_bucket ?? "",
            aws_endpoint: setting.aws_endpoint ?? "",
            aws_url: setting.aws_url ?? "",
            aws_use_path_style_endpoint: Boolean(setting.aws_use_path_style_endpoint),
        });
        r2SecretConfigured.value = Boolean(setting.r2_secret_configured);
        awsSecretConfigured.value = Boolean(setting.aws_secret_configured);
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    if (!canUpdate.value) {
        return showAlert("warning", "Você não possui permissão para alterar esta configuração.");
    }

    try {
        saving.value = true;
        const payload = { ...form };

        if (!payload.r2_secret_access_key) delete payload.r2_secret_access_key;
        if (!payload.aws_secret_access_key) delete payload.aws_secret_access_key;

        const response = await storageSettingService.update(payload);
        form.r2_secret_access_key = "";
        form.aws_secret_access_key = "";
        r2SecretConfigured.value = Boolean(response.setting?.r2_secret_configured);
        awsSecretConfigured.value = Boolean(response.setting?.aws_secret_configured);
        showAlert("success", response.message);
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        saving.value = false;
    }
};

onMounted(fetchSetting);
</script>

<template>
    <section class="container pb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-start gap-3 mb-4">
            <div>
                <Breadcrumb
                    :items="[
                        { icon: 'pi pi-home', to: '/' },
                        { label: 'Configurações' },
                        { label: 'Armazenamento' },
                    ]"
                />
                <div class="mt-3">
                    <h1 class="h4 fw-bold mb-1">Armazenamento de arquivos</h1>
                    <p class="text-muted mb-0">
                        Escolha onde os próximos arquivos de mídia serão armazenados.
                    </p>
                </div>
            </div>

            <Button
                label="Salvar configuração"
                icon="pi pi-check"
                size="small"
                class="align-self-end"
                :loading="saving"
                :disabled="loading || !canUpdate"
                @click="save"
            />
        </div>

        <Card v-if="loading" class="border-0 shadow-sm">
            <template #content>
                <div class="d-flex justify-content-center py-5">
                    <Spinner />
                </div>
            </template>
        </Card>

        <form v-else class="row g-4" @submit.prevent="save">
            <div class="col-12">
                <Card class="border-0 shadow-sm">
                    <template #title>
                        <span class="fs-6 fw-semibold">Destino dos novos arquivos</span>
                    </template>
                    <template #subtitle>
                        A alteração não move arquivos existentes e não interrompe o acesso às mídias antigas.
                    </template>
                    <template #content>
                        <div class="row g-3">
                            <div
                                v-for="provider in providers"
                                :key="provider.value"
                                class="col-12 col-md-4"
                            >
                                <button
                                    type="button"
                                    class="provider-card d-flex align-items-start gap-3 w-100 h-100 text-start rounded-3 p-3"
                                    :class="{ active: form.driver === provider.value }"
                                    :disabled="!canUpdate"
                                    @click="form.driver = provider.value"
                                >
                                    <span class="provider-icon flex-shrink-0">
                                        <i :class="provider.icon"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <strong class="d-block">{{ provider.label }}</strong>
                                        <small class="d-block text-muted mt-1">{{ provider.description }}</small>
                                    </span>
                                    <i
                                        v-if="form.driver === provider.value"
                                        class="pi pi-check-circle text-primary ms-auto"
                                    ></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="col-12 col-xl-8">
                <Card class="border-0 shadow-sm">
                    <template #title>
                        <span class="fs-6 fw-semibold">{{ selectedProvider?.label }}</span>
                    </template>
                    <template #content>
                        <AlertBox v-if="form.driver === 'local'" type="info">
                            Os arquivos serão mantidos em <strong>storage/app/private</strong> e continuarão acessíveis somente pelas rotas autenticadas da plataforma.
                        </AlertBox>

                        <div v-else-if="form.driver === 'r2'" class="row g-4">
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>ID da conta Cloudflare</label>
                                    <InputText v-model="form.r2_account_id" placeholder="Account ID" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Nome do bucket</label>
                                    <InputText v-model="form.r2_bucket" placeholder="meu-bucket" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Access Key ID</label>
                                    <InputText v-model="form.r2_access_key_id" autocomplete="off" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Secret Access Key</label>
                                    <Password
                                        v-model="form.r2_secret_access_key"
                                        :placeholder="r2SecretConfigured ? 'Chave configurada — deixe vazio para manter' : 'Informe a chave secreta'"
                                        :feedback="false"
                                        toggleMask
                                        autocomplete="new-password"
                                        fluid
                                    />
                                    <small v-if="r2SecretConfigured" class="d-block text-success mt-2">
                                        <i class="pi pi-lock me-1"></i>Chave secreta criptografada.
                                    </small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="field">
                                    <label>Endpoint personalizado</label>
                                    <InputText
                                        v-model="form.r2_endpoint"
                                        :placeholder="generatedR2Endpoint"
                                        fluid
                                    />
                                    <small class="text-muted">
                                        Opcional. Sem preenchimento será usado: {{ generatedR2Endpoint }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div v-else class="row g-4">
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Access Key ID</label>
                                    <InputText v-model="form.aws_access_key_id" autocomplete="off" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Secret Access Key</label>
                                    <Password
                                        v-model="form.aws_secret_access_key"
                                        :placeholder="awsSecretConfigured ? 'Chave configurada — deixe vazio para manter' : 'Informe a chave secreta'"
                                        :feedback="false"
                                        toggleMask
                                        autocomplete="new-password"
                                        fluid
                                    />
                                    <small v-if="awsSecretConfigured" class="d-block text-success mt-2">
                                        <i class="pi pi-lock me-1"></i>Chave secreta criptografada.
                                    </small>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Região</label>
                                    <InputText v-model="form.aws_region" placeholder="us-east-1" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Nome do bucket</label>
                                    <InputText v-model="form.aws_bucket" placeholder="meu-bucket" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label>Endpoint personalizado</label>
                                    <InputText v-model="form.aws_endpoint" placeholder="https://s3.amazonaws.com" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label>URL pública personalizada</label>
                                    <InputText v-model="form.aws_url" placeholder="https://cdn.exemplo.com" fluid />
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="status-panel d-flex justify-content-between align-items-center gap-3 rounded-3 p-3">
                                    <div>
                                        <strong class="d-block">Usar endpoint no formato path-style</strong>
                                        <small class="text-muted">Ative somente quando o serviço compatível com S3 exigir.</small>
                                    </div>
                                    <ToggleSwitch v-model="form.aws_use_path_style_endpoint" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="col-12 col-xl-4">
                <Card class="border-0 shadow-sm mb-4">
                    <template #title>
                        <span class="fs-6 fw-semibold">Como a troca funciona</span>
                    </template>
                    <template #content>
                        <div class="d-grid gap-3">
                            <div class="info-item d-flex gap-3">
                                <span>1</span>
                                <small>Arquivos novos usam o provedor selecionado.</small>
                            </div>
                            <div class="info-item d-flex gap-3">
                                <span>2</span>
                                <small>Cada mídia registra o disco e o caminho onde foi salva.</small>
                            </div>
                            <div class="info-item d-flex gap-3">
                                <span>3</span>
                                <small>Arquivos antigos permanecem no destino original e continuam acessíveis.</small>
                            </div>
                        </div>
                    </template>
                </Card>

                <AlertBox type="warning">
                    Salvar esta configuração não transfere arquivos existentes entre provedores.
                </AlertBox>
            </div>

            <div class="col-12 d-flex justify-content-end d-xl-none">
                <Button
                    label="Salvar configuração"
                    icon="pi pi-check"
                    type="submit"
                    :loading="saving"
                    :disabled="!canUpdate"
                />
            </div>
        </form>
    </section>
</template>

<style scoped>
.min-w-0 {
    min-width: 0;
}

.provider-card {
    appearance: none;
    border: 1px solid var(--p-content-border-color);
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        transform 0.2s ease;
}

.provider-card:not(:disabled) {
    cursor: pointer;
}

.provider-card:not(:disabled):hover {
    transform: translateY(-2px);
    border-color: var(--p-primary-color);
}

.provider-card.active {
    border-color: var(--p-primary-color);
    background: var(--p-primary-50);
}

.provider-icon {
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.status-panel {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-surface-50);
}

.info-item span {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
    font-weight: 700;
}

.info-item small {
    padding-top: 0.2rem;
    color: var(--p-text-muted-color);
}
</style>
