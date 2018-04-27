<template>
    <div class="clinical-tabs">
        <breadcrumb :route_name="route_name" root_name="Clinical Experience" root_path="clinical.mylearners"></breadcrumb>

        <h1 class="no-margin space-below" v-i18n>Clinical Experience</h1>

        <ul class="nav nav-tabs space-above">
            <li><view-link to="clinical.mylearners">My Learners</view-link></li>
            <li><view-link to="clinical.rotationschedule">Rotation Schedule</view-link></li>
            <!--<li><view-link to="clinical.logbook">Logbook</view-link></li>-->
        </ul>
    </div>
</template>

<script>
    const importedComponents = {
        Breadcrumb: use('./Breadcrumb.vue'),
        ViewLink: use('EntradaJS/View/Component/ViewLink.vue')
    };

    module.exports = {
        name: 'clinical-tabs',
        props: ['href'],
        data () {
            return {
                route_name: this.$getRoute().getName(),
            }
        },
        components: importedComponents
    };
</script>

<style>
</style>