<template>
    <div id="alerts">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Alerts</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Alerts</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Alert.vue</strong></preformatted>

<preformatted title="Props"><strong>type</strong>: {
    type: String,
    default: 'info'
}</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <alert>Info alert</alert>

                        <preformatted>&lt;<strong>alert</strong>&gt;Text&lt;/<strong>alert</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="styles">
                    <subheading>Styles</subheading>
                        <well>
                            <alert type="success">Success alert</alert>

                            <alert type="danger">Danger alert</alert>

<preformatted>&lt;alert <strong>type</strong>="<strong>success</strong>"&gt;Text&lt;/alert&gt;

&lt;alert <strong>type</strong>="<strong>danger</strong>"&gt;Text&lt;/alert&gt;</preformatted>
                        </well>
                </section>

                <section id="list">
                    <subheading>Alerts with List Items</subheading>

                    <well>
                        <alert>
                            <ul>
                                <li>Info message 1</li>
                                <li>Info message 2</li>
                            </ul>
                        </alert>

                        <alert type="success">
                            <ul>
                                <li>Info message 1</li>
                                <li>Info message 2</li>
                            </ul>
                        </alert>

                        <alert type="danger">
                            <ul>
                                <li>Info message 1</li>
                                <li>Info message 2</li>
                            </ul>
                        </alert>

<preformatted>&lt;alert&gt;
    &lt;<strong>ul</strong>&gt;
        &lt;<strong>li</strong>&gt;Text&lt;/<strong>li</strong>&gt;
        &lt;<strong>li</strong>&gt;Text&lt;/<strong>li</strong>&gt;
    &lt;/<strong>ul</strong>&gt;
&lt;/alert&gt;</preformatted>
                    </well>
                </section>

                <section id="collapsible">
                    <subheading>Collapsible</subheading>

                    <well>
                        <alert :collapsible="true">
                            Info with collapsible content
                            <div slot="content">
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed mauris magna, vulputate nec velit in, tincidunt ullamcorper risus. Sed dapibus augue eget diam iaculis, sit amet hendrerit sem lobortis.</p>
                            </div>
                        </alert>

                        <alert :collapsible="true" type="success">
                            Info with collapsible content
                            <div slot="content">
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed mauris magna, vulputate nec velit in, tincidunt ullamcorper risus. Sed dapibus augue eget diam iaculis, sit amet hendrerit sem lobortis.</p>
                            </div>
                        </alert>

                        <alert :collapsible="true" type="danger">
                            Info with collapsible content
                            <div slot="content">
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed mauris magna, vulputate nec velit in, tincidunt ullamcorper risus. Sed dapibus augue eget diam iaculis, sit amet hendrerit sem lobortis.</p>
                            </div>
                        </alert>

<preformatted>&lt;alert <strong>:collapsible</strong>="<strong>true</strong>"&gt;
    Message
    &lt;<strong>div slot</strong>="<strong>content</strong>"&gt;
        // content
    &lt;/<strong>div</strong>&gt;
&lt;/alert&gt;</preformatted>
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
                    <a href="#styles">Styles</a>
                </list-item>

                <list-item>
                    <a href="#list">Alerts with List Items</a>
                </list-item>

                <list-item>
                    <a href="#collapsible">Collapsible</a>
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
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "AlertDocumentation",

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
