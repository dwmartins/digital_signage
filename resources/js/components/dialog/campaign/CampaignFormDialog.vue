<script setup>
import { showAlert } from "@/helpers/alert";
import campaignService from "@/services/campaign.service";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    modelValue: Boolean,
    campaign: Object,
    customers: Array,
    categories: Array,
    plans: Array,
    mediaAssets: Array,
});
const emit = defineEmits(["update:modelValue", "saved"]);
const saving = ref(false);
const hydrating = ref(false);
const errors = reactive({});
const defaults = () => ({
    id: null,
    user_id: null,
    plan_id: null,
    name: "",
    description: "",
    category_ids: [],
    media_asset_id: null,
});
const form = reactive(defaults());
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
const isUpdate = computed(() => !!props.campaign?.id);
const customerMedia = computed(() =>
    props.mediaAssets.filter(
        (media) =>
            Number(media.user_id) === Number(form.user_id) &&
            media.type === selectedPlan.value?.media_type,
    ),
);
const selectedPlan = computed(() =>
    props.plans.find((plan) => plan.id === form.plan_id),
);
const money = (value) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value ?? 0);
const formatSize = (bytes) =>
    bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.ceil(bytes / 1024)} KB`;

const validate = () => {
    errors.user_id = form.user_id ? null : "Selecione o cliente.";
    errors.plan_id = form.plan_id ? null : "Selecione o plano.";
    errors.name = form.name?.trim() ? null : "Informe o nome.";
    errors.media_asset_id = form.media_asset_id
        ? null
        : "Selecione uma mídia compatível com o plano.";
    return !Object.values(errors).some(Boolean);
};

const submit = async () => {
    if (!validate()) return;
    try {
        saving.value = true;
        const response = isUpdate.value
            ? await campaignService.update(form)
            : await campaignService.create(form);
        showAlert("success", response.message);
        emit("saved");
        visible.value = false;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(
    () => form.user_id,
    (value, oldValue) => {
        if (!hydrating.value && oldValue && value !== oldValue)
            form.media_asset_id = null;
    },
);

watch(
    () => form.plan_id,
    (value, oldValue) => {
        if (!hydrating.value && oldValue && value !== oldValue)
            form.media_asset_id = null;
    },
);

watch(
    () => props.modelValue,
    (opened) => {
        if (!opened) return;
        hydrating.value = true;
        const campaign = props.campaign;
        Object.assign(form, defaults(), campaign ?? {}, {
            category_ids:
                campaign?.categories?.map((category) => category.id) ?? [],
            media_asset_id: campaign?.media_assets?.[0]?.id ?? null,
            plan_id: campaign?.subscription?.plan_id ?? null,
        });
        Object.keys(errors).forEach((key) => delete errors[key]);
        setTimeout(() => (hydrating.value = false), 0);
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="`${isUpdate ? 'Editar' : 'Nova'} campanha`"
        :style="{ width: '58rem' }"
        :breakpoints="{ '768px': '96vw' }"
        :draggable="false"
    >
        <form id="campaignForm" class="row g-4" @submit.prevent="submit">
            <div class="col-12">
                <Divider align="left"><b>Cliente e identificação</b></Divider>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label
                        ><span class="text-danger me-1">*</span>Cliente
                        anunciante</label
                    >
                    <Select
                        v-model="form.user_id"
                        :options="customers"
                        optionLabel="full_name"
                        optionValue="id"
                        filter
                        :invalid="!!errors.user_id"
                        fluid
                    >
                        <template #option="{ option }"
                            ><div class="d-flex flex-column">
                                <span
                                    >#{{ option.id }} -
                                    {{ option.full_name }}</span
                                ><small class="text-muted">{{
                                    option.email
                                }}</small>
                            </div></template
                        >
                    </Select>
                    <small v-if="errors.user_id" class="text-danger">{{
                        errors.user_id
                    }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label
                        ><span class="text-danger me-1">*</span>Nome da
                        campanha</label
                    >
                    <InputText
                        v-model="form.name"
                        maxlength="255"
                        :invalid="!!errors.name"
                        fluid
                    />
                    <small v-if="errors.name" class="text-danger">{{
                        errors.name
                    }}</small>
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label>Observação</label
                    ><Textarea
                        v-model="form.description"
                        rows="3"
                        maxlength="5000"
                        autoResize
                        fluid
                    />
                </div>
            </div>

            <div class="col-12 order-2">
                <Divider align="left"><b>Conteúdo e classificação</b></Divider>
            </div>
            <div class="col-md-6 order-2">
                <div class="field">
                    <label>Categorias</label>
                    <MultiSelect
                        v-model="form.category_ids"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Opcional: selecione uma ou mais"
                        filter
                        display="chip"
                        fluid
                    />
                    <small class="text-muted"
                        >A campanha pode ficar sem categoria.</small
                    >
                </div>
            </div>
            <div class="col-md-6 order-2">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Mídia</label>
                    <Select
                        v-model="form.media_asset_id"
                        :options="customerMedia"
                        optionLabel="name"
                        optionValue="id"
                        :disabled="!form.user_id || !form.plan_id"
                        :placeholder="
                            form.user_id && form.plan_id
                                ? 'Selecione uma mídia'
                                : 'Selecione primeiro o cliente e o plano'
                        "
                        filter
                        :invalid="!!errors.media_asset_id"
                        fluid
                    >
                        <template #option="{ option }"
                            ><div class="d-flex flex-column">
                                <span
                                    ><i
                                        :class="
                                            option.type === 'image'
                                                ? 'pi pi-image'
                                                : 'pi pi-video'
                                        "
                                        class="me-2"
                                    ></i
                                    >{{ option.name }}</span
                                ><small class="text-muted"
                                    >{{ option.original_name }} ·
                                    {{ formatSize(option.size_bytes) }}</small
                                >
                            </div></template
                        >
                    </Select>
                    <small v-if="errors.media_asset_id" class="text-danger">{{
                        errors.media_asset_id
                    }}</small>
                    <small
                        v-else-if="form.user_id && !customerMedia.length"
                        class="text-warning"
                        >Este cliente não possui mídia compatível com o
                        plano.</small
                    >
                </div>
            </div>

            <div class="col-12 order-1">
                <Divider align="left"><b>Contratação</b></Divider>
            </div>
            <div class="col-md-7 order-1">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Plano</label>
                    <Select
                        v-model="form.plan_id"
                        :options="plans"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        :invalid="!!errors.plan_id"
                        fluid
                    >
                        <template #option="{ option }"
                            ><div
                                class="d-flex justify-content-between gap-4 w-100"
                            >
                                <span
                                    >{{ option.name }} ·
                                    {{ option.screen_limit }} telas</span
                                ><strong
                                    >{{ money(option.price) }} /
                                    {{
                                        option.billing_cycle === "yearly"
                                            ? "ano"
                                            : "mês"
                                    }}</strong
                                >
                            </div></template
                        >
                    </Select>
                    <small v-if="errors.plan_id" class="text-danger">{{
                        errors.plan_id
                    }}</small>
                </div>
            </div>
            <div v-if="selectedPlan" class="col-md-5 order-1">
                <div class="plan-summary h-100">
                    <strong>{{ money(selectedPlan.price) }}</strong
                    ><span>{{
                        selectedPlan.billing_cycle === "yearly"
                            ? "Cobrança anual"
                            : "Cobrança mensal"
                    }}</span
                    ><small
                        >{{
                            selectedPlan.media_type === "video"
                                ? "Vídeo"
                                : "Imagem"
                        }}
                        · até {{ selectedPlan.screen_limit }} telas</small
                    >
                </div>
            </div>
            <div class="col-12 order-1">
                <Message severity="info" :closable="false"
                    >Ao salvar, uma assinatura pendente será criada
                    automaticamente para esta campanha.</Message
                >
            </div>
        </form>
        <template #footer
            ><Button
                label="Cancelar"
                severity="danger"
                text
                :disabled="saving"
                @click="visible = false" /><Button
                label="Salvar campanha"
                icon="pi pi-check"
                type="submit"
                form="campaignForm"
                :loading="saving"
        /></template>
    </Dialog>
</template>

<style scoped>
.plan-summary {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 1rem 1.25rem;
    border: 1px solid var(--p-primary-200);
    border-radius: 12px;
    background: var(--p-primary-50);
}
.plan-summary strong {
    color: var(--p-primary-color);
    font-size: 1.35rem;
}
.plan-summary span {
    font-weight: 600;
}
.plan-summary small {
    color: var(--p-text-muted-color);
}
</style>
