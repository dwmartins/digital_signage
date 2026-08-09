<script setup>
import { showAlert } from '@/helpers/alert';
import profileService from '@/services/profile.service';
import { onMounted, ref } from 'vue';
import Card from 'primevue/card';
import Skeleton from 'primevue/skeleton';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

const sessions = ref([]);

const loading          = ref(false);
const removingSessions = ref(false);

onMounted(async () => {
    await fetchSessions();
});

const fetchSessions = async () => {
    try {
        loading.value = true;
        sessions.value = await profileService.getSessions();
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
};

const getDeviceIcon = (userAgent) => {
    if (!userAgent) return 'pi pi-desktop';
    const ua = userAgent.toLowerCase();
    if (ua.includes('mobile') || ua.includes('android') || ua.includes('iphone')) return 'pi pi-mobile';
    if (ua.includes('tablet') || ua.includes('ipad')) return 'pi pi-tablet';
    return 'pi pi-desktop';
};

const getBrowserName = (userAgent) => {
    if (!userAgent) return 'Desconhecido';
    if (userAgent.includes('Chrome') && !userAgent.includes('Edg'))  return 'Chrome';
    if (userAgent.includes('Firefox'))  return 'Firefox';
    if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
    if (userAgent.includes('Edg'))      return 'Edge';
    if (userAgent.includes('Opera'))    return 'Opera';
    return 'Desconhecido';
};

const formatLastActivity = (timestamp) => {
    return new Date(timestamp * 1000).toLocaleString('pt-BR', {
        dateStyle: 'long',
        timeStyle: 'short',
    });
};

const removeSession = async (session_id) => {
    try {
        removingSessions.value = true;
        const response = await profileService.removeSession(session_id);
        showAlert('success', response.message);

        sessions.value = sessions.value.filter(session => session.id !== session_id);
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        removingSessions.value = false;
    }
}
</script>

<template>
    <Card>
        <template #title>
            <div class="d-flex align-items-center gap-2">
                <i class="pi pi-desktop" />
                Sessões Ativas
            </div>
        </template>
        <template #content>

            <!-- Skeleton -->
            <div v-if="loading" class="d-flex flex-column gap-2">
                <div
                    v-for="i in 2"
                    :key="i"
                    class="d-flex align-items-center gap-3 p-3 rounded session-item"
                >
                    <Skeleton shape="circle" size="36px" />
                    <div class="d-flex flex-column gap-1 flex-grow-1">
                        <Skeleton width="140px" height="14px" />
                        <Skeleton width="200px" height="12px" />
                    </div>
                    <Skeleton width="60px" height="22px" border-radius="12px" />
                </div>
            </div>

            <!-- Sessions -->
            <div v-else class="d-flex flex-column gap-2 mt-2">
                <div
                    v-for="session in sessions"
                    :key="session.id"
                    class="d-flex align-items-center gap-3 p-3 rounded session-item"
                    :class="{ 'session-item--current': session.is_current }"
                >
                    <div class="session-icon d-flex align-items-center justify-content-center rounded-circle">
                        <i :class="getDeviceIcon(session.user_agent)" />
                    </div>

                    <div class="d-flex justify-content-between w-100">
                        <div class="d-flex flex-column flex-grow-1 overflow-hidden">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-medium fs-7">{{ getBrowserName(session.user_agent) }}</span>
                                <Tag
                                    v-if="session.is_current"
                                    value="Atual"
                                    severity="success"
                                    style="font-size: 11px; padding: 1px 6px;"
                                />
                            </div>
                            <div class="d-flex flex-wrap gap-2 gap-md-3 mt-1">
                                <span class="text-muted fs-8 text-truncate">
                                    <i class="pi pi-map-marker me-1" style="font-size: 10px;" />
                                    {{ session.ip_address ?? 'Desconhecido' }}
                                </span>
                                <span class="text-muted fs-8 text-nowrap">
                                    <i class="pi pi-clock me-1" style="font-size: 10px;" />
                                    {{ formatLastActivity(session.last_activity) }}
                                </span>
                            </div>
                        </div>
                        <div >
                            <Button
                                v-if="!session.is_current"
                                icon="pi pi-times"
                                severity="danger"
                                variant="outlined"
                                size="small"
                                v-tooltip.top="'Sair'"
                                :loading="removingSessions"
                                @click="removeSession(session.id)"
                            />
                        </div>
                    </div>
                </div>

                <p v-if="!sessions.length" class="text-muted fs-7 mb-0">
                    'Nenhum resultado encontrado.'
                </p>
            </div>

        </template>
    </Card>
</template>

<style scoped>
.session-item {
    border: 1px solid var(--p-content-border-color, #e5e7eb);
    transition: background 0.2s;
}

.session-item--current {
    border-color: var(--p-green-300, #86efac);
    background-color: var(--p-green-50, #f0fdf4);
}

html.dark-mode .session-item--current {
    background-color: #00f74a15;
}

.session-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    background-color: var(--p-content-hover-background, #f3f4f6);
    color: var(--p-text-muted-color, #6b7280);
    font-size: 15px;
}
</style>