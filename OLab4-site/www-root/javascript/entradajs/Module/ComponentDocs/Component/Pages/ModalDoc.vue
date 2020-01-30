<template>
    <div id="modals">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Modals</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Modals</page-heading>

<preformatted title="Required Components">path/to/<strong>components/Modal.vue</strong>
path/to/<strong>components/CardHeading.vue</strong></preformatted>

<preformatted title="Props"><strong>state</strong>: {
    type: Boolean,
    default: false
}

<strong>icon</strong>: {
    type: String
}</preformatted>

                    <preformatted title="Data"><strong>modalState</strong>: false</preformatted>

                    <alert>
                        <ul>
                            <li>The modal launcing element can be anything that is focusable.</li>
                        </ul>
                    </alert>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <btn @click.native="defaultModal = true">Launch Modal</btn>

                        <modal :state="defaultModal" @closing="defaultModal = false">
                            <header>
                                <card-heading>Modal Title</card-heading>
                            </header>

                            <div slot="content">
                                <alert>
                                    <ul>
                                        <li>This modal is completely responsive! Scale your browser window to see.</li>
                                    </ul>
                                </alert>
                            </div>
                        </modal>

<preformatted>&lt;btn <strong>@click.native</strong>="<strong>modalState = true</strong>"&gt;&lt;/btn&gt;

&lt;<strong>modal</strong> <strong>:state</strong>="<strong>modalState</strong>" <strong>@closing</strong>="<strong>modalState = false</strong>"&gt;
    &lt;<strong>header</strong>&gt;
        &lt;<strong>card-heading</strong>&gt;Modal Title&lt;/<strong>card-heading</strong>&gt;
    &lt;/<strong>header</strong>&gt;

    &lt;<strong>div slot</strong>="<strong>content</strong>"&gt;
        // content
    &lt;/<strong>div</strong>&gt;
&lt;/<strong>modal</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="footer">
                    <subheading>Footer</subheading>

                    <well>
                        <btn @click.native="testModal = true">Launch Modal</btn>

                        <modal :state="testModal" @closing="testModal = false">
                            <header>
                                <card-heading>Modal Title</card-heading>
                            </header>

                            <div slot="content">
                                <alert>
                                    <ul>
                                        <li>This modal is completely responsive! Scale your browser window to see.</li>
                                    </ul>
                                </alert>

                                <single-line-input id="title-input" type="text">Single Line Input</single-line-input>

                                <select-input label="Select Input">
                                    <span slot="value">{{ radioSelectedValue }}</span>

                                    <radio v-for="option in radioData" key="Single Select Options" :id="option.id" name="single-select-input" :label="option.label" :disabled="option.disabled" :value="radioSelectedValue" @radioEvent="radioChangeValue"></radio>
                                </select-input>
                            </div>

                            <footer slot="footer">
                                <div>
                                    <btn @click.native="testModal = false" :small="true">Close</btn>
                                </div>

                                <div>
                                    <btn @click.native="testModal = false" type="primary" :small="true">Confirm</btn>
                                </div>
                            </footer>
                        </modal>

<preformatted>&lt;btn @click.native="modalState = true"&gt;&lt;/btn&gt;

&lt;modal :state="modalState" @closing="modalState = false"&gt;
    &lt;header&gt;
        &lt;card-heading&gt;Modal Title&lt;/card-heading&gt;
    &lt;/header&gt;

    &lt;div slot="content"&gt;
        // content
    &lt;/div&gt;

    &lt;<strong>footer slot</strong>="<strong>footer</strong>"&gt;
        &lt;<strong>div</strong>&gt;
            // left content
        &lt;/<strong>div</strong>&gt;

        &lt;<strong>div</strong>&gt;
            // right content
        &lt;/<strong>div</strong>&gt;
    &lt;/<strong>footer</strong>&gt;
&lt;/modal&gt;</preformatted>
                    </well>
                </section>

                <section id="icon">
                    <subheading>Icon</subheading>

                    <well>
                        <btn @click.native="iconModal = true">Launch Modal</btn>

                        <modal icon="fa fa-star" :state="iconModal" @closing="iconModal = false">
                            <header>
                                <card-heading>Modal Title</card-heading>
                            </header>

                            <div slot="content">
                                <alert>
                                    <ul>
                                        <li>This modal is completely responsive! Scale your browser window to see.</li>
                                    </ul>
                                </alert>
                            </div>
                        </modal>

<preformatted>&lt;btn @click.native="modalState = true"&gt;&lt;/btn&gt;

&lt;modal <strong>icon</strong>="<strong>fa fa-star</strong>" :state="modalState" @closing="modalState = false"&gt;
    &lt;header&gt;
        &lt;card-heading&gt;Modal Title&lt;/card-heading&gt;
    &lt;/header&gt;

    &lt;div slot="content"&gt;
        // content
    &lt;/div&gt;
&lt;/modal&gt;</preformatted>
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
        CardHeading: use('EntradaJS/Components/CardHeading.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        Modal: use('EntradaJS/Components/Modal.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Radio: use('EntradaJS/Components/Radio.vue'),
        SelectInput: use('EntradaJS/Components/SelectInput.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "ModalDocumentation",

        components: importedComponents,

        data () {
            return {
                defaultModal: false,
                testModal: false,
                iconModal: false,

                radioData: [
                    { id: 'single-select-option-1', label: 'Option 1', disabled: false },
                    { id: 'single-select-option-2', label: 'Option 2', disabled: false },
                    { id: 'single-select-option-3', label: 'Option 3', disabled: false }
                ],

                radioSelectedValue: 'Select One'
            };
        },

        methods: {
            radioChangeValue (newValue) {
                this.radioSelectedValue = newValue;
            },

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
