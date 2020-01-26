<template>
    <div id="radios">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Radios</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Radios</page-heading>

<preformatted title="Required Components">path/to/<strong>components/Radio.vue</strong></preformatted>

<preformatted title="Props"><strong>id</strong>: {
    type: String,
    required: true
},

<strong>name</strong>: {
    type: String,
    required: true
},

<strong>label</strong>: {
    type: String,
    required: true
},

<strong>value</strong>: {
    type: String
},

<strong>disabled</strong>: {
    type: Boolean,
    default: false
}</preformatted>

<preformatted title="Methods"><strong>radioChangeValue</strong> (<strong>newValue</strong>) {
    this.<strong>radioSelectedValue</strong> = <strong>newValue</strong>;
}</preformatted>

<preformatted title="Data"><strong>radioData</strong>: [
    { <strong>id</strong>: 'default-option-1', <strong>label</strong>: 'Option 1', <strong>disabled</strong>: false },
    { <strong>id</strong>: 'default-option-2', <strong>label</strong>: 'Option 2', <strong>disabled</strong>: false },
    { <strong>id</strong>: 'default-option-3', <strong>label</strong>: 'Option 3', <strong>disabled</strong>: false }
],

<strong>radioSelectedValue</strong>: 'Option 1'</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <radio v-for="option in defaultOptions" key="Default Radio Options" :id="option.id" name="default-radio" :label="option.label" :disabled="option.disabled" :value="defaultSelectedValue" @radioEvent="defaultChangeValue"></radio>

<preformatted>&lt;<strong>radio</strong> <strong>v-for</strong>="<strong>radio</strong> in <strong>radioData</strong>" <strong>key</strong>="Radio Options" <strong>:id</strong>="<strong>radio.id</strong>" <strong>name</strong>="default-radio" <strong>:label</strong>="<strong>radio.label</strong>" <strong>:disabled</strong>="<strong>radio.disabled</strong>" <strong>:value</strong>="<strong>radioSelectedValue</strong>" <strong>@radioEvent</strong>="<strong>radioChangeValue</strong>"&gt;&lt;/<strong>radio</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="disabled">
                    <subheading>Disabled</subheading>

                    <well>
                        <input-group>
                            <radio v-for="option in disabledOptions" key="Disabled Radio Options" :id="option.id" name="disabled-radio" :label="option.label" :value="disabledSelectedValue" :disabled="option.disabled" @radioEvent="disabledChangeValue"></radio>
                        </input-group>

<preformatted><strong>radioData</strong>: [
    { id: 'default-option-1', label: 'Option 1', disabled: <strong>true</strong> },
    { id: 'default-option-2', label: 'Option 2', disabled: <strong>true</strong> },
    { id: 'default-option-3', label: 'Option 3', disabled: <strong>true</strong> }
]</preformatted>
                    </well>
                </section>

                <section id="inline">
                    <subheading>Inline</subheading>

                    <well>
                        <preformatted title="Required Components">path/to/<strong>components/InputGroup.vue</strong></preformatted>

                        <input-group :inline="true">
                            <radio v-for="option in inlineOptions" key="Inline Radio Options" :id="option.id" name="inline-radio" :label="option.label" :disabled="option.disabled" :value="inlineSelectedValue" @radioEvent="inlineChangeValue"></radio>
                        </input-group>

<preformatted>&lt;<strong>input-group</strong>&gt;
    &lt;radio v-for="radio in radioData" key="Radio Options" :id="radio.id" name="default-radio" :label="radio.label" :disabled="radio.disabled" :value="radioSelectedValue" @radioEvent="radioChangeValue"&gt;&lt;/radio&gt;
&lt;/<strong>input-group</strong>&gt;</preformatted>
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
                    <a href="#disabled">Disabled</a>
                </list-item>

                <list-item>
                    <a href="#inline">Inline</a>
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
        Radio: use('EntradaJS/Components/Radio.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "RadioDocumentation",

        components: importedComponents,

        data () {
            return {
                defaultOptions: [
                    { id: 'default-option-1', label: 'Option 1', disabled: false },
                    { id: 'default-option-2', label: 'Option 2', disabled: false },
                    { id: 'default-option-3', label: 'Option 3', disabled: false }
                ],

                disabledOptions: [
                    { id: 'disabled-option-1', label: 'Option 1', disabled: true },
                    { id: 'disabled-option-2', label: 'Option 2', disabled: true },
                    { id: 'disabled-option-3', label: 'Option 3', disabled: true }
                ],

                inlineOptions: [
                    { id: 'inline-option-1', label: 'Option 1', disabled: false },
                    { id: 'inline-option-2', label: 'Option 2', disabled: false },
                    { id: 'inline-option-3', label: 'Option 3', disabled: false }
                ],

                defaultSelectedValue: 'Option 1',
                disabledSelectedValue: 'Option 1',
                inlineSelectedValue: 'Option 1'
            };
        },

        methods: {
            defaultChangeValue (newValue) {
                this.defaultSelectedValue = newValue;
            },

            disabledChangeValue (newValue) {
                this.disabledSelectedValue = newValue;
            },

            inlineChangeValue (newValue) {
                this.inlineSelectedValue = newValue;
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
