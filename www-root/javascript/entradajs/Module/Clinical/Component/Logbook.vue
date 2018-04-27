<template>
    <div class="published-schedules">
        <clinical-tabs href="logbook"></clinical-tabs>
        <cohort-select></cohort-select>
    </div>
</template>

<script>
    const CohortSelect = use('./CohortSelect.vue');
    const ClinicalTabs = use('./Clinical.vue');

    module.exports = {
        name: 'logbook',
        props: ['title'],
        components: {
            CohortSelect,
            ClinicalTabs,
        }
    };
</script>

<style>
</style>