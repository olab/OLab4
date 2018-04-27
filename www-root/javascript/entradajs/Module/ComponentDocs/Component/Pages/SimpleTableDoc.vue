<template>
    <div id="alerts">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Simple Tables</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Simple Tables</page-heading>

<preformatted title="Required Components">path/to/<strong>components/DataTable.vue</strong>
path/to/<strong>components/TableRow.vue</strong>
path/to/<strong>components/HeaderRow.vue</strong>
path/to/<strong>components/TableCell.vue</strong></preformatted>
                </section>

                <section id="default">
                    <subheading>Default</subheading>

                    <well>
                        <simple-table>
                            <table-row>
                                <table-cell>Cell 1</table-cell>
                                <table-cell :large="true">Cell 2</table-cell>
                            </table-row>
                        </simple-table>

<preformatted>&lt;<strong>simple-table</strong>&gt;
    &lt;<strong>table-row</strong>&gt;
        &lt;<strong>table-cell</strong>&gt;
            // content
        &lt;/<strong>table-cell</strong>&gt;

        &lt;<strong>table-cell</strong> <strong>:large</strong>="<strong>true</strong>"&gt;
            // content
        &lt;/<strong>table-cell</strong>&gt;
    &lt;/<strong>table-row</strong>&gt;
&lt;/<strong>simple-table</strong>&gt;</preformatted>
                    </well>
                </section>

                <section id="header">
                    <subheading>Header</subheading>

                    <well>
                        <simple-table>
                            <header-row>
                                <table-cell>Header Cell 1</table-cell>
                                <table-cell>Header Cell 2</table-cell>
                            </header-row>

                            <table-row>
                                <table-cell>Cell 1 Content</table-cell>
                                <table-cell :large="true">Cell 2 Content</table-cell>
                            </table-row>
                        </simple-table>

<preformatted>&lt;simple-table&gt;
    &lt;<strong>header-row</strong>&gt;
        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;

        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;
    &lt;/<strong>header-row</strong>&gt;

    &lt;table-row&gt;
        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;

        &lt;table-cell :large="true"&gt;
            // content
        &lt;/table-cell&gt;
    &lt;/table-row&gt;
&lt;/simple-table&gt;</preformatted>
                    </well>
                </section>

                <section id="left-cap">
                    <subheading>Left End Cap</subheading>
                    <p>A Left End Cap is a column that is fixed to the left side of the data table.</p>

                    <well>
                        <simple-table>
                            <div slot="left-end-cap">
                                <table-row>
                                    <table-cell>Left End Cap</table-cell>
                                </table-row>
                            </div>

                            <table-row>
                                <table-cell>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                                <table-cell>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                                <table-cell :large="true">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                            </table-row>
                        </simple-table>

<preformatted>&lt;simple-table&gt;
    &lt;<strong>div slot</strong>="<strong>left-end-cap</strong>"&gt;
        &lt;table-row&gt;
            &lt;table-cell&gt;
                // content
            &lt;/table-cell&gt;
        &lt;/table-row&gt;
    &lt;/<strong>div</strong>&gt;

    &lt;table-row&gt;
        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;

        &lt;table-cell :large="true"&gt;
            // content
        &lt;/table-cell&gt;
    &lt;/table-row&gt;
&lt;/simple-table&gt;</preformatted>
                    </well>
                </section>

                <section id="right-cap">
                    <subheading>Right End Cap</subheading>
                    <p>A Right End Cap is a column that is fixed to the right side of the data table.</p>

                    <well>
                        <simple-table>
                            <table-row>
                                <table-cell>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                                <table-cell>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                                <table-cell :large="true">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                            </table-row>

                            <div slot="right-end-cap">
                                <table-row>
                                    <table-cell>Right End Cap</table-cell>
                                </table-row>
                            </div>
                        </simple-table>

<preformatted>&lt;simple-table&gt;
    &lt;table-row&gt;
        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;

        &lt;table-cell :large="true"&gt;
            // content
        &lt;/table-cell&gt;
    &lt;/table-row&gt;

    &lt;<strong>div slot</strong>="<strong>right-end-cap</strong>"&gt;
        &lt;table-row&gt;
            &lt;table-cell&gt;
                // content
            &lt;/table-cell&gt;
        &lt;/table-row&gt;
    &lt;/<strong>div</strong>&gt;
&lt;/simple-table&gt;</preformatted>
                    </well>
                </section>

                <section id="cap-headers">
                    <subheading>End Cap Headers</subheading>

                    <well>
                        <simple-table>
                            <div slot="left-end-cap">
                                <header-row>
                                    <table-cell>Cap Header</table-cell>
                                </header-row>

                                <table-row>
                                    <table-cell>Left End Cap</table-cell>
                                </table-row>
                            </div>

                            <header-row>
                                <table-cell>Header Cell 1</table-cell>
                                <table-cell>Header Cell 2</table-cell>
                                <table-cell>Header Cell 3</table-cell>
                            </header-row>

                            <table-row>
                                <table-cell>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                                <table-cell>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                                <table-cell :large="true">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</table-cell>
                            </table-row>

                            <div slot="right-end-cap">
                                <header-row>
                                    <table-cell>Cap Header</table-cell>
                                </header-row>

                                <table-row>
                                    <table-cell>Right End Cap</table-cell>
                                </table-row>
                            </div>
                        </simple-table>

<preformatted>&lt;simple-table&gt;
    &lt;div slot="left-end-cap"&gt;
        &lt;<strong>header-row</strong>&gt;
            &lt;table-cell&gt;
                // content
            &lt;/table-cell&gt;
        &lt;/<strong>header-row</strong>&gt;

        &lt;table-row&gt;
            &lt;table-cell&gt;
                // content
            &lt;/table-cell&gt;
        &lt;/table-row&gt;
    &lt;/div&gt;

    &lt;<strong>header-row</strong>&gt;
        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;

        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;
    &lt;/<strong>header-row</strong>&gt;

    &lt;table-row&gt;
        &lt;table-cell&gt;
            // content
        &lt;/table-cell&gt;

        &lt;table-cell :large="true"&gt;
            // content
        &lt;/table-cell&gt;
    &lt;/table-row&gt;

    &lt;div slot="right-end-cap"&gt;
        &lt;<strong>header-row</strong>&gt;
            &lt;table-cell&gt;
                // content
            &lt;/table-cell&gt;
        &lt;/<strong>header-row</strong>&gt;

        &lt;table-row&gt;
            &lt;table-cell&gt;
                // content
            &lt;/table-cell&gt;
        &lt;/table-row&gt;
    &lt;/div&gt;
&lt;/simple-table&gt;</preformatted>
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
                    <a href="#header">Header</a>
                </list-item>

                <list-item>
                    <a href="#left-cap">Left End Cap</a>
                </list-item>

                <list-item>
                    <a href="#right-cap">Right End Cap</a>
                </list-item>

                <list-item>
                    <a href="#cap-headers">End Cap Headers</a>
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
        Btn: use('EntradaJS/Components/Button.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        HeaderRow: use('EntradaJS/Components/HeaderRow.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        SimpleTable: use('EntradaJS/Components/SimpleTable.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        TableCell: use('EntradaJS/Components/TableCell.vue'),
        TableRow: use('EntradaJS/Components/TableRow.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "SimpleTableDocumentation",

        components: importedComponents,

        data () {
            return {
                users: [
                    { id: 1, name: 'Joe', user_number: 1233456, login: true },
                    { id: 2, name: 'Sam', user_number: 2736542, login: false }
                ]
            };
        },

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
