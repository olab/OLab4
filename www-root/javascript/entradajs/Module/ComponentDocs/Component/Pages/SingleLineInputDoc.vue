<template>
    <div id="single-line-input">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Single Line Input</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Single Line Input</page-heading>

<preformatted title="Required Components">path/to/<strong>components/SingleLineInput.vue</strong></preformatted>

<preformatted title="Props"><strong>innerLabel</strong>: {
    type: Boolean,
    default: false
},

<strong>hideLabel</strong>: {
    type: Boolean,
    default: false
},

<strong>helpText</strong>: {
    type: String
},

<strong>id</strong>: {
    type: String,
    required: true
},

<strong>type</strong>: {
    type: String,
    required: true
},

<strong>value</strong>: {
    type: String
},

<strong>required</strong>: {
    type: Boolean,
    default: false
},

<strong>step</strong>: {
    type: Number,
    default: 1
},

<strong>placeholder</strong>: {
    type: String,
},

<strong>disabled</strong>: {
    type: Boolean,
    default: false
},

<strong>readonly</strong>: {
    type: Boolean,
    default: false
},

<strong>prependInput</strong>: {
    type: String
},

<strong>appendInput</strong>: {
    type: String
},

<strong>prependIcon</strong>: {
    type: String
},

<strong>appendIcon</strong>: {
    type: String
}</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <single-line-input id="default-simple" type="text">Simple</single-line-input>
                        <preformatted>&lt;<strong>single-line-input</strong> <strong>id</strong>="input-id" <strong>type</strong>="text"&gt;Label&lt;/<strong>single-line-input</strong>&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-hidden-label" type="text" :hide-label="true">Hidden Label</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>:hide-label</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-default-value" type="text" value="Default Value">Defualt Value</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>value</strong>="Value"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-number" type="number" :step="5">Number</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="<strong>number</strong>" <strong>:step</strong>="5"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-required" type="text" :required="true">Required</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>:required</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-placeholder" type="text" placeholder="Placeholder text">Placeholder</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>placeholder</strong>="Text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-help-text" type="text" help-text="This can help explain what the input is for">Default with help text</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>help-text</strong>="Description"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-disabled" type="text" :disabled="true">Disabled</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>:disabled</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-readonly" type="text" :readonly="true" help-text="Appears as disabled, but does not have disabled cursor or greyed out label">Read-only</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>:readonly</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-prepend-input" type="text" prepend-input="Prepended Text">Prepend Input</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>prepend-input</strong>="Text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-append-input" type="text" append-input="Appended Text">Append Input</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>append-input</strong>="Text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-prepend-icon" type="text" prepend-icon="fa fa-user">Prepend Icon</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>prepend-icon</strong>="<strong>fa fa-user</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-append-icon" type="text" append-icon="fa fa-search">Append Icon</single-line-input>
                        <preformatted>&lt;single-line-input id="input-id" type="text" <strong>append-icon</strong>="<strong>fa fa-search</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input id="default-kitchen-sink" type="text" placeholder="Placeholder text" prepend-input="Prepended Text" append-input="Appended Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="This can help explain what the input is for" :required="true">Kitchen Sink</single-line-input>
                        <preformatted>&lt;<strong>single-line-input</strong> id="input-id" type="text" placeholder="Text" prepend-input="Text" append-input="Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="Description" :required="true"&gt;Label&lt;/<strong>single-line-input</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="inner-label">
                    <subheading>Inner Label</subheading>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-simple" type="text">Simple</single-line-input>
                        <preformatted>&lt;<strong>single-line-input</strong> <strong>:inner-label</strong>="<strong>true</strong>" <strong>id</strong>="input-id" <strong>type</strong>="text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-simple" type="text" :hide-label="true">Hidden Value</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" :hide-label="true"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-default-value" type="text" value="Default Value">Defualt Value</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>value</strong>="Value"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-number" type="text" :step="5">Number</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="<strong>number</strong>" <strong>:step</strong>="5"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-required" type="text" :required="true">Required</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>:required</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-placeholder" type="text" placeholder="Placeholder text">Placeholder</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>placeholder</strong>="Text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-help-text" type="text" help-text="This can help explain what the input is for">Default with help text</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>help-text</strong>="Description"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-disabled" type="text" :disabled="true">Disabled</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>:disabled</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-readonly" type="text" :readonly="true" help-text="Appears as disabled, but does not have disabled cursor or greyed out label">Read-only</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>:readonly</strong>="<strong>true</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-prepend-input" type="text" prepend-input="Prepended Text">Prepend Input</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>prepend-input</strong>="Text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-append-input" type="text" append-input="Appended Text">Append Input</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>append-input</strong>="Text"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-prepend-icon" type="text" prepend-icon="fa fa-user">Prepend Icon</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>prepend-icon</strong>="<strong>fa fa-user</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-append-icon" type="text" append-icon="fa fa-search">Append Icon</single-line-input>
                        <preformatted>&lt;single-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" <strong>append-icon</strong>="<strong>fa fa-search</strong>"&gt;Label&lt;/single-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <single-line-input :inner-label="true" id="inner-label-kitchen-sink" type="text" placeholder="Placeholder text" prepend-input="Prepended Text" append-input="Appended Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="This can help explain what the input is for" :required="true">Kitchen Sink</single-line-input>
                        <preformatted>&lt;<strong>single-line-input</strong> <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" type="text" placeholder="Text" prepend-input="Text" append-input="Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="Description" :required="true"&gt;Label&lt;/<strong>single-line-input</strong>&gt;</preformatted>
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
                    <a href="#inner-label">Inner Label</a>
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
        InputGroup: use('EntradaJS/Components/InputGroup.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "SingleLineInputDocumentation",

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
