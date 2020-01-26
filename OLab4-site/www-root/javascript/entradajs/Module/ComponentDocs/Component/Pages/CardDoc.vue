<template>
    <div id="cards">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Cards</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Cards</page-heading>

<preformatted title="Required Components">path/to/<strong>components/Card.vue</strong>
path/to/<strong>components/CardHeading.vue</strong></preformatted>

<preformatted title="Props"><strong>noHeaderBorder</strong>: {
    type: Boolean,
    default: false
},

<strong>checkbox</strong>: {
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

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <card>
                            <header>
                                <card-heading>Header</card-heading>
                            </header>

                            <div slot="content">
                                <p>Content</p>
                            </div>

                            <footer slot="footer">
                                <div>
                                    <p>Footer Left</p>
                                </div>

                                <div>
                                    <p>Footer Right</p>
                                </div>
                            </footer>
                        </card>

<preformatted>&lt;<strong>card</strong>&gt;
    &lt;<strong>header</strong>&gt;
        &lt;<strong>card-heading</strong>&gt;Title&lt;/<strong>card-heading</strong>&gt;
    &lt;/<strong>header</strong>&gt;

    &lt;<strong>div</strong> <strong>slot</strong>="<strong>content</strong>"&gt;
        // content
    &lt;/<strong>div</strong>&gt;

    &lt;<strong>footer</strong> <strong>slot</strong>="<strong>footer</strong>"&gt;
        &lt;<strong>div</strong>&gt;
            // content
        &lt;/<strong>div</strong>&gt;

        &lt;<strong>div</strong>&gt;
            // content
        &lt;/<strong>div</strong>&gt;
    &lt;/<strong>footer</strong>&gt;
&lt;/<strong>card</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="no-header">
                    <subheading>No Header</subheading>

                    <well>
                        <card>
                            <div slot="content">
                                <p>Content</p>
                            </div>

                            <footer slot="footer">
                                <div>
                                    <p>Footer</p>
                                </div>
                            </footer>
                        </card>

<preformatted>&lt;<strong>card</strong>&gt;
    &lt;<strong>div</strong> <strong>slot</strong>="<strong>content</strong>"&gt;
        // content
    &lt;/<strong>div</strong>&gt;

    &lt;<strong>footer</strong> <strong>slot</strong>="<strong>footer</strong>"&gt;
        &lt;<strong>div</strong>&gt;
            // content
        &lt;/<strong>div</strong>&gt;
    &lt;/<strong>footer</strong>&gt;
&lt;/<strong>card</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="no-footer">
                    <subheading>No Footer</subheading>

                    <well>
                        <card>
                            <header>
                                <card-heading>Header</card-heading>
                            </header>

                            <div slot="content">
                                <p>Content</p>
                            </div>
                        </card>

<preformatted>&lt;<strong>card</strong>&gt;
    &lt;<strong>header</strong>&gt;
        &lt;<strong>card-heading</strong>&gt;Title&lt;/<strong>card-heading</strong>&gt;
    &lt;/<strong>header</strong>&gt;

    &lt;<strong>div</strong> <strong>slot</strong>="<strong>content</strong>"&gt;
        // content
    &lt;/<strong>div</strong>&gt;
&lt;/<strong>card</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="content-only">
                    <subheading>Content Only</subheading>

                    <well>
                        <card>
                            <div slot="content">
                                <p>Content</p>
                            </div>
                        </card>

<preformatted>&lt;<strong>card</strong>&gt;
    &lt;<strong>div</strong> <strong>slot</strong>="<strong>content</strong>"&gt;
        // content
    &lt;/<strong>div</strong>&gt;
&lt;/<strong>card</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="no-header-border">
                    <subheading>No Header Border</subheading>

                    <well>
                        <card :no-header-border="true">
                            <header>
                                <card-heading>Header</card-heading>
                            </header>

                            <div slot="content">
                                <p>Content</p>
                            </div>

                            <footer slot="footer">
                                <div>
                                    <p>Footer</p>
                                </div>
                            </footer>
                        </card>

<preformatted>&lt;card <strong>:no-header-border</strong>="<strong>true</strong>"&gt;
    &lt;header&gt;
        &lt;card-heading&gt;Title&lt;/card-heading&gt;
    &lt;/header&gt;

    &lt;div slot="content"&gt;
        // content
    &lt;/div&gt;

    &lt;footer slot="footer"&gt;
        &lt;div&gt;
            // content
        &lt;/div&gt;
    &lt;/footer&gt;
&lt;/card&gt;</preformatted>
                    </well>
                </section>

                <section id="checkbox">
                    <subheading>Checkbox</subheading>

                    <well>
                        <card :checkbox="true" checkbox-id="checkbox-card-checkbox" checkbox-name="checkbox-card-checkbox" checkbox-label="Label">
                            <header>
                                <card-heading>Header</card-heading>
                            </header>

                            <div slot="content">
                                <p>Content</p>
                            </div>

                            <footer slot="footer">
                                <div>
                                    <p>Footer</p>
                                </div>
                            </footer>
                        </card>

<preformatted>&lt;card <strong>:checkbox</strong>="<strong>true</strong>" <strong>checkbox-id</strong>="checkbox-id" <strong>checkbox-name</strong>="checkbox-name"&gt;
    &lt;header&gt;
        &lt;card-heading&gt;Title&lt;/card-heading&gt;
    &lt;/header&gt;

    &lt;div slot="content"&gt;
        // content
    &lt;/div&gt;

    &lt;footer slot="footer"&gt;
        &lt;div&gt;
            // content
        &lt;/div&gt;
    &lt;/footer&gt;
&lt;/card&gt;</preformatted>
                    </well>
                </section>

                <section id="content-toggle">
                    <subheading>Content Toggle</subheading>

                    <well>
                        <card :no-header-border="true" :content-toggle="true">
                            <header>
                                <card-heading>Header</card-heading>
                            </header>

                            <div slot="content">
                                <p>Content</p>
                            </div>

                            <footer slot="footer">
                                <div>
                                    <p>Footer</p>
                                </div>
                            </footer>
                        </card>
<preformatted>&lt;card <strong>:no-header-border</strong>="<strong>true</strong>" <strong>:content-toggle</strong>="<strong>true</strong>"&gt;
    &lt;header&gt;
        &lt;card-heading&gt;Title&lt;/card-heading&gt;
    &lt;/header&gt;

    &lt;div slot="content"&gt;
        // content
    &lt;/div&gt;

    &lt;footer slot="footer"&gt;
        &lt;div&gt;
            // content
        &lt;/div&gt;
    &lt;/footer&gt;
&lt;/card&gt;</preformatted>
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
                    <a href="#no-header">No Header</a>
                </list-item>

                <list-item>
                    <a href="#no-footer">No Footer</a>
                </list-item>

                <list-item>
                    <a href="#content-only">Content Only</a>
                </list-item>

                <list-item>
                    <a href="#no-header-border">No Header Border</a>
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
        Card: use('EntradaJS/Components/Card.vue'),
        CardHeading: use('EntradaJS/Components/CardHeading.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "CardDocumentation",

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
