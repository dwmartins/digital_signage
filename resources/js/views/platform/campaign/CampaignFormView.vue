<script setup>
import CampaignForm from "@/views/platform/campaign/CampaignForm.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import { showAlert } from "@/helpers/alert";
import campaignService from "@/services/campaign.service";
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import Spinner from "@/components/shared/Spinner.vue";

const route = useRoute();
const router = useRouter();
const campaign = ref(null);
const categories = ref([]);
const subscriptions = ref([]);
const displayPoints = ref([]);
const loading = ref(true);
const isUpdate = computed(() => !!route.params.id);

const fetchData = async () => {
    try {
        loading.value = true;
        const requests = [campaignService.options()];
        if (isUpdate.value) requests.push(campaignService.show(route.params.id));
        const [options, response] = await Promise.all(requests);
        categories.value = options.categories ?? [];
        subscriptions.value = options.subscriptions ?? [];
        displayPoints.value = options.display_points ?? [];
        campaign.value = response?.campaign ?? null;
    } catch (error) {
        showAlert("error", error.response?.data);
        router.replace({ name: "platform.campaigns" });
    } finally {
        loading.value = false;
    }
};

const back = () => router.push({ name: "platform.campaigns" });
const updateCampaign = (value) => { campaign.value = value; };

onMounted(fetchData);
</script>

<template>
    <section class="container">
        <div class="row align-items-center g-2 mb-3">
            <div class="col-12 col-md">
                <Breadcrumb
                    :items="[
                        { icon: 'pi pi-home', to: '/' },
                        { label: 'Campanhas', to: '/platform/campanhas' },
                        { label: isUpdate ? 'Editar campanha' : 'Nova campanha' },
                    ]"
                />
            </div>
            <div class="col-12 col-md-auto d-flex justify-content-end">
                <Button
                    label="Voltar"
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="back"
                />
            </div>
        </div>

        <div class="mb-4">
            <h2 class="mb-1">{{ isUpdate ? 'Editar campanha' : 'Nova campanha' }}</h2>
            <p class="text-muted mb-0">{{ isUpdate ? 'Gerencie a contratação, o conteúdo e a classificação da campanha.' : 'Vincule uma assinatura disponível e envie o primeiro conteúdo da campanha.' }}</p>
        </div>

        <Card v-if="loading">
            <template #content>
                <div class="d-flex justify-content-center py-5">
                    <Spinner/>
                </div>
            </template>
        </Card>
        <CampaignForm v-else :modelValue="true" :campaign="campaign" :categories="categories"
            :subscriptions="subscriptions" :displayPoints="displayPoints" @saved="back" @cancelled="back" @media-detached="updateCampaign" />
    </section>
</template>
