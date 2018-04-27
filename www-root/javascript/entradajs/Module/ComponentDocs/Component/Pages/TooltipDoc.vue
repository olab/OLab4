<template>
    <div id="tooltips">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Tooltips</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Tooltips</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Tooltip.vue</strong></preformatted>

<preformatted title="Props"><strong>position</strong>: {
    type: String,
    default: 'top'
}</preformatted>
                </section>

                <alert><ul><li>The element that the tooltip is placed in must have the style <code>position: relative</code> or else the tooltip won't be positioned properly.</li></ul></alert>

                <section id="top">
                    <subheading>Top</subheading>

                    <well>
                        <btn href="#"><tooltip>Top Tooltip</tooltip>Hover Me</btn>

                        <preformatted>&lt;btn&gt;&lt;<strong>tooltip</strong>&gt;Text&lt;/<strong>tooltip</strong>&gt;Button Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="bottom">
                    <subheading>Bottom</subheading>

                    <well>
                        <btn href="#"><tooltip position="bottom">Bottom Tooltip</tooltip>Hover Me</btn>

                        <preformatted>&lt;btn&gt;&lt;<strong>tooltip</strong> <strong>position</strong>="<strong>bottom</strong>"&gt;Text&lt;/<strong>tooltip</strong>&gt;Button Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="left">
                    <subheading>Left</subheading>

                    <well>
                        <btn href="#"><tooltip position="left">Left Tooltip</tooltip>Hover Me</btn>

                        <preformatted>&lt;btn&gt;&lt;<strong>tooltip</strong> <strong>position</strong>="<strong>left</strong>"&gt;Text&lt;/<strong>tooltip</strong>&gt;Button Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="right">
                    <subheading>Right</subheading>

                    <well>
                        <btn href="#"><tooltip position="right">Right Tooltip</tooltip>Hover Me</btn>

                        <preformatted>&lt;btn&gt;&lt;<strong>tooltip</strong> <strong>position</strong>="<strong>right</strong>"&gt;Text&lt;/<strong>tooltip</strong>&gt;Button Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#top">Top</a>
                </list-item>

                <list-item>
                    <a href="#bottom">Bottom</a>
                </list-item>

                <list-item>
                    <a href="#left">Left</a>
                </list-item>

                <list-item>
                    <a href="#right">Right</a>
                </list-item>
            </list-group>
        </sidebar>
    </div>
</template>

<script>
    const importedComponents = {
        ContentInner: use('./../Structure/ContentInner.vue'),
        Sidebar: use('./../Structure/Sidebar.vue'),
        Container: use('EntradaJS/Components/Layout/Container.vue'),
        Alert: use('EntradaJS/Components/Alert.vue'),
        Btn: use('EntradaJS/Components/Button.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Tooltip: use('EntradaJS/Components/Tooltip.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "TooltipDocumentation",

        components: importedComponents,

        methods: {
            goBack () {
                window.history.length > 1
                ? this.$router.go(-1)
                : this.$router.push('/')
            }
        },

        created () {
            this.$emit('showing');
        },

        destroyed() {
            this.$emit('showing');
        }
    }
</script>

<style lang="scss">
    #tooltips {
        .btn {
            margin-right: 0;
            position: relative;
        }
    }
</style>
