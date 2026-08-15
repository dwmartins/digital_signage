<script setup>
import { showAlert } from "@/helpers/alert";
import { formatDateTime } from "@/helpers/date";
import mediaService from "@/services/media.service";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({ modelValue: Boolean, media: Object });
const emit = defineEmits(["update:modelValue", "saved"]);
const saving = ref(false);
const loadingHistory = ref(false);
const history = ref([]);
const historyDialog = ref(false);
const fieldErrors = reactive({});
const options = [
    { label: "Aprovada", value: "approved" },
    { label: "Rejeitada", value: "rejected" },
];
const form = reactive({ approval_status: "approved", rejection_reason: null });
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const eventDetails = {
    created: { label: "Mídia criada", icon: "pi pi-plus", severity: "info" },
    details_updated: { label: "Dados alterados", icon: "pi pi-pencil", severity: "secondary" },
    file_replaced: { label: "Arquivo substituído", icon: "pi pi-refresh", severity: "warn" },
    approved: { label: "Mídia aprovada", icon: "pi pi-check-circle", severity: "success" },
    rejected: { label: "Mídia rejeitada", icon: "pi pi-times-circle", severity: "danger" },
    subscription_status_changed: { label: "Status da assinatura refletido", icon: "pi pi-sync", severity: "info" },
    detached_from_campaign: { label: "Desvinculada da campanha", icon: "pi pi-link", severity: "warn" },
    linked_to_campaign: { label: "Vinculada à campanha", icon: "pi pi-link", severity: "info" },
};
const fieldLabels = {
    name: "Nome", description: "Observação", original_name: "Arquivo", type: "Tipo",
    size_bytes: "Tamanho", width: "Largura", height: "Altura", duration_seconds: "Duração",
    approval_status: "Status", rejection_reason: "Motivo da rejeição", user_id: "Cliente",
};
const approvalLabels = {
    pending_approval: "Aguardando aprovação", awaiting_subscription: "Aguardando assinatura",
    approved: "Aprovada", rejected: "Rejeitada",
};

const eventInfo = (item) => eventDetails[item.event] ?? {
    label: "Mídia atualizada", icon: "pi pi-history", severity: "secondary",
};
const userName = (item) => item.user
    ? `${item.user.name} ${item.user.last_name ?? ""}`.trim()
    : "Sistema";
const formatValue = (field, value) => {
    if (value === null || value === undefined || value === "") return "Não informado";
    if (field === "approval_status") return approvalLabels[value] ?? value;
    if (field === "size_bytes") return `${(Number(value) / 1048576).toFixed(2)} MB`;
    if (field === "duration_seconds") return `${value}s`;
    return String(value);
};
const changes = (item) => Object.keys(fieldLabels)
    .filter((field) => item.old_values?.[field] !== item.new_values?.[field])
    .map((field) => ({ field, label: fieldLabels[field], from: item.old_values?.[field], to: item.new_values?.[field] }));

const fetchHistory = async (perPage = 3) => {
    if (!props.media?.id) return;
    try {
        loadingHistory.value = true;
        const response = await mediaService.history(props.media.id, perPage);
        history.value = response.data ?? [];
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loadingHistory.value = false;
    }
};

const openHistory = async () => {
    historyDialog.value = true;
    await fetchHistory(50);
};

const onSubmit = async () => {
    fieldErrors.rejection_reason =
        form.approval_status === "rejected" && !form.rejection_reason?.trim()
            ? "Informe o motivo da rejeição."
            : null;

    if (fieldErrors.rejection_reason) return;

    try {
        saving.value = true;
        const response = await mediaService.updateApproval(
            props.media.id,
            form,
        );
        showAlert("success", response.message);
        emit("saved", response.media);
        visible.value = false;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(
    () => props.modelValue,
    (opened) => {
        if (!opened) return;
        form.approval_status =
            props.media?.approval_status === "pending_approval"
                ? "approved"
                : props.media?.approval_status === "rejected"
                  ? "rejected"
                  : "approved";
        form.rejection_reason = props.media?.rejection_reason ?? null;
        fieldErrors.rejection_reason = null;
        historyDialog.value = false;
        fetchHistory(3);
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Analisar mídia"
        :style="{ width: '34rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
    >
        <form id="mediaApprovalForm" class="row g-3" @submit.prevent="onSubmit">
            <div class="col-12">
                <div class="field">
                    <label>Mídia</label>
                    <InputText :modelValue="media?.name" disabled fluid />
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label
                        ><span class="text-danger me-1">*</span>Decisão</label
                    >
                    <Select
                        v-model="form.approval_status"
                        :options="options"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div v-if="form.approval_status === 'rejected'" class="col-12">
                <div class="field">
                    <label
                        ><span class="text-danger me-1">*</span>Motivo da
                        rejeição</label
                    >
                    <Textarea
                        v-model="form.rejection_reason"
                        rows="4"
                        maxlength="5000"
                        :invalid="!!fieldErrors.rejection_reason"
                        autoResize
                        fluid
                    />
                    <small
                        v-if="fieldErrors.rejection_reason"
                        class="text-danger"
                        >{{ fieldErrors.rejection_reason }}</small
                    >
                </div>
            </div>
            <div class="col-12">
                <Divider align="left"><b>Histórico recente</b></Divider>
                <div v-if="loadingHistory" class="d-flex justify-content-center py-3">
                    <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="5" />
                </div>
                <div v-else-if="history.length" class="history-summary">
                    <div v-for="item in history" :key="item.id" class="history-summary-item">
                        <div class="history-marker"><i :class="eventInfo(item).icon"></i></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between gap-2 flex-wrap">
                                <strong>{{ eventInfo(item).label }}</strong>
                                <small class="text-muted">{{ formatDateTime(item.created_at) }}</small>
                            </div>
                            <small class="text-muted">Por {{ userName(item) }}</small>
                        </div>
                    </div>
                    <Button label="Ver histórico completo" icon="pi pi-history" text fluid @click="openHistory" />
                </div>
                <div v-else class="history-empty"><i class="pi pi-history"></i><span>Nenhum histórico registrado.</span></div>
            </div>
        </form>

        <Dialog v-model:visible="historyDialog" modal header="Histórico da mídia" appendTo="body"
            :style="{ width: '46rem' }" :breakpoints="{ '768px': '96vw' }" :draggable="false">
            <div class="history-header mb-3">
                <div class="history-media-icon"><i :class="media?.type === 'video' ? 'pi pi-video' : 'pi pi-image'"></i></div>
                <div><strong>{{ media?.name }}</strong><small class="d-block text-muted">Alterações, substituições e decisões de aprovação</small></div>
            </div>
            <div v-if="loadingHistory" class="d-flex justify-content-center py-5"><ProgressSpinner /></div>
            <div v-else class="history-timeline">
                <article v-for="item in history" :key="item.id" class="history-card">
                    <div class="history-card-icon" :class="`history-${eventInfo(item).severity}`"><i :class="eventInfo(item).icon"></i></div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div><strong>{{ eventInfo(item).label }}</strong><small class="d-block text-muted">{{ item.description }}</small></div>
                            <small class="history-date">{{ formatDateTime(item.created_at) }}</small>
                        </div>
                        <div class="history-user mt-2"><i class="pi pi-user"></i><span>{{ userName(item) }}</span><small v-if="item.user?.email">{{ item.user.email }}</small></div>
                        <div v-if="changes(item).length && item.event !== 'created'" class="changes-grid mt-3">
                            <div v-for="change in changes(item)" :key="change.field" class="change-item">
                                <small>{{ change.label }}</small>
                                <div><span>{{ formatValue(change.field, change.from) }}</span><i class="pi pi-arrow-right"></i><strong>{{ formatValue(change.field, change.to) }}</strong></div>
                            </div>
                        </div>
                    </div>
                </article>
                <div v-if="!history.length" class="history-empty"><i class="pi pi-history"></i><span>Nenhum histórico registrado.</span></div>
            </div>
        </Dialog>

        <template #footer>
            <Button
                label="Cancelar"
                severity="danger"
                class="p-button-text"
                :disabled="saving"
                @click="visible = false"
            />
            <Button
                label="Salvar decisão"
                icon="pi pi-check"
                :loading="saving"
                type="submit"
                form="mediaApprovalForm"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.min-width-0 { min-width: 0; }
.history-summary { overflow: hidden; border: 1px solid var(--p-content-border-color); border-radius: 10px; }
.history-summary-item { display: flex; align-items: center; gap: .75rem; padding: .7rem .85rem; border-bottom: 1px solid var(--p-content-border-color); }
.history-marker, .history-media-icon { display: grid; place-items: center; flex: 0 0 auto; border-radius: 9px; color: var(--p-primary-color); background: var(--p-primary-50); }
.history-marker { width: 34px; height: 34px; }
.history-media-icon { width: 44px; height: 44px; font-size: 1.2rem; }
.history-header, .history-user { display: flex; align-items: center; gap: .75rem; }
.history-timeline { display: flex; flex-direction: column; gap: .85rem; max-height: 65vh; overflow-y: auto; padding-right: .25rem; }
.history-card { display: flex; align-items: flex-start; gap: .85rem; padding: 1rem; border: 1px solid var(--p-content-border-color); border-radius: 12px; background: var(--p-content-background); }
.history-card-icon { display: grid; place-items: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 10px; }
.history-info { color: var(--p-blue-600); background: var(--p-blue-50); }
.history-success { color: var(--p-green-600); background: var(--p-green-50); }
.history-danger { color: var(--p-red-600); background: var(--p-red-50); }
.history-warn { color: var(--p-orange-600); background: var(--p-orange-50); }
.history-secondary { color: var(--p-text-muted-color); background: var(--p-surface-100); }
.history-date { padding: .25rem .5rem; border-radius: 6px; color: var(--p-text-muted-color); background: var(--p-surface-100); }
.history-user { color: var(--p-text-muted-color); font-size: .82rem; }
.history-user small::before { content: "•"; margin-right: .65rem; }
.changes-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
.change-item { min-width: 0; padding: .6rem; border-radius: 8px; background: var(--p-surface-50); }
.change-item > small { display: block; margin-bottom: .25rem; color: var(--p-text-muted-color); font-weight: 600; }
.change-item div { display: flex; align-items: center; gap: .4rem; min-width: 0; font-size: .78rem; }
.change-item span, .change-item strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.change-item span { color: var(--p-text-muted-color); text-decoration: line-through; }
.change-item i { flex: 0 0 auto; font-size: .65rem; }
.history-empty { display: flex; flex-direction: column; align-items: center; gap: .5rem; padding: 1.5rem; color: var(--p-text-muted-color); border: 1px dashed var(--p-content-border-color); border-radius: 10px; }
.history-empty i { font-size: 1.5rem; }
@media (max-width: 576px) { .changes-grid { grid-template-columns: 1fr; } .history-user { flex-wrap: wrap; } }
</style>
