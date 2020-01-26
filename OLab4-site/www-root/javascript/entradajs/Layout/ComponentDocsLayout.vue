<template>
    <div class="ejs component-page">
        <main-header></main-header>

        <main-nav></main-nav>

        <main-content>
            <view-pane></view-pane>
        </main-content>
    </div>
</template>

<script>
    const VueMatchHeights = use('./../../../node_modules/vue-match-heights/dist/vue-match-heights.common.js');
    const VueTextareaAutosize = use('./../../../node_modules/vue-textarea-autosize/dist/vue-textarea-autosize.common.js');

    const ViewPane = use('./../EntradaJS/View/Component/ViewPane.vue');
    const MainHeader = use('./../Module/ComponentDocs/Component/Structure/MainHeader.vue');
    const MainNav = use('./../Module/ComponentDocs/Component/Structure/MainNav.vue');
    const MainContent = use('./../Module/ComponentDocs/Component/Structure/MainContent.vue');

    Vue.use(VueMatchHeights, {
        disabled: [600],
    });
    Vue.use(VueTextareaAutosize);

    module.exports = {
        name: 'component-page',
        components: {
            MainHeader, MainNav, MainContent, ViewPane
        }
    };
</script>
