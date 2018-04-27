<template>
    <div id="select-inputs">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Select Inputs</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Select Inputs</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/SelectInput.vue</strong></preformatted>

<preformatted title="Props"><strong>fixed</strong>: {
    type: Boolean,
    default: false
}</preformatted>
                </section>

                <section id="single-select-input">
                    <subheading>Single Select Input</subheading>

                    <well>
                        <alert>
                            <ul>
                                <li>Review the Radio element's <router-link to="/components/radios">documentation</router-link> to understand how the radio component is setup.</li>
                            </ul>
                        </alert>

                        <preformatted title="Required Components">path/to/<strong>components/Radio.vue</strong></preformatted>

<preformatted title="Methods"><strong>optionChangeValue</strong> (<strong>newValue</strong>) {
    this.<strong>optionSelectedValue</strong> = <strong>newValue</strong>;
}</preformatted>

<preformatted title="Data"><strong>optionData</strong>: [
    { <strong>id</strong>: 'default-option-1', <strong>label</strong>: 'Option 1', <strong>disabled</strong>: false },
    { <strong>id</strong>: 'default-option-2', <strong>label</strong>: 'Option 2', <strong>disabled</strong>: false },
    { <strong>id</strong>: 'default-option-3', <strong>label</strong>: 'Option 3', <strong>disabled</strong>: false }
],

<strong>optionSelectedValue</strong>: 'Select One'</preformatted>

                        <select-input>
                            <span slot="value">{{ radioSelectedValue }}</span>

                            <radio v-for="option in radioData" key="Single Select Options" :id="option.id" name="single-select-input" :label="option.label" :disabled="option.disabled" :value="radioSelectedValue" @radioEvent="radioChangeValue"></radio>
                        </select-input>

<preformatted>&lt;<strong>select-input</strong>&gt;
    &lt;<strong>span slot</strong>="<strong>value</strong>"&gt;{{ <strong>optionSelectedValue</strong> }}&lt;/<strong>span</strong>&gt;

    &lt;radio v-for="option in <strong>optionData</strong>" key="Options" :id="option.id" name="single-select-input" :label="option.label" :disabled="option.disabled" :value="<strong>optionSelectedValue</strong>" @radioEvent="<strong>optionChangeValue</strong>"&gt;&lt;/radio&gt;
&lt;/<strong>select-input</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="multi-select-input">
                    <subheading>Multi-Select Input</subheading>

                    <well>
                        <alert>
                            <ul>
                                <li>Review the Checkbox element's <router-link to="/components/checkboxes">documentation</router-link> to understand how the checkbox component is setup.</li>
                            </ul>
                        </alert>

                        <preformatted title="Required Components">path/to/<strong>components/Checkbox.vue</strong></preformatted>

<preformatted title="Methods"><strong>optionChangeValue</strong> (<strong>isChecked</strong>) {
    if (<strong>isChecked</strong>) {
        this.<strong>optionSelectCount++</strong>;
    } else if (<strong>!isChecked</strong>) {
        this.<strong>optionSelectCount--</strong>;
    }
}</preformatted>

<preformatted title="Data"><strong>optionData</strong>: [
    { <strong>id</strong>: 'default-option-1', <strong>label</strong>: 'Option 1', <strong>disabled</strong>: false },
    { <strong>id</strong>: 'default-option-2', <strong>label</strong>: 'Option 2', <strong>disabled</strong>: false },
    { <strong>id</strong>: 'default-option-3', <strong>label</strong>: 'Option 3', <strong>disabled</strong>: false }
],

<strong>optionSelectCount</strong>: 0</preformatted>

                        <select-input>
                            <span slot="value">{{ checkboxSelectCount }} selected</span>

                            <checkbox v-for="option in checkboxData" key="Multi-Select Options" name="multi-select-input" :id="option.id" :label="option.label" :disabled="option.disabled" @checkboxUpdate="checkboxChangeValue"></checkbox>
                        </select-input>

<preformatted>&lt;<strong>select-input</strong>&gt;
    &lt;<strong>span slot</strong>="<strong>value</strong>"&gt;{{ <strong>optionSelectedValue</strong> }} selected&lt;/<strong>span</strong>&gt;

    &lt;checkbox v-for="option in <strong>optionData</strong>" key="Options" name="multi-select-input" :id="option.id" :label="option.label" :disabled="option.disabled" @checkboxUpdate="<strong>optionChangeValue</strong>"&gt;&lt;/checkbox&gt;
&lt;/<strong>select-input</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="label">
                    <subheading>Label</subheading>

                    <well>
                        <select-input label="Label">
                            <span slot="value">{{ labelSelectedValue }}</span>

                            <radio v-for="option in labelData" key="Fixed Options" :id="option.id" name="select-input-fixed" :label="option.label" :disabled="option.disabled" :value="labelSelectedValue" @radioEvent="labelChangeValue"></radio>
                        </select-input>

<preformatted>&lt;select-input <strong>label</strong>="<strong>Label</strong>"&gt;
    // content
&lt;/select-input&gt;</preformatted>
                    </well>
                </section>

                <section id="fixed">
                    <subheading>Fixed</subheading>

                    <well>
                        <select-input :fixed="true">
                            <span slot="value">{{ fixedSelectedValue }}</span>

                            <radio v-for="option in fixedData" key="Fixed Options" :id="option.id" name="select-input-fixed" :label="option.label" :disabled="option.disabled" :value="fixedSelectedValue" @radioEvent="fixedChangeValue"></radio>
                        </select-input>

<preformatted>&lt;select-input <strong>:fixed</strong>="<strong>true</strong>"&gt;
    // content
&lt;/select-input&gt;</preformatted>
                    </well>
                </section>

                <section id="search">
                    <subheading>Search</subheading>

                    <well>
                        <alert>
                            <ul>
                                <li>Review the Single Line Input element's <router-link to="/components/single-line-input">documentation</router-link> to understand how the single line input component is setup.</li>
                            </ul>
                        </alert>

                        <preformatted title="Required Components">path/to/<strong>components/SingleLineInput.vue</strong></preformatted>

<preformatted title="Props"><strong>emptyMessage</strong> {
    type: String,
    default: 'No options'
}</preformatted>

<preformatted title="Computed"><strong>filteredOptionData</strong> () {
    var self = this;
    return this.<strong>optionData</strong>.filter( function ( <strong>option</strong> ) {
        return <strong>option.label</strong>.toLowerCase().indexOf( self.<strong>search</strong>.toLowerCase() ) >= 0;
    });
}</preformatted>

                        <select-input empty-message="No options">
                            <span slot="value">{{ searchSelectedValue }}</span>

                            <div slot="search">
                                <single-line-input v-model="search" id="search-options" type="text" placeholder="Search for option" append-icon="fa fa-search" :hide-label="true">Search</single-line-input>
                            </div>

                            <radio v-for="option in filteredSearchData" key="Search Options" :id="option.id" name="search-input" :label="option.label" :disabled="option.disabled" :value="searchSelectedValue" @radioEvent="searchChangeValue"></radio>
                        </select-input>

<preformatted>&lt;select-input <strong>empty-message</strong>="<strong>No options</strong>"&gt;
    &lt;span slot="value"&gt;{{ <code>optionSelectedValue</code> }}&lt;/span&gt;

    &lt;<strong>span slot</strong>="<strong>search</strong>"&gt;
        &lt;<strong>single-line-input</strong> v-model="<strong>search</strong>" id="search-options" type="text" placeholder="Search for option" append-icon="fa fa-search" :hide-label="true"&gt;Search&lt;/<strong>single-line-input</strong>&gt;
    &lt;/<strong>span</strong>&gt;

    &lt;radio v-for="option in <strong>filteredOptionData</strong>" key="Options" :id="option.id" name="single-select-input" :label="option.label" :disabled="option.disabled" :value="optionSelectedValue" @radioEvent="optionChangeValue"&gt;&lt;/radio&gt;
&lt;/select-input&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#single-select-input">Single Select Input</a>
                </list-item>

                <list-item>
                    <a href="#multi-select-input">Multi-Select Input</a>
                </list-item>

                <list-item>
                    <a href="#label">Label</a>
                </list-item>

                <list-item>
                    <a href="#fixed">Fixed</a>
                </list-item>

                <list-item>
                    <a href="#search">Search</a>
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
        Checkbox: use('EntradaJS/Components/Checkbox.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Radio: use('EntradaJS/Components/Radio.vue'),
        SelectInput: use('EntradaJS/Components/SelectInput.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "SelectInputDocumentation",

        components: importedComponents,

        data () {
            return {
                radioData: [
                    { id: 'single-select-option-1', label: 'Option 1', disabled: false },
                    { id: 'single-select-option-2', label: 'Option 2', disabled: false },
                    { id: 'single-select-option-3', label: 'Option 3', disabled: false }
                ],

                checkboxData: [
                    { id: 'multi-select-option-1', label: 'Option 1', disabled: false },
                    { id: 'multi-select-option-2', label: 'Option 2', disabled: false },
                    { id: 'multi-select-option-3', label: 'Option 3', disabled: false }
                ],

                labelData: [
                    { id: 'label-option-1', label: 'Option 1', disabled: false },
                    { id: 'label-option-2', label: 'Option 2', disabled: false },
                    { id: 'label-option-3', label: 'Option 3', disabled: false }
                ],

                fixedData: [
                    { id: 'fixed-option-1', label: 'Option 1', disabled: false },
                    { id: 'fixed-option-2', label: 'Option 2', disabled: false },
                    { id: 'fixed-option-3', label: 'Option 3', disabled: false }
                ],

                searchData: [
                    { id: 'search-option-1', label: 'Option 1', disabled: false },
                    { id: 'search-option-2', label: 'Option 2', disabled: false },
                    { id: 'search-option-3', label: 'Option 3', disabled: false }
                ],

                search: '',

                radioSelectedValue: 'Select One',
                checkboxSelectCount: 0,
                labelSelectedValue: 'Select One',
                fixedSelectedValue: 'Select One',
                searchSelectedValue: 'Select One'
            }
        },

        computed: {
            filteredSearchData () {
                var self = this;
                return this.searchData.filter( function ( option ) {
                    return option.label.toLowerCase().indexOf( self.search.toLowerCase() ) >= 0;
                });
            }
        },

        methods: {
            radioChangeValue ( newValue ) {
                this.radioSelectedValue = newValue;
            },

            checkboxChangeValue ( isChecked ) {
                if ( isChecked ) {
                    this.checkboxSelectCount++;
                } else if ( !isChecked ) {
                    this.checkboxSelectCount--;
                }
            },

            labelChangeValue ( newValue ) {
                this.labelSelectedValue = newValue;
            },

            fixedChangeValue ( newValue ) {
                this.fixedSelectedValue = newValue;
            },

            searchChangeValue ( newValue ) {
                this.searchSelectedValue = newValue;
            },

            goBack () {
                window.history.length > 1
                ? this.$router.go( -1 )
                : this.$router.push( '/' )
            },

            updatingItemSelected ( value ) {
                this.items.forEach(function ( item ) {
                    if ( item.name == value ) {
                        item.selected = true;
                    } else {
                        item.selected = false;
                    }
                });
            }
        },

        created () {
            this.$emit( 'showing' );
        },

        destroyed() {
            this.$emit( 'showing' );
        }
    }
</script>
