<template>
    <div id="multiple-line-input">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Multiple Line Input</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Multiple Line Input</page-heading>

<preformatted title="Required Components">path/to/<strong>components/MultipleLineInput.vue</strong></preformatted>

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
                        <multiple-line-input id="default-simple">Simple</multiple-line-input>
                        <preformatted>&lt;<strong>multiple-line-input</strong> <strong>id</strong>="input-id"&gt;Label&lt;/<strong>multiple-line-input</strong>&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-hidden-label" :hide-label="true">Hidden Label</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>:hide-label</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-default-value" value="Default Value">Defualt Value</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>value</strong>="Value"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-required" :required="true">Required</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>:required</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-placeholder" placeholder="Placeholder text">Placeholder</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>placeholder</strong>="Text"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-help-text" help-text="This can help explain what the input is for">Default with help text</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>help-text</strong>="Description"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-disabled" :disabled="true">Disabled</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>:disabled</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-readonly" :readonly="true" help-text="Appears as disabled, but does not have disabled cursor or greyed out label">Read-only</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>:readonly</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-prepend-icon" prepend-icon="fa fa-user">Prepend Icon</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>prepend-icon</strong>="<strong>fa fa-user</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-append-icon" append-icon="fa fa-search">Append Icon</multiple-line-input>
                        <preformatted>&lt;multiple-line-input id="input-id" type="text" <strong>append-icon</strong>="<strong>fa fa-search</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input id="default-kitchen-sink" placeholder="Placeholder text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="This can help explain what the input is for" :required="true">Kitchen Sink</multiple-line-input>
                        <preformatted>&lt;<strong>multiple-line-input</strong> id="input-id" type="text" placeholder="Text" prepend-input="Text" append-input="Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="Description" :required="true"&gt;Label&lt;/<strong>multiple-line-input</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="inner-label">
                    <subheading>Inner Label</subheading>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-simple">Simple</multiple-line-input>
                        <preformatted>&lt;<strong>multiple-line-input</strong> <strong>:inner-label</strong>="<strong>true</strong>" <strong>id</strong>="input-id" <strong>type</strong>="text"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-simple" :hide-label="true">Hidden Value</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" :hide-label="true"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-default-value" value="Default Value">Defualt Value</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>value</strong>="Value"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-required" :required="true">Required</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>:required</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-placeholder" placeholder="Placeholder text">Placeholder</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>placeholder</strong>="Text"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-help-text" help-text="This can help explain what the input is for">Default with help text</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>help-text</strong>="Description"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-disabled" :disabled="true">Disabled</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>:disabled</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-readonly" :readonly="true" help-text="Appears as disabled, but does not have disabled cursor or greyed out label">Read-only</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>:readonly</strong>="<strong>true</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-prepend-icon" prepend-icon="fa fa-user">Prepend Icon</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>prepend-icon</strong>="<strong>fa fa-user</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-append-icon" append-icon="fa fa-search">Append Icon</multiple-line-input>
                        <preformatted>&lt;multiple-line-input <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" <strong>append-icon</strong>="<strong>fa fa-search</strong>"&gt;Label&lt;/multiple-line-input&gt;</preformatted>
                    </well>

                    <well>
                        <multiple-line-input :inner-label="true" id="inner-label-kitchen-sink" placeholder="Placeholder text" prepend-input="Prepended Text" append-input="Appended Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="This can help explain what the input is for" :required="true">Kitchen Sink</multiple-line-input>
                        <preformatted>&lt;<strong>multiple-line-input</strong> <strong>:inner-label</strong>="<strong>true</strong>" id="input-id" placeholder="Text" prepend-icon="fa fa-user" append-icon="fa fa-search" help-text="Description" :required="true"&gt;Label&lt;/<strong>multiple-line-input</strong>&gt;</preformatted>
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
        MultipleLineInput: use('EntradaJS/Components/MultipleLineInput.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "MultipleLineInputDocumentation",

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
