<template>
    <div id="well">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Text Editor</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Text Editor</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/TextEditor.vue</strong></preformatted>
                    <preformatted title="Props"><strong>height</strong>: {
    type: Number,
    default: 200
},

<strong>content</strong>: {
    type: String
},

<strong>toolbar</strong>: {
    type: String,
    required: true
},

<strong>types</strong>: {
    type: String,
    default: 'classic'
}
                    </preformatted>
                </section>

                <section id="simple">
                    <subheading>Simple</subheading>
                    <well>
                        <text-editor :content="content" toolbar="Simple"></text-editor>
                        <preformatted>&lt;<strong>text-editor</strong> <strong>:content</strong>="content" <strong>toolbar</strong>="simple"&gt;&lt;/<strong>text-editor</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="advanced">
                    <subheading>Advanced</subheading>
                    <well>
                        <text-editor :content="content" toolbar="advanced"></text-editor>
                        <preformatted>&lt;<strong>text-editor</strong> <strong>:content</strong>="content" <strong>toolbar</strong>="advanced"&gt;&lt;/<strong>text-editor</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="custom">
                    <subheading>Custom</subheading>
                    <well>
                        <text-editor :content="content" toolbar="custom"></text-editor>
                        <preformatted>&lt;<strong>text-editor</strong> <strong>:content</strong>="content" <strong>toolbar</strong>="custom"&gt;&lt;/<strong>text-editor</strong>&gt;</preformatted>
                        <preformatted title="Within 'TextEditor.vue'">else if (this.componentToolbar == 'custom') {
    return {
        toolbar: [
            { name: 'document', items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },
            { name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
            { name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
            { name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
            '/',
            { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat' ] },
            { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language' ] },
            { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
            { name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },
            '/',
            { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
            { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
            { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
            { name: 'about', items: [ 'About' ] }
        ],
                            </preformatted>
                    </well>
                </section>

                <section id="label">
                    <subheading>Label</subheading>
                    <well>
                        <text-editor :content="content" toolbar="Simple">Label</text-editor>
                        <preformatted>&lt;<strong>text-editor</strong> <strong>:content</strong>="content" <strong>toolbar</strong>="simple"&gt;&lt;/<strong>text-editor</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="inline">
                    <subheading>Inline Editor</subheading>
                    <well>
                        <text-editor :content="content" toolbar="simple" types="inline"></text-editor>
                        <preformatted>&lt;<strong>text-editor</strong> <strong>:content</strong>="content" <strong>toolbar</strong>="simple" <strong>types</strong>="inline"&gt;&lt;/<strong>text-editor</strong>&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#simple">Simple</a>
                </list-item>

                <list-item>
                    <a href="#advanced">Advanced</a>
                </list-item>

                <list-item>
                    <a href="#custom">Custom</a>
                </list-item>

                <list-item>
                    <a href="#label">Label</a>
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
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        TextEditor: use('EntradaJS/Components/TextEditor.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        components: importedComponents,

        data () {
            return {
                content: ''
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
