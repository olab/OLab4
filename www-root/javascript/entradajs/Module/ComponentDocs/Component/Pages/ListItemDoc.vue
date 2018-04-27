<template>
    <div id="list-items">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>List Items</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>List Items</page-heading>

<preformatted title="Required Components">path/to/<strong>components/ListGroup.vue</strong>
path/to/<strong>components/ListItem.vue</strong></preformatted>

<preformatted title="Props"><strong>checkbox</strong>: {
    type: Boolean,
    default: false
},

<strong>checkboxId</strong>: {
    type: String
},

<strong>checkboxName</strong>: {
    type: String
},

<strong>contentToggle</strong>: {
    type: Boolean,
    default: false
}</preformatted>
                </section>

                <section id="link-list">
                    <subheading>Link List</subheading>

                    <well>
                        <list-group>
                            <list-item>
                                <a href="#">Link</a>
                            </list-item>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>
                        </list-group>

<preformatted>&lt;<strong>list-group</strong>&gt;
    &lt;<strong>list-item</strong>&gt;
        &lt;<strong>a</strong> href="#"&gt;Link&lt;/<strong>a</strong>&gt;
    &lt;/<strong>list-item</strong>&gt;

    &lt;<strong>list-item</strong>&gt;
        &lt;<strong>a</strong> href="#"&gt;Link&lt;/<strong>a</strong>&gt;
    &lt;/<strong>list-item</strong>&gt;

    &lt;<strong>list-item</strong>&gt;
        &lt;<strong>a</strong> href="#"&gt;Link&lt;/<strong>a</strong>&gt;
    &lt;/<strong>list-item</strong>&gt;
&lt;/<strong>list-group</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="header">
                    <subheading>Header</subheading>

                    <well>
                        <list-group>
                            <div slot="title">Header</div>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>
                        </list-group>

<preformatted>&lt;list-group&gt;
    &lt;<strong>div slot</strong>="<strong>title</strong>"&gt;Header&lt;/<strong>div</strong>&gt;

    &lt;list-item&gt;
        &lt;a href="#"&gt;Link&lt;/a&gt;
    &lt;/list-item&gt;

    &lt;list-item&gt;
        &lt;a href="#"&gt;Link&lt;/a&gt;
    &lt;/list-item&gt;

    &lt;list-item&gt;
        &lt;a href="#"&gt;Link&lt;/a&gt;
    &lt;/list-item&gt;
&lt;/list-group&gt;</preformatted>
                    </well>
                </section>

                <section id="alternate-title">
                    <subheading>Alternate Title</subheading>

                    <well>
                        <list-group :alt-title="true">
                            <div slot="title">Header</div>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>

                            <list-item>
                                <a href="#">Link</a>
                            </list-item>
                        </list-group>

<preformatted>&lt;list-group <strong>:alt-title</strong>="<strong>true</strong>"&gt;
    &lt;<strong>div slot</strong>="<strong>title</strong>"&gt;Header&lt;/<strong>div</strong>&gt;

    &lt;list-item&gt;
        &lt;a href="#"&gt;Link&lt;/a&gt;
    &lt;/list-item&gt;

    &lt;list-item&gt;
        &lt;a href="#"&gt;Link&lt;/a&gt;
    &lt;/list-item&gt;

    &lt;list-item&gt;
        &lt;a href="#"&gt;Link&lt;/a&gt;
    &lt;/list-item&gt;
&lt;/list-group&gt;</preformatted>
                    </well>
                </section>

                <section id="content">
                    <subheading>Content</subheading>

                    <well>
                        <list-group>
                            <list-item>
                                <strong>Title</strong>
                                <div slot="content">Content</div>
                            </list-item>

                            <list-item>
                                <strong>Title</strong>
                                <div slot="content">Content</div>
                            </list-item>

                            <list-item>
                                <strong>Title</strong>
                                <div slot="content">Content</div>
                            </list-item>
                        </list-group>

<preformatted>&lt;list-group&gt;
    &lt;list-item&gt;
        &lt;strong&gt;Title&lt;/strong&gt;
        &lt;<strong>div slot</strong>="<strong>content</strong>"&gt;
            // content
        &lt;/<strong>div</strong>&gt;
    &lt;/list-item&gt;

    &lt;list-item&gt;
        &lt;strong&gt;Title&lt;/strong&gt;
        &lt;<strong>div slot</strong>="<strong>content</strong>"&gt;
            // content
        &lt;/<strong>div</strong>&gt;
    &lt;/list-item&gt;

    &lt;list-item&gt;
        &lt;strong&gt;Title&lt;/strong&gt;
        &lt;<strong>div slot</strong>="<strong>content</strong>"&gt;
            // content
        &lt;/<strong>div</strong>&gt;
    &lt;/list-item&gt;
&lt;/list-group&gt;</preformatted>
                    </well>
                </section>

                <section id="scrollable">
                    <subheading>Scrollable</subheading>

                    <well>
                        <list-group :max-height="100">
                            <list-item>
                                <span>List Item</span>
                            </list-item>

                            <list-item>
                                <span>List Item</span>
                            </list-item>

                            <list-item>
                                <span>List Item</span>
                            </list-item>
                        </list-group>

<preformatted>&lt;list-group <strong>:max-height</strong>="100"&gt;
    &lt;list-item&gt;
        // content
    &lt;/list-item&gt;

    &lt;list-item&gt;
        // content
    &lt;/list-item&gt;

    &lt;list-item&gt;
        // content
    &lt;/list-item&gt;
&lt;/list-group&gt;</preformatted>
                    </well>
                </section>

                <section id="checkbox">
                    <subheading>Checkbox</subheading>

                    <well>
                        <list-group>
                            <list-item :checkbox="true" checkbox-id="list-item-id-1" checkbox-label="Label" checkbox-name="list-item-name-1">
                                <strong>Title</strong>
                            </list-item>

                            <list-item :checkbox="true" checkbox-id="list-item-id-2" checkbox-label="Label" checkbox-name="list-item-name-2">
                                <strong>Title</strong>
                            </list-item>

                            <list-item :checkbox="true" checkbox-id="list-item-id-3" checkbox-label="Label" checkbox-name="list-item-name-3">
                                <strong>Title</strong>
                            </list-item>
                        </list-group>

<preformatted>&lt;list-group&gt;
    &lt;list-item <strong>:checkbox</strong>="<strong>true</strong>" <strong>checkbox-id</strong>="list-item-id" <strong>checkbox-name</strong>="list-item-name"&gt;
        &lt;strong&gt;Header&lt;/strong&gt;
    &lt;/list-item&gt;

    &lt;list-item <strong>:checkbox</strong>="<strong>true</strong>" <strong>checkbox-id</strong>="list-item-id" <strong>checkbox-name</strong>="list-item-name"&gt;
        &lt;strong&gt;Header&lt;/strong&gt;
    &lt;/list-item&gt;

    &lt;list-item <strong>:checkbox</strong>="<strong>true</strong>" <strong>checkbox-id</strong>="list-item-id" <strong>checkbox-name</strong>="list-item-name"&gt;
        &lt;strong&gt;Header&lt;/strong&gt;
    &lt;/list-item&gt;
&lt;/list-group&gt;</preformatted>
                    </well>
                </section>

                <section id="content-toggle">
                    <subheading>Content Toggle</subheading>

                    <well>
                        <list-group>
                            <list-item :content-toggle="true">
                                <strong>Title</strong>
                                <div slot="content">Content</div>
                            </list-item>

                            <list-item :content-toggle="true">
                                <strong>Title</strong>
                                <div slot="content">Content</div>
                            </list-item>

                            <list-item :content-toggle="true">
                                <strong>Title</strong>
                                <div slot="content">Content</div>
                            </list-item>
                        </list-group>

<preformatted>&lt;list-group&gt;
    &lt;list-item <strong>:content-toggle</strong>="<strong>true</strong>"&gt;
        &lt;strong&gt;Title&lt;/strong&gt;
        &lt;div slot="content"&gt;Content&lt;/div&gt;
    &lt;/list-item&gt;

    &lt;list-item <strong>:content-toggle</strong>="<strong>true</strong>"&gt;
        &lt;strong&gt;Title&lt;/strong&gt;
        &lt;div slot="content"&gt;Content&lt;/div&gt;
    &lt;/list-item&gt;

    &lt;list-item <strong>:content-toggle</strong>="<strong>true</strong>"&gt;
        &lt;strong&gt;Title&lt;/strong&gt;
        &lt;div slot="content"&gt;Content&lt;/div&gt;
    &lt;/list-item&gt;
&lt;/list-group&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#link-list">Link List</a>
                </list-item>

                <list-item>
                    <a href="#header">Header</a>
                </list-item>

                <list-item>
                    <a href="#alternate-title">Alternate Title</a>
                </list-item>

                <list-item>
                    <a href="#content">Content</a>
                </list-item>

                <list-item>
                    <a href="#scrollable">Scrollable</a>
                </list-item>

                <list-item>
                    <a href="#checkbox">Checkbox</a>
                </list-item>

                <list-item>
                    <a href="#content-toggle">Content Toggle</a>
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
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "ListItemDocumentation",

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
