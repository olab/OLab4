<template>
    <div class="no-components-layout">
        <view-pane></view-pane>
    </div>
</template>

<script>
    const ViewPane = use('EntradaJS/View/Component/ViewPane.vue');

    module.exports = {
        name: 'no-components-layout',
        components: {
            ViewPane
        }
    };
</script>

<style>

</style>