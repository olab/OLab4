<template>
    <ul class="nav-group">
        <slot />
    </ul>
</template>

<script>
    module.exports = {
        name: "NavGroup"
    }
</script>

<style lang="scss">
    .nav-group {
        margin: 0;
        padding: 0;
    }
</style>
