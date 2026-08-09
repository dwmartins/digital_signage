<script setup>
import Spinner from '@/components/shared/Spinner.vue';
import { showAlert } from '@/helpers/alert';
import profileService from '@/services/profile.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, ref } from 'vue';

const authStore = useAuthStore();
const user      = authStore.user;

const loadingAvatar   = ref(false);
const uploadingAvatar = ref(false);

const avatarInput     = ref(null);
const avatarPreview   = ref(null);
const avatarFile      = ref(null);

const memberSince = computed(() =>
    new Date(user.created_at).toLocaleDateString('pt-BR', { dateStyle: 'long' })
);

const triggerAvatarInput = () => avatarInput.value?.click();

const onAvatarChange = async (event) => {
    const fileInput = event.target;
    const file = event.target.files[0];

    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    const fileSizeInMB = file.size / (1024 * 1024); // 2MB

    if (!file.type.startsWith('image/') || !allowedTypes.includes(file.type)) {
        showAlert('error', 'O arquivo selecionado não é uma imagem válida.');
        fileInput.value = '';
        return;
    }

    if (fileSizeInMB > 2) {
        showAlert('error', 'A imagem deve ter no máximo 2 MB.');
        fileInput.value = '';
        return;
    }

    avatarFile.value = file;
    uploadingAvatar.value = true;

    try {
        const readerResult = await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = () => resolve(null);
            reader.readAsDataURL(file);
        });

        if (readerResult) {
            avatarPreview.value = readerResult;
        }
    } catch (error) {
        showAlert('error', 'Falha ao processar a imagem.');
    } finally {
        uploadingAvatar.value = false;
    }
};

const onAvatarClear = () => {
    avatarFile.value = null;
    avatarPreview.value = null;
}

const saveAvatar = async () => {
    if (!avatarFile.value) return;
    try {
        loadingAvatar.value = true;
        const response = await profileService.updateAvatar(avatarFile.value);
        
        authStore.updateAvatar(response.data.avatar, response.data.avatar_url);
        
        onAvatarClear();
        showAlert('success', response.message);
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loadingAvatar.value = false;
    }
};
</script>

<template>
    <Card>
        <template #content>
            <div class="d-flex flex-column align-items-center text-center gap-3">

                <div class="avatar-wrapper position-relative">
                    <Avatar
                        :image="avatarPreview ?? user.avatar_url"
                        shape="circle"
                        style="width: 96px; height: 96px;"
                        :pt="{ image: { style: 'object-fit: cover; width: 100%; height: 100%;' } }"
                    />

                    <div v-if="uploadingAvatar" class="loading-avatar">
                        <Spinner/>
                    </div>

                    <button
                        class="avatar-edit-btn"
                        type="button"
                        @click="triggerAvatarInput"
                        v-tooltip.top="'Alterar foto'"
                    >
                        <i class="pi pi-camera" />
                    </button>
                    <input
                        ref="avatarInput"
                        type="file"
                        accept="image/*"
                        class="d-none"
                        id="new_avatar"
                        @change="onAvatarChange"
                    />
                </div>

                <div>
                    <h4 class="mb-1 fw-semibold">{{ user.full_name }}</h4>
                    <p class="text-muted mb-2 mt-0 fs-7">{{ user.email }}</p>
                    <Tag
                        :value="authStore.getRole()"
                        severity="info"
                        style="font-size: 12px; padding: 2px 8px;"
                    />
                </div>

                <div v-if="avatarPreview" class="d-flex gap-2">
                    <Button
                        icon="pi pi-times"
                        severity="danger"
                        size="small"
                        @click="onAvatarClear"
                    />
                    <Button
                        label="Salvar foto"
                        icon="pi pi-check"
                        severity="success"
                        size="small"
                        :loading="loadingAvatar"
                        @click="saveAvatar"
                    />
                </div>

                <Divider/>

                <div class="w-100 text-start d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between fs-7">
                        <span class="text-muted">Status</span>
                        <Tag
                            :value="user.status ? 'Ativo' : 'Inativo'"
                            :severity="user.status ? 'success' : 'danger'"
                            style="font-size: 11px; padding: 1px 6px;"
                        />
                    </div>
                    <div class="d-flex justify-content-between fs-7">
                        <span class="text-muted">Membro desde</span>
                        <span class="fw-medium">{{ memberSince }}</span>
                    </div>
                </div>

            </div>
        </template>
    </Card>
</template>

<style scoped>
.avatar-wrapper {
    display: inline-block;
}

.avatar-wrapper .loading-avatar {
    position: absolute;
    width: 96px;
    height: 96px;
    background-color: rgba(2, 2, 2, 0.473);
    top: 0;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid #fff;
    background: var(--p-primary-color, #6366f1);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: opacity 0.2s;
}

.avatar-edit-btn:hover {
    opacity: 0.85;
}
</style>