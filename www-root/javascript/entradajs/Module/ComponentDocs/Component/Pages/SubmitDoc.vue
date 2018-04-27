<template>
    <div id="submit-inputs">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Submit Inputs</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Submit Inputs</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Submit.vue</strong></preformatted>

<preformatted title="Props"><strong>formId</strong>: {
    type: String,
    required: true
},

<strong>value</strong>: {
    type: String,
    required: true
},

<strong>type</strong>: {
    type: String,
    default: 'default'
},

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
},

<strong>disabled</strong>: {
    type: Boolean,
    default: false
}</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <submit form-id="form1" value="Submit">Submit Default</submit>

                        <preformatted>&lt;<strong>submit</strong> <strong>form-id</strong>="form-id" <strong>value</strong>="Value"&gt;Text&lt;/<strong>submit</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="types">
                    <subheading>Types</subheading>

                    <well>
                        <submit form-id="form1" value="Submit" type="primary">Submit Primary</submit>

                        <submit form-id="form1" value="Submit" type="success">Submit Success</submit>

                        <submit form-id="form1" value="Submit" type="danger">Submit Danger</submit>

<preformatted>&lt;submit form-id="form-id" value="Value" <strong>type</strong>="<strong>primary</strong>"&gt;Text&lt;/submit&gt;

&lt;submit form-id="form-id" value="Value" <strong>type</strong>="<strong>success</strong>"&gt;Text&lt;/submit&gt;

&lt;submit form-id="form-id" value="Value" <strong>type</strong>="<strong>danger</strong>"&gt;Text&lt;/submit&gt;</preformatted>
                    </well>
                </section>

                <section id="disabled">
                    <subheading>Disabled</subheading>

                    <well>
                        <submit form-id="form1" value="Submit" :disabled="true">Submit Disabled</submit>

                        <preformatted>&lt;submit form-id="form-id" value="Value" <strong>:disabled</strong>="<strong>true</strong>"&gt;Text&lt;/submit&gt;</preformatted>
                    </well>
                </section>

                <section id="small">
                    <subheading>Small</subheading>

                    <well>
                        <submit :small="true" form-id="form1" value="Submit">Submit Default</submit>

                        <submit :small="true" form-id="form1" value="Submit" button-type="primary">Submit Primary</submit>

                        <submit :small="true" form-id="form1" value="Submit" button-type="success">Submit Success</submit>

                        <submit :small="true" form-id="form1" value="Submit" button-type="danger">Submit Danger</submit>

                        <submit :small="true" form-id="form1" value="Submit" :disabled="true">Submit Disabled</submit>

                        <preformatted>&lt;submit <strong>:small</strong>="<strong>true</strong>" form-id="form-id" value="Value"&gt;Text&lt;/submit&gt;</preformatted>
                    </well>
                </section>

                <section id="icons">
                    <subheading>Icons</subheading>

                    <well>
                        <submit form-id="form1" value="Submit" type="success" prepend-icon="fa fa-paper-plane">Submit</submit>

                        <submit form-id="form1" value="Submit" type="danger" append-icon="fa fa-trash">Delete</submit>

<preformatted>&lt;submit form-id="form-id" value="Value" <strong>prepend-icon</strong>="<strong>fa fa-paper-plane</strong>"&gt;Submit&lt;/submit&gt;

&lt;submit form-id="form-id" value="Value" <strong>append-icon</strong>="<strong>fa fa-trash</strong>"&gt;Delete&lt;/submit&gt;</preformatted>
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
                    <a href="#disabled">Disabled</a>
                </list-item>

                <list-item>
                    <a href="#small">Small</a>
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
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Submit: use('EntradaJS/Components/Submit.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "SubmitDocumentation",

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
    #submit-inputs {
        .well {
            .preformatted {
                margin-top: 20px;
            }
        }
    }
</style>
