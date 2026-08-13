<script setup>
import {showAlert} from '@/helpers/alert';import playerService from '@/services/player.service';import{computed,ref}from'vue';
const props=defineProps({modelValue:Boolean,player:Object});const emit=defineEmits(['update:modelValue','deleted']);const deleting=ref(false);const visible=computed({get:()=>props.modelValue,set:v=>emit('update:modelValue',v)});
const remove=async()=>{try{deleting.value=true;const r=await playerService.destroy(props.player.id);showAlert('success',r.message);emit('deleted');visible.value=false}catch(e){showAlert('error',e.response?.data)}finally{deleting.value=false}};
</script>
<template><Dialog v-model:visible="visible" modal :style="{width:'32rem'}" header="Excluir player (PC)"><p>Deseja excluir <strong>{{player?.name}}</strong>?</p><template #footer><Button label="Cancelar" severity="secondary" text @click="visible=false"/><Button label="Excluir" severity="danger" icon="pi pi-trash" :loading="deleting" @click="remove"/></template></Dialog></template>
