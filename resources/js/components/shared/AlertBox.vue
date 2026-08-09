<script setup>
defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (value) =>
            ['info', 'warning', 'success', 'danger'].includes(value)
    }
});
</script>

<template>
    <div class="alert-box" :class="`alert-${type}`">
        <slot></slot>
    </div>
</template>

<style scoped>
.alert-box {
    padding: 15px 10px;
    border-left: .25rem solid;
}

.alert-info {
    background-color: #cff4fc;
    border-color: #9eeaf9;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffe69c;
}

.alert-success {
    background-color: #afecd1;
    border-color: #96cab3;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f1aeb5;
}

html.dark-mode .alert-info {
    color: #6edff6;
    background-color: #032830;
}

html.dark-mode .alert-warning {
    color: #f3ce56;
    background-color: #fff3cd4f;
}

html.dark-mode .alert-success {
    color: #75b798;
    background-color: #051b11;
}

html.dark-mode .alert-danger {
    color: #ea868f;
    background-color: #2c0b0e;
}
</style>