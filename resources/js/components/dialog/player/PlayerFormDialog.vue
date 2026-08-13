<script setup>
import { showAlert } from '@/helpers/alert';
import playerService from '@/services/player.service';
import { computed, reactive, ref, watch } from 'vue';
const props = defineProps({ modelValue: Boolean, player: Object });
const emit = defineEmits(['update:modelValue', 'saved']);
const saving = ref(false);
const errors = reactive({});
const statuses = [{label:'Ativo',value:'active'},{label:'Manutenção',value:'maintenance'},{label:'Bloqueado',value:'blocked'},{label:'Estoque',value:'stock'}];
const defaults = () => ({id:null,name:'',code:'',hostname:'',brand:'',model:'',operating_system:'Linux',architecture:null,memory_mb:null,storage_mb:null,status:'active',notes:''});
const form = reactive(defaults());
const visible = computed({get:()=>props.modelValue,set:value=>emit('update:modelValue',value)});
const isUpdate = computed(()=>!!props.player?.id);
const submit = async () => {
    errors.name = form.name?.trim() ? null : 'Informe o nome.';
    errors.code = form.code?.trim() ? null : 'Informe o código.';
    if (errors.name || errors.code) return;
    try { saving.value=true; const response=isUpdate.value?await playerService.update(form):await playerService.create(form); showAlert('success',response.message); emit('saved'); visible.value=false; }
    catch(error){ showAlert('error',error.response?.data); } finally { saving.value=false; }
};
watch(()=>props.modelValue,open=>{if(!open)return;Object.assign(form,defaults(),props.player??{});form.memory_mb=form.memory_mb?Number(form.memory_mb):null;form.storage_mb=form.storage_mb?Number(form.storage_mb):null;});
</script>
<template>
<Dialog v-model:visible="visible" modal :style="{width:'58rem'}" :breakpoints="{'992px':'94vw'}" :draggable="false" :header="`${isUpdate?'Editar':'Adicionar'} player (PC)`">
<form id="playerForm" class="row g-4" @submit.prevent="submit">
<div class="col-12"><Divider align="left"><b>Identificação</b></Divider></div>
<div class="col-md-7"><div class="field"><label>Nome *</label><InputText v-model="form.name" :invalid="!!errors.name" fluid /></div></div>
<div class="col-md-5"><div class="field"><label>Código *</label><InputText v-model="form.code" @input="form.code=form.code.toUpperCase()" fluid /></div></div>
<div class="col-md-7"><div class="field"><label>Hostname</label><InputText v-model="form.hostname" fluid /></div></div>
<div class="col-md-5"><div class="field"><label>Status</label><Select v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" fluid /></div></div>
<div class="col-12"><Divider align="left"><b>Equipamento e sistema</b></Divider></div>
<div class="col-md-4"><div class="field"><label>Marca</label><InputText v-model="form.brand" fluid /></div></div>
<div class="col-md-4"><div class="field"><label>Modelo</label><InputText v-model="form.model" fluid /></div></div>
<div class="col-md-4"><div class="field"><label>Sistema operacional</label><InputText v-model="form.operating_system" fluid /></div></div>
<div class="col-md-4"><div class="field"><label>Arquitetura</label><Select v-model="form.architecture" :options="['x86_64','arm64','armv7']" showClear fluid /></div></div>
<div class="col-md-4"><div class="field"><label>Memória (MB)</label><InputNumber v-model="form.memory_mb" :min="1" fluid /></div></div>
<div class="col-md-4"><div class="field"><label>Armazenamento (MB)</label><InputNumber v-model="form.storage_mb" :min="1" fluid /></div></div>
<div class="col-12"><div class="field"><label>Observações</label><Textarea v-model="form.notes" rows="4" maxlength="5000" autoResize fluid /></div></div>
</form>
<template #footer><Button label="Cancelar" severity="danger" class="p-button-text" @click="visible=false"/><Button label="Salvar" icon="pi pi-check" :loading="saving" type="submit" form="playerForm"/></template>
</Dialog>
</template>
