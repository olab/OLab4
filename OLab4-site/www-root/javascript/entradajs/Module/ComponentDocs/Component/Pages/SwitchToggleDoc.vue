<template>
    <div id="switch-toggle">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Switch Toggle</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Swtich Toggle</page-heading>

<preformatted title="Required Components">path/to/<strong>components/SwitchToggle.vue</strong></preformatted>

<preformatted title="Props"><strong>id</strong>: {
    type: String,
    required: true
},

<strong>value</strong>: {
    type: String
},

<strong>name</strong>: {
    type: String,
    required: true
},

<strong>disabled</strong>: {
    type: Boolean,
    default: false
},

<strong>trueState</strong>: {
    type: String,
    required: true
},

<strong>falseState</strong>: {
    type: String,
    required: true
},

<strong>defaultState</strong>: {
    type: Boolean,
    default: false
}</preformatted>

<preformatted title="Methods"><strong>checking ()</strong> {
    if (!this.disabled) {
        this.isChecked = !this.isChecked;
        this.$emit('<strong>checkEvent</strong>');
    }
}</preformatted>
                </section>

                <section id="toggle-off">
                    <subheading>Toggle Off</subheading>

                    <well>
                        <input-group>
                            <switch-toggle id="switch-off" name="switch-off" true-state="On" false-state="Off" :default-state="showDefaultOffInput" @toggleEvent="toggleDefaultOffInput">Default Off</switch-toggle>

                            <single-line-input v-show="showDefaultOffInput" id="default-off-input" type="text" name="default-off-input" help-hext="This input will be shown when the toggle is set to On.">Default Off Input</single-line-input>
                        </input-group>

                        <preformatted>&lt;<strong>switch-toggle</strong> id="toggle-id" name="toggle-name" true-state="On" false-state="Off" <strong>:default-state</strong>="<strong>false</strong>" @toggleEvent="toggleMethod"&gt;Default Off&lt;/<strong>switch-toggle</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="toggle-on">
                    <subheading>Toggle On</subheading>

                    <well>
                        <input-group>
                            <switch-toggle id="switch-on" name="switch-on" true-state="On" false-state="Off" :default-state="showDefaultOnInput" @toggleEvent="toggleDefaultOnInput">Default On</switch-toggle>

                            <single-line-input v-show="showDefaultOnInput" id="default-on-input" type="text" name="default-on-input" help-text="This input will be hidden when the toggle is set to Off.">Default On Input</single-line-input>
                        </input-group>

                        <preformatted>&lt;<strong>switch-toggle</strong> id="toggle-id" name="toggle-name" true-state="On" false-state="Off" <strong>:default-state</strong>="<strong>true</strong>" @toggleEvent="toggleMethod"&gt;Default On&lt;/<strong>switch-toggle</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="disabled">
                    <subheading>Disabled</subheading>

                    <well>
                        <switch-toggle id="switch-disabled" name="switch-disabled" true-state="On" false-state="Off" :default-state="true" :disabled="true">Default Disabled</switch-toggle>

                        <preformatted>&lt;<strong>switch-toggle</strong> id="toggle-id" name="toggle-name" true-state="On" false-state="Off" :default-state="true" <strong>:disabled</strong>="<strong>true</strong>"&gt;Default On&lt;/<strong>switch-toggle</strong>&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#toggle-off">Toggle Off</a>
                </list-item>

                <list-item>
                    <a href="#toggle-on">Toggle On</a>
                </list-item>

                <list-item>
                    <a href="#disabled">Disabled</a>
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
        SwitchToggle: use('EntradaJS/Components/SwitchToggle.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "SwitchToggleDocumentation",

        components: importedComponents,

        data() {
            return {
                showDefaultOffInput: false,
                showDefaultOnInput: true,
            };
        },

        methods: {
            toggleDefaultOffInput (currentState) {
                this.showDefaultOffInput = currentState;
            },

            toggleDefaultOnInput (currentState) {
                this.showDefaultOnInput = currentState;
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
