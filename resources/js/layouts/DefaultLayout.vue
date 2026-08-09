<script setup>
import ThemeSettingsDialog from '@/components/shared/dialog/theme/ThemeSettingsDialog.vue';
import { showAlert } from '@/helpers/alert';
import { customerNavItems, platformNavItems } from '@/navigations/companyNavigation';
import authService from '@/services/auth.service';
import { useAuthStore } from '@/stores/authStore';
import { useThemeStore } from '@/stores/themeStore';
import { computed, reactive } from 'vue';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const props = defineProps({
    area: {
        type: String,
        required: true,
    },
});

const route     = useRoute();
const router    = useRouter();
const authStore = useAuthStore();
const theme     = useThemeStore();
const user      = authStore.getUser();

const menu = ref();
const sidebarOpen = ref(true);
const isMobile = ref(false);
const themeDialogVisible = ref(false);
const openNavGroups = reactive({});

const navItems = computed(() => {
    const items = {
        platform: platformNavItems,
        customer: customerNavItems,
    };

    return (items[props.area] || []).map(normalizeNavItem).filter(Boolean);
});

const isDarkMode = computed(() => theme.is_dark);

const accountMenuItems = computed(() => {
    const items = [];

    items.push(
        { label: 'Perfil', icon: 'pi pi-user', command: goToProfile },
        { separator: true },
        { label: 'Sair', icon: 'pi pi-sign-out', command: logout },
    );

    return items;
});

onMounted(() => {
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
});

function checkScreenSize() {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value) {
        sidebarOpen.value = false;
    }
}

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebarOnMobile() {
    if (isMobile.value) {
        sidebarOpen.value = false;
    }
}

async function changeTheme() {
    try {
        await theme.toggleTheme();
    } catch (error) {
        showAlert('error', error.response?.data);
    }
}

function isActive(item) {
    return route.path === item.to || route.path.startsWith(`${item.to}/`);
}

function toggleMenu(event) {
    menu.value.toggle(event);
}

function normalizeNavItem(item) {
    const children = item.children
        ?.map(normalizeNavItem)
        .filter(Boolean) ?? [];

    if (!canShowNavItem(item) && !children.length) {
        return null;
    }

    if (item.children && !children.length) {
        return null;
    }

    return {
        ...item,
        children,
    };
}

function canShowNavItem(item) {
    if (item.permission && !authStore.hasPermission(item.permission)) return false;

    return true;
}

function navGroupKey(item) {
    return item.to || item.label;
}

function isGroupActive(item) {
    return item.children?.some(child => isActive(child) || isGroupActive(child)) ?? false;
}

function isGroupOpen(item) {
    const key = navGroupKey(item);

    return openNavGroups[key] ?? isGroupActive(item);
}

function toggleNavGroup(item) {
    const key = navGroupKey(item);

    openNavGroups[key] = !isGroupOpen(item);
}

async function logout() {
    try {
        const response = await authService.logout();
        showAlert('success', response.message);
        closeSidebarOnMobile();
        router.push('/entrar');
    } catch (error) {
        showAlert('error', error.response?.data);
    }
}

function goToProfile() {
    closeSidebarOnMobile();
    router.push(profilePath());
}

</script>

<template>
    <div class="dashboard-container">
        <aside class="sidebar" :class="{ collapsed: !sidebarOpen }">
            <nav class="sidebar-menu">
                <template v-for="item in navItems" :key="navGroupKey(item)">
                    <button
                        v-if="item.children?.length"
                        type="button"
                        class="nav-item nav-group-toggle"
                        :class="{ active: isGroupActive(item) }"
                        @click="toggleNavGroup(item)"
                    >
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                        <i
                            class="pi pi-angle-down nav-group-arrow"
                            :class="{ open: isGroupOpen(item) }"
                        ></i>
                    </button>

                    <div
                        v-if="item.children?.length && isGroupOpen(item)"
                        class="nav-submenu"
                    >
                        <RouterLink
                            v-for="child in item.children"
                            :key="child.to"
                            :to="child.to"
                            class="nav-item nav-subitem"
                            :class="{ active: isActive(child) }"
                            @click="closeSidebarOnMobile"
                        >
                            <i :class="child.icon"></i>
                            <span>{{ child.label }}</span>
                        </RouterLink>
                    </div>

                    <RouterLink
                        v-else-if="!item.children?.length"
                        :to="item.to"
                        class="nav-item"
                        :class="{ active: isActive(item) }"
                        @click="closeSidebarOnMobile"
                    >
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                    </RouterLink>
                </template>
            </nav>
        </aside>

        <div class="main-content-area" :class="{ expanded: !sidebarOpen }">
            <div class="filter" v-if="isMobile && sidebarOpen" @click="toggleSidebar"></div>

            <header class="header">
                <div class="header-left">
                    <Button
                        @click="toggleSidebar"
                        icon="pi pi-align-justify"
                        variant="text"
                        severity="secondary"
                        rounded
                        size="large"
                    />
                </div>

                <div class="header-right">
                    <Button
                        icon="pi pi-palette"
                        variant="text"
                        severity="secondary"
                        size="large"
                        rounded
                        aria-label="Personalizar aparência"
                        v-tooltip.bottom="'Personalizar aparência'"
                        @click="themeDialogVisible = true"
                    />

                    <Button
                        :icon="isDarkMode ? 'pi pi-moon' : 'pi pi-sun'"
                        variant="text"
                        severity="secondary"
                        size="large"
                        rounded
                        @click="changeTheme"
                    />

                    <Button @click="toggleMenu" class="p-0" severity="secondary" text>
                        <div class="d-flex align-items-center gap-2">
                            <Avatar :image="user.avatar_url" shape="circle" class="border" />
                            <div class="d-flex flex-column align-items-start user-name">
                                <span>{{ user.full_name || user.name }}</span>
                            </div>
                            <i class="pi pi-angle-down"></i>
                        </div>
                    </Button>
                    <Menu ref="menu" :model="accountMenuItems" popup />
                </div>
            </header>

            <main class="main-content">
                <RouterView />
            </main>
        </div>

        <ThemeSettingsDialog v-model="themeDialogVisible" />
    </div>
</template>
<style scoped>
:global(:root) {
    --sidebar-width: 248px;
    --sidebar-bg: #102a2d;
    --header-height: 64px;
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
    background-color: var(--app-page-bg);
}

.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    color: #f8fafc;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    transition: transform 0.25s ease;
}

html.dark-mode .sidebar {
    background-color: var(--sidebar-bg);
}

.sidebar.collapsed {
    transform: translateX(-100%);
}

.sidebar-header {
    height: var(--header-height);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.brand-mark {
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    display: grid;
    place-items: center;
    background: var(--p-primary-500);
    border-radius: 8px;
}

.brand-mark--image {
    background: #fffffff5;
    overflow: hidden;
    padding: 4px;
}

.brand-mark img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.brand-name {
    flex: 1 1 auto;
    min-width: 0;
}

.brand-name strong {
    display: -webkit-box;
    font-size: 0.98rem;
    line-height: 1.15;
    overflow: hidden;
    overflow-wrap: anywhere;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.sidebar-header small {
    display: block;
    color: #a7f3d0;
    line-height: 1.2;
}

.sidebar-menu {
    padding: 12px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #cbd5e1;
    text-decoration: none;
    padding: 11px 12px;
    border-radius: 7px;
    margin-bottom: 4px;
    font-family: inherit;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    transition: background-color 0.2s, color 0.2s;
}

.nav-group-toggle {
    width: 100%;
    border: 0;
    background: transparent;
    text-align: left;
    appearance: none;
    cursor: pointer;
}

.nav-group-toggle span {
    flex: 1;
}

.nav-group-arrow {
    margin-left: auto;
    font-size: 0.8rem;
    transition: transform 0.2s;
}

.nav-group-arrow.open {
    transform: rotate(180deg);
}

.nav-submenu {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin: 0 0 6px 12px;
    padding-left: 10px;
    border-left: 1px solid rgba(255, 255, 255, 0.12);
}

.nav-subitem {
    padding: 9px 10px;
    font-size: 0.92rem;
}

.nav-item:hover,
.nav-item.active {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
}

.main-content-area {
    flex: 1;
    min-height: 100vh;
    margin-left: var(--sidebar-width);
    transition: margin-left 0.25s ease;
    width: calc(100% - var(--sidebar-width));
}

.main-content-area.expanded {
    margin-left: 0;
    width: 100%;
}

.header {
    height: var(--header-height);
    position: sticky;
    top: 0;
    z-index: 90;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    background: var(--app-header-bg);
    border-bottom: 1px solid var(--app-border-color);
}

.header-left,
.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.main-content {
    padding: 18px;
}

.filter {
    background-color: rgba(0, 0, 0, 0.45);
    position: fixed;
    inset: 0;
    z-index: 900;
}

.user-name {
    line-height: 1.25;
}

.user-name small {
    color: #64748b;
}

.header-company-name {
    max-width: 280px;
    line-height: 1.2;
    overflow: hidden;
    font-size: 0.78rem;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .main-content-area {
        margin-left: 0;
        width: 100%;
    }

    .header {
        padding: 0 12px;
    }

}

@media (max-width: 360px) {
    .user-name {
        display: none !important;
    }
}
</style>