<template>
    <div id="checkboxes">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Checkboxes</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Checkboxes</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Checkbox.vue</strong></preformatted>

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

<strong>hideLabel</strong>: {
    type: Boolean,
    default: false
},

<strong>checked</strong>: {
    type: Boolean,
    default: false
},

<strong>disabled</strong>: {
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
                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <checkbox id="default-checkbox" name="default-checkbox" label="Default Checkbox"></checkbox>

                        <preformatted>&lt;<strong>checkbox</strong> <strong>id</strong>="checkbox-id" <strong>name</strong>="checkbox-name" <strong>label</strong>="Label"&gt;&lt;/<strong>checkbox</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="checked">
                    <subheading>Checked</subheading>

                    <well>
                        <checkbox id="default-checkbox-checked" name="default-checkbox-checked" label="Default Checkbox Checked" :checked="true">Checkbox checked</checkbox>

                        <preformatted>&lt;checkbox id="checkbox-id" name="checkbox-name" label="Label" <strong>:checked</strong>="<strong>true</strong>"&gt;&lt;/checkbox&gt;</preformatted>
                    </well>
                </section>

                <section id="disabled">
                    <subheading>Disabled</subheading>

                    <well>
                        <checkbox id="disabled-checkbox" name="disabled-checkbox" label="Disabled Checkbox" :disabled="true">Disabled checkbox</checkbox>

                        <preformatted>&lt;checkbox id="checkbox-id" name="checkbox-name" label="Label" <strong>:disabled</strong>="<strong>true</strong>"&gt;Label&lt;/checkbox&gt;</preformatted>
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
                    <a href="#checked">Checked</a>
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
        Checkbox: use('EntradaJS/Components/Checkbox.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "CheckboxDocumentation",

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
