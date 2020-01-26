<template>
    <div class="ejs debug-layout">
        <div class="breadcrumb">
            <li class="active"><span class="divider">/</span> EntradaJS Dev Environment</li>
        </div>
        <view-pane></view-pane>
    </div>
</template>

<script>
    const ViewPane = use('./../EntradaJS/View/Component/ViewPane.vue');

    module.exports = {
        name: 'debug-layout',
        components: {
            ViewPane
        }
    };
</script>

<style>
    body {
        background-color: #e8edf1;
        padding: 20px;
    }

    .debug-layout {
        background-color: #fff;
        border: 1px solid #d9dee2;
        border-radius: 5px;
        padding: 20px;
    }
</style>
