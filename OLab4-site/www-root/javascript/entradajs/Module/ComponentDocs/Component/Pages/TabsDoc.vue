<template>
    <div id="tabs">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Tabs</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Tabs</page-heading>

<preformatted title="Required Components">path/to/<strong>components/Tabs.vue</strong>
path/to/<strong>components/Tab.vue</strong>
path/to/<strong>components/layout/Container.vue</strong></preformatted>

<preformatted title="Props"><strong>name</strong>: {
    type: String,
    required: true
},

<strong>badge</strong>: {
    type: String
},

<strong>badgeType</strong>: {
    type: String,
    default: 'default'
},

<strong>icon</strong>: {
    type: String,
    default: ''
},

<strong>selected</strong>: {
    type: Boolean,
    default: false
}</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <tabs>
                            <tab name="Tab 1">
                                <container>
                                    Tab 1 content
                                </container>
                            </tab>

                            <tab name="Tab 2">
                                <container>
                                    Tab 2 content
                                </container>
                            </tab>

                            <tab name="Tab 3">
                                <container>
                                    Tab 3 content
                                </container>
                            </tab>
                        </tabs>

<preformatted>&lt;<strong>tabs</strong>&gt;
    &lt;<strong>tab name</strong>="Tab 1"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/<strong>tab</strong>&gt;

    &lt;<strong>tab name</strong>="Tab 2"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/<strong>tab</strong>&gt;

    &lt;<strong>tab name</strong>="Tab 3"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/<strong>tab</strong>&gt;
&lt;/<strong>tabs</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="selected">
                    <subheading>Selected</subheading>

                    <well>
                        <tabs>
                            <tab name="Tab 1" :selected="true">
                                <container>
                                    Tab 1 content
                                </container>
                            </tab>

                            <tab name="Tab 2">
                                <container>
                                    Tab 2 content
                                </container>
                            </tab>

                            <tab name="Tab 3">
                                <container>
                                    Tab 3 content
                                </container>
                            </tab>
                        </tabs>

<preformatted>&lt;tabs&gt;
    &lt;tab name="Tab 1" <strong>:selected</strong>="<strong>true</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Tab 2"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Tab 3"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;
&lt;/tabs&gt;</preformatted>
                    </well>
                </section>

                <section id="icons">
                    <subheading>Icons</subheading>

                    <well>
                        <tabs>
                            <tab name="Users" icon="fa fa-users" :selected="true">
                                <container>
                                    Users content
                                </container>
                            </tab>

                            <tab name="Files" icon="fa fa-folder">
                                <container>
                                    Files content
                                </container>
                            </tab>

                            <tab name="Settings" icon="fa fa-cog">
                                <container>
                                    Settings content
                                </container>
                            </tab>
                        </tabs>

<preformatted>&lt;tabs&gt;
    &lt;tab name="Tab 1" <strong>icon</strong>="<strong>fa fa-users</strong>" :selected="true"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Tab 2" <strong>icon</strong>="<strong>fa fa-folder</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Tab 3" <strong>icon</strong>="<strong>fa fa-cog</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;
&lt;/tabs&gt;</preformatted>
                    </well>
                </section>

                <section id="badges">
                    <subheading>Badges</subheading>

                    <well>
                        <tabs>
                            <tab name="Default Badge" badge="1" :selected="true">
                                <container>
                                    Default badge content
                                </container>
                            </tab>

                            <tab name="Primary Badge" badge="2" badge-type="primary">
                                <container>
                                    Primary badge content
                                </container>
                            </tab>

                            <tab name="Info Badge" badge="3" badge-type="info">
                                <container>
                                    Info badge content
                                </container>
                            </tab>

                            <tab name="Success Badge" badge="4" badge-type="success">
                                <container>
                                    Success badge content
                                </container>
                            </tab>

                            <tab name="Danger Badge" badge="5" badge-type="danger">
                                <container>
                                    Danger badge content
                                </container>
                            </tab>
                        </tabs>

<preformatted>&lt;tabs&gt;
    &lt;tab name="Default" <strong>badge</strong>="1" :selected="true"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Primary" <strong>badge</strong>="2" <strong>badge-type</strong>="<strong>primary</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Info" <strong>badge</strong>="3" <strong>badge-type</strong>="<strong>info</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Success" <strong>badge</strong>="4" <strong>badge-type</strong>="<strong>success</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;

    &lt;tab name="Danger" <strong>badge</strong>="5" <strong>badge-type</strong>="<strong>danger</strong>"&gt;
        &lt;container&gt;
            // content
        &lt;/container&gt;
    &lt;/tab&gt;
&lt;/tabs&gt;</preformatted>
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
                    <a href="#selected">Selected</a>
                </list-item>

                <list-item>
                    <a href="#icons">Icons</a>
                </list-item>

                <list-item>
                    <a href="#badges">Badges</a>
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
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Tab: use('EntradaJS/Components/Tab.vue'),
        Tabs: use('EntradaJS/Components/Tabs.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "TabsDocumentation",

        components: importedComponents,

        created () {
            this.$emit('showing');
        },

        destroyed() {
            this.$emit('showing');
        }
    }
</script>

<style lang="scss">
    #tabs
        .tab-details {
            margin-bottom: 30px;
        }
    }
</style>
