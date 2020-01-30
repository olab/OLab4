<template>
    <div id="badges">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Badges</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Badges</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Badge.vue</strong></preformatted>

<preformatted title="Props"><strong>type</strong>: {
    type: String,
    default: 'default'
},

<strong>large</strong>: {
    type: Boolean,
    default: false
}</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <badge>Default</badge>

                        <preformatted>&lt;<strong>badge</strong>&gt;Text&lt;/<strong>badge</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="types">
                    <subheading>Types</subheading>

                    <well>
                        <badge type="primary">Primary</badge>

                        <badge type="info">Info</badge>

                        <badge type="success">Success</badge>

                        <badge type="danger">Danger</badge>

<preformatted>&lt;badge <strong>type</strong>="<strong>primary</strong>"&gt;Text&lt;/badge&gt;

&lt;badge <strong>type</strong>="<strong>info</strong>"&gt;Text&lt;/badge&gt;

&lt;badge <strong>type</strong>="<strong>success</strong>"&gt;Text&lt;/badge&gt;

&lt;badge <strong>type</strong>="<strong>danger</strong>"&gt;Text&lt;/badge&gt;</preformatted>
                    </well>
                </section>

                <section id="large">
                    <subheading>Large</subheading>

                    <well>
                        <badge :large="true">Default</badge>

                        <badge :large="true" type="primary">Primary</badge>

                        <badge :large="true" type="info">Info</badge>

                        <badge :large="true" type="success">Success</badge>

                        <badge :large="true" type="danger">Danger</badge>

                        <preformatted>&lt;badge <strong>:large</strong>="<strong>true</strong>"&gt;Text&lt;/badge&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#default">Default</a>
                </list-item>

                <list-item>
                    <a href="#types">Types</a>
                </list-item>

                <list-item>
                    <a href="#large">Large</a>
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
        Badge: use('EntradaJS/Components/Badge.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "BadgeDocumentation",

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
    @import 'theme.scss';

    #badges {
        .well {
            .preformatted {
                margin-top: $spacing-lg;
            }
        }
    }
</style>
