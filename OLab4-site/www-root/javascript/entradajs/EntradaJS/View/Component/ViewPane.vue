<template>
    <div class="view-pane">
        <component :is="currentView"></component>
    </div>
</template>

<script>
'use strict';module.exports={name:'view-pane',props:[],data:function data(){return{currentView:null}},methods:{setView:function setView(component){this.currentView=component}}};
</script>

<style>
</style>