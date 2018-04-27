<template>
    <div id="buttons">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Buttons</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Buttons</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Button.vue</strong></preformatted>

<preformatted title="Props"><strong>link</strong>: {
    type: String,
    required: true
},

<strong>type</strong>: {
    type: String,
    default: 'default'
},

<strong>tooltip</strong>: {
    type: String
},

<strong>tooltipPosition</strong>: {
    type: String,
    default: 'top'
}

<strong>small</strong>: {
    type: Boolean,
    default: false
},

<strong>prependIcon</strong>: {
    type: String,
    default: 'default'
},

<strong>appendIcon</strong>: {
    type: String,
    default: 'default'
}</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <btn>Default</btn>

                        <preformatted>&lt;<strong>btn</strong>&gt;Text&lt;/<strong>btn</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="link">
                    <subheading>Link</subheading>

                    <well>
                        <btn link="#">Link</btn>

                        <preformatted>&lt;<strong>btn</strong> <strong>link</strong>="#"&gt;Text&lt;/<strong>btn</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="types">
                    <subheading>Types</subheading>

                    <well>
                        <btn type="primary">Primary</btn>

                        <btn type="success">Success</btn>

                        <btn type="info">Info</btn>

                        <btn type="danger">Danger</btn>

                        <btn type="disabled">Disabled</btn>

<preformatted>&lt;btn <strong>type</strong>="<strong>primary</strong>"&gt;Text&lt;/btn&gt;

&lt;btn <strong>type</strong>="<strong>success</strong>"&gt;Text&lt;/btn&gt;

&lt;btn <strong>type</strong>="<strong>info</strong>"&gt;Text&lt;/btn&gt;

&lt;btn <strong>type</strong>="<strong>danger</strong>"&gt;Text&lt;/btn&gt;

&lt;btn <strong>type</strong>="<strong>disabled</strong>"&gt;Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="tooltip">
                    <subheading>Tooltip</subheading>

                    <well>
                        <btn tooltip="Tooltip">Top</btn>

                        <btn tooltip="Tooltip" tooltip-position="bottom">Bottom</btn>

                        <btn tooltip="Tooltip" tooltip-position="left">Left</btn>

                        <btn tooltip="Tooltip" tooltip-position="right">Right</btn>

                        <preformatted>&lt;btn <strong>tooltip</strong>="<strong>Text</strong>"&gt;Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="small">
                    <subheading>Small</subheading>

                    <well>
                        <btn :small="true">Default</btn>

                        <btn :small="true" type="primary">Primary</btn>

                        <btn :small="true" type="success">Success</btn>

                        <btn :small="true" type="info">Info</btn>

                        <btn :small="true" type="danger">Danger</btn>

                        <btn :small="true" type="disabled">Disabled</btn>

                        <preformatted>&lt;btn <strong>:small</strong>="<strong>true</strong>"&gt;Text&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="groups">
                    <subheading>Groups</subheading>

                    <well>
                        <btn-group>
                            <btn>Button 1</btn>
                            <btn>Button 2</btn>
                            <btn>Button 3</btn>
                        </btn-group>

<preformatted>&lt;<strong>btn-group</strong>&gt;
    &lt;<strong>btn</strong>&gt;Text&lt;/<strong>btn</strong>&gt;
    &lt;<strong>btn</strong>&gt;Text&lt;/<strong>btn</strong>&gt;
    &lt;<strong>btn</strong>&gt;Text&lt;/<strong>btn</strong>&gt;
&lt;/<strong>btn-group</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="icons">
                    <subheading>Icons</subheading>

                    <well>
                        <btn type="success" prepend-icon="fa fa-plus">Prepend Icon</btn>

                        <btn type="danger" append-icon="fa fa-times">Append Icon</btn>

<preformatted>&lt;btn <strong>prepend-icon</strong>="<strong>fa fa-plus</strong>"&gt;Text&lt;/btn&gt;

&lt;btn <strong>append-icon</strong>="<strong>fa fa-times</strong>"&gt;Text&lt;/btn&gt;</preformatted>
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
                    <a href="#tooltip">Tooltip</a>
                </list-item>

                <list-item>
                    <a href="#small">Small</a>
                </list-item>

                <list-item>
                    <a href="#groups">Groups</a>
                </list-item>

                <list-item>
                    <a href="#icons">Icons</a>
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
        Btn: use('EntradaJS/Components/Button.vue'),
        BtnGroup: use('EntradaJS/Components/ButtonGroup.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "ButtonDocumentation",

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
    @import 'theme';

    #buttons {
        .well {
            .preformatted {
                margin-top: $spacing-lg;
            }
        }
    }
</style>
