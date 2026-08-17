<script setup>
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import AlertBox from "@/components/shared/AlertBox.vue";
import Spinner from "@/components/shared/Spinner.vue";
import { showAlert } from "@/helpers/alert";
import emailSettingService from "@/services/email-setting.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, reactive, ref } from "vue";

const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const passwordConfigured = ref(false);

const form = reactive({
    enabled: false,
    host: "",
    port: 587,
    encryption: "tls",
    username: "",
    password: "",
    from_address: "",
    from_name: "",
    timeout: 30,
});

const encryptionOptions = [
    { label: "TLS (recomendado)", value: "tls" },
    { label: "SSL", value: "ssl" },
    { label: "Sem criptografia", value: null },
];

const canUpdate = computed(() => auth.hasPermission("email-settings.update"));

const fetchSetting = async () => {
    try {
        loading.value = true;
        const response = await emailSettingService.show();
        const setting = response.setting ?? {};

        Object.assign(form, {
            enabled: Boolean(setting.enabled),
            host: setting.host ?? "",
            port: setting.port ?? 587,
            encryption: setting.encryption ?? null,
            username: setting.username ?? "",
            password: "",
            from_address: setting.from_address ?? "",
            from_name: setting.from_name ?? "",
            timeout: setting.timeout ?? 30,
        });
        passwordConfigured.value = Boolean(setting.password_configured);
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

        if (!payload.password) delete payload.password;

        const response = await emailSettingService.update(payload);
        passwordConfigured.value = Boolean(response.setting?.password_configured);
        form.password = "";
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
                        { label: 'Envio de e-mail' },
                    ]"
                />
                <div class="mt-3">
                    <h1 class="h4 fw-bold mb-1">Envio de e-mail</h1>
                    <p class="text-muted mb-0">
                        Configure o servidor responsável pelos e-mails transacionais da plataforma.
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

        <div v-else class="row g-4">
            <div class="col-12 col-xl-8">
                <Card class="border-0 shadow-sm">
                    <template #title>
                        <span class="fs-6 fw-semibold">Servidor SMTP</span>
                    </template>
                    <template #subtitle>
                        Os dados são armazenados no banco e aplicados sem depender do arquivo .env.
                    </template>
                    <template #content>
                        <form class="row g-4" @submit.prevent="save">
                            <div class="col-12">
                                <div class="status-panel d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 rounded-3 p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="status-icon" :class="form.enabled ? 'status-icon-active' : 'status-icon-inactive'">
                                            <i :class="form.enabled ? 'pi pi-send' : 'pi pi-pause'"></i>
                                        </span>
                                        <div>
                                            <strong class="d-block">Envio de e-mails</strong>
                                            <small class="text-muted">
                                                {{
                                                    form.enabled
                                                        ? "O SMTP será usado nos próximos envios da plataforma."
                                                        : "Nenhuma mensagem será enviada pelo servidor SMTP."
                                                }}
                                            </small>
                                        </div>
                                    </div>
                                    <ToggleSwitch
                                        v-model="form.enabled"
                                        class="align-self-end align-self-sm-center"
                                        :disabled="!canUpdate"
                                    />
                                </div>
                            </div>

                            <div class="col-12 col-md-8">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Servidor SMTP</label>
                                    <InputText
                                        v-model="form.host"
                                        placeholder="smtp.exemplo.com"
                                        autocomplete="off"
                                        fluid
                                    />
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Porta</label>
                                    <InputNumber v-model="form.port" :min="1" :max="65535" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label>Segurança da conexão</label>
                                    <Select
                                        v-model="form.encryption"
                                        :options="encryptionOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        fluid
                                    />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label>Tempo limite (segundos)</label>
                                    <InputNumber v-model="form.timeout" :min="1" :max="120" showButtons fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label>Usuário</label>
                                    <InputText
                                        v-model="form.username"
                                        placeholder="usuario@exemplo.com"
                                        autocomplete="off"
                                        fluid
                                    />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label>Senha</label>
                                    <Password
                                        v-model="form.password"
                                        :placeholder="passwordConfigured ? 'Senha configurada — deixe vazio para manter' : 'Informe a senha do SMTP'"
                                        :feedback="false"
                                        toggleMask
                                        autocomplete="new-password"
                                        fluid
                                    />
                                    <small v-if="passwordConfigured" class="d-block text-success mt-2">
                                        <i class="pi pi-lock me-1"></i>Existe uma senha criptografada configurada.
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <Divider />
                                <h2 class="fs-6 fw-semibold mb-1">Identificação do remetente</h2>
                                <p class="small text-muted mb-0">
                                    Estes dados aparecerão para quem receber as mensagens.
                                </p>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>Nome do remetente</label>
                                    <InputText v-model="form.from_name" placeholder="Nome da plataforma" fluid />
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field">
                                    <label><span class="text-danger me-1">*</span>E-mail do remetente</label>
                                    <InputText
                                        v-model="form.from_address"
                                        type="email"
                                        placeholder="noreply@exemplo.com"
                                        fluid
                                    />
                                </div>
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
                    </template>
                </Card>
            </div>

            <div class="col-12 col-xl-4">
                <Card class="border-0 shadow-sm mb-4">
                    <template #title>
                        <span class="fs-6 fw-semibold">Resumo da conexão</span>
                    </template>
                    <template #content>
                        <div class="connection-preview rounded-3 p-3">
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <span class="text-muted">Status</span>
                                <Tag
                                    :value="form.enabled ? 'Ativo' : 'Inativo'"
                                    :severity="form.enabled ? 'success' : 'secondary'"
                                />
                            </div>
                            <div class="summary-row">
                                <span>Servidor</span>
                                <strong class="text-break">{{ form.host || "Não informado" }}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Porta</span>
                                <strong>{{ form.port || "—" }}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Segurança</span>
                                <strong>{{ encryptionOptions.find((item) => item.value === form.encryption)?.label }}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Autenticação</span>
                                <strong>{{ form.username ? "Configurada" : "Sem autenticação" }}</strong>
                            </div>
                        </div>
                    </template>
                </Card>

                <AlertBox type="info">
                    A senha é criptografada antes de ser salva e nunca é enviada novamente ao navegador.
                </AlertBox>
            </div>
        </div>
    </section>
</template>

<style scoped>
.status-panel {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-surface-50);
}

.status-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
}

.status-icon-active {
    color: #16a34a;
    background: rgba(34, 197, 94, 0.12);
}

.status-icon-inactive {
    color: var(--p-text-muted-color);
    background: var(--p-surface-200);
}

.connection-preview {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-surface-50);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 0;
    border-top: 1px solid var(--p-content-border-color);
}

.summary-row span {
    color: var(--p-text-muted-color);
}

.summary-row strong {
    max-width: 65%;
    text-align: right;
}
</style>
