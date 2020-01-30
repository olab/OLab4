<template>
    <div id="buttons">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Advanced Tables</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Advanced Tables</page-heading>

                    <alert>
                        <ul>
                            <li>This component has known issues.</li>
                        </ul>
                    </alert>

                    <preformatted title="Required Components">path/to/<strong>components/VueBootstrapTable.vue</strong></preformatted>

                    <preformatted title="Name">name: "<strong>AdvancedTable</strong>"</preformatted>

<preformatted title="Props"><strong>columns</strong>: {
    type: Array,
    required: true,
},

<strong>values</strong>: {
    type: Array,
    required: false,
},

<strong>sortable</strong>: {
    type: Boolean,
    required: false,
    default: false,
},

<strong>multiColumnSortable</strong>: {
    type: Boolean,
    required: false,
    default: false,
},

<strong>showFilter</strong>: {
    type: Boolean,
    required: false,
    default: false,
},

<strong>filterCaseSensitive</strong>: {
    type: Boolean,
    required: false,
    default: false,
},

<strong>showColumnPicker</strong>: {
    type: Boolean,
    required: false,
    default: false,
},

<strong>paginated</strong>: {
    type: Boolean,
    required: false,
    default: false,
},

<strong>pageSize</strong>: {
    type: Number,
    required: false,
    default: 10,
}</preformatted>

<preformatted title="Data"><strong>columns</strong>: [
    {
        title:"ID",
    },
    {
        title:"Name",
        visible: true,
        editable: true,
    },
    {
        title:"Age",
        visible: true,
        editable: true,
    },
    {
        title:"Country",
        visible: true,
        editable: true,
    }
],

<strong>values</strong>: [
    {
        "ID": 1,
        "Name": "John",
        "Country": "UK",
        "Age": 25,
    },
    {
        "ID": 2,
        "Name": "Mary",
        "Country": "France",
        "Age": 30,
    },
    {
        "ID": 3,
        "Name": "Ana",
        "Country": "Portugal",
        "Age": 20,
    }
]</preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values"></advanced-table>

                        <preformatted>&lt;<strong>advanced-table</strong> <strong>:columns</strong>="columns" <strong>:values</strong>="values"&gt;&lt;/<strong>advanced-table</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="filter">
                    <subheading>Filter</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values" :show-filter="true"></advanced-table>

                        <preformatted>&lt;advanced-table :columns="columns" :values="values" <strong>:show-filter</strong>="<strong>true</strong>"&gt;&lt;/advanced-table&gt;</preformatted>
                    </well>
                </section>

                <section id="filter-case-sensitive">
                    <subheading>Filter Case Sensitive</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values" :show-filter="true" :filter-case-sensitive="true"></advanced-table>

                        <preformatted>&lt;advanced-table :columns="columns" :values="values" <strong>:show-filter</strong>="<strong>true</strong>" <strong>:filter-case-sensitive</strong>="<strong>true</strong>"&gt;&lt;/advanced-table&gt;</preformatted>
                    </well>
                </section>

                <section id="column-picker">
                    <subheading>Column Picker</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values" :show-column-picker="true"></advanced-table>

                        <preformatted>&lt;advanced-table :columns="columns" :values="values" <strong>:show-column-picker</strong>="<strong>true</strong>"&gt;&lt;/advanced-table&gt;</preformatted>
                    </well>
                </section>

                <section id="paginated">
                    <subheading>Paginated</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values" :paginated="true" :page-size="1"></advanced-table>

                        <preformatted>&lt;advanced-table :columns="columns" :values="values" <strong>:paginated</strong>="<strong>true</strong>" <strong>:page-size</strong>="<strong>1</strong>"&gt;&lt;/advanced-table&gt;</preformatted>
                    </well>
                </section>

                <section id="sortable">
                    <subheading>Sortable</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values" :sortable="true"></advanced-table>

                        <preformatted>&lt;advanced-table :columns="columns" :values="values" <strong>:sortable</strong>="<strong>true</strong>"&gt;&lt;/advanced-table&gt;</preformatted>
                    </well>
                </section>

                <section id="kitchen-sink">
                    <subheading>Kitchen Sink</subheading>

                    <well>
                        <advanced-table :columns="columns" :values="values" :show-filter="true" :show-column-picker="true" :sortable="true" :paginated="true" :page-size="1" :filter-case-sensitive="true"></advanced-table>

                        <preformatted>&lt;<strong>advanced-table</strong> :columns="columns" :values="values" :show-filter="true" :show-column-picker="true" :sortable="true" :paginated="true" :filter-case-sensitive="true"&gt;&lt;/<strong>advanced-table</strong>&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configuration</div>

                <list-item>
                    <a href="#default">Default</a>
                </list-item>

                <list-item>
                    <a href="#filter">Filter</a>
                </list-item>

                <list-item>
                    <a href="#filter-case-sensitive">Filter Case Sensitive</a>
                </list-item>

                <list-item>
                    <a href="#column-picker">Column Picker</a>
                </list-item>

                <list-item>
                    <a href="#paginated">Paginated</a>
                </list-item>

                <list-item>
                    <a href="#sortable">Sortable</a>
                </list-item>

                <list-item>
                    <a href="#kitchen-sink">Kitchen Sink</a>
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
        AdvancedTable: use('EntradaJS/Components/VueBootstrapTable.vue'),
        Alert: use('EntradaJS/Components/Alert.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "AdvancedTableDocumentation",
        store,
        components: importedComponents,

        methods: {
            goBack () {
                window.history.length > 1
                ? this.$router.go(-1)
                : this.$router.push('/')
            }
        },

        computed: {
            columns () {
                return this.$store.state.dataTableDocColumns;
            },

            values () {
                return this.$store.state.dataTableDocValues;
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
