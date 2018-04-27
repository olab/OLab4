<template>
    <div id="popovers">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Popovers</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Popovers</page-heading>

<preformatted title="Required Components">path/to/<strong>components/Popover.vue</strong>
path/to/<strong>components/CardHeading.vue</strong></preformatted>

<preformatted title="Props"><strong>state</strong>: {
    type: Boolean,
    default: false
}

<strong>icon</strong>: {
    type: String
}</preformatted>

                    <preformatted title="Data"><strong>popoverState</strong>: false</preformatted>

                    <alert>
                        <ul>
                            <li>The modal launcing element can be anything that is focusable.</li>
                        </ul>
                    </alert>
                </section>

                <section id="top">
                    <subheading>Top</subheading>

                    <well>
                        <btn @click.native="topPopover = !topPopover">
                            Launch Popover

                            <popover :state="topPopover">
                                <card-heading>Popover Title</card-heading>

                                <div slot="content">
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                </div>
                            </popover>
                        </btn>

<preformatted>&lt;btn <strong>@click.native</strong>="<strong>popoverState = !popoverState</strong>"&gt;
    // button text

    &lt;<strong>popover</strong> <strong>:state</strong>="<strong>popoverState</strong>"&gt;
        &lt;<strong>card-heading</strong>&gt;Title&lt;/<strong>card-heading</strong>&gt;

        &lt;<strong>div slot</strong>="<strong>content</strong>"&gt;
            // content
        &lt;/<strong>div</strong>&gt;
    &lt;/<strong>popover</strong>&gt;
&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="bottom">
                    <subheading>Bottom</subheading>

                    <well>
                        <btn @click.native="bottomPopover = !bottomPopover">
                            Launch Popover

                            <popover :state="bottomPopover" position="bottom">
                                <card-heading>Popover Title</card-heading>

                                <div slot="content">
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                </div>
                            </popover>
                        </btn>

<preformatted>&lt;btn @click.native="popoverState = !popoverState"&gt;
    // button text

    &lt;popover <strong>position</strong>="<strong>bottom</strong>" :state="popoverState"&gt;
        &lt;card-heading&gt;Title&lt;/card-heading&gt;

        &lt;div slot="content"&gt;
            // content
        &lt;/div&gt;
    &lt;/popover&gt;
&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="left">
                    <subheading>Left</subheading>

                    <well>
                        <btn @click.native="leftPopover = !leftPopover">
                            Launch Popover

                            <popover :state="leftPopover" position="left">
                                <card-heading>Popover Title</card-heading>

                                <div slot="content">
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                </div>
                            </popover>
                        </btn>

<preformatted>&lt;btn @click.native="popoverState = !popoverState"&gt;
    // button text

    &lt;popover <strong>position</strong>="<strong>left</strong>" :state="popoverState"&gt;
        &lt;card-heading&gt;Title&lt;/card-heading&gt;

        &lt;div slot="content"&gt;
            // content
        &lt;/div&gt;
    &lt;/popover&gt;
&lt;/btn&gt;</preformatted>
                    </well>
                </section>

                <section id="right">
                    <subheading>Right</subheading>

                    <well>
                        <btn @click.native="rightPopover = !rightPopover">
                            Launch Popover

                            <popover :state="rightPopover" position="right">
                                <card-heading>Popover Title</card-heading>

                                <div slot="content">
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                </div>
                            </popover>
                        </btn>

<preformatted>&lt;btn @click.native="popoverState = !popoverState"&gt;
    // button text

    &lt;popover <strong>position</strong>="<strong>right</strong>" :state="popoverState"&gt;
        &lt;card-heading&gt;Title&lt;/card-heading&gt;

        &lt;div slot="content"&gt;
            // content
        &lt;/div&gt;
    &lt;/popover&gt;
&lt;/btn&gt;</preformatted>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#top">Top</a>
                </list-item>

                <list-item>
                    <a href="#bottom">Bottom</a>
                </list-item>

                <list-item>
                    <a href="#left">Left</a>
                </list-item>

                <list-item>
                    <a href="#right">Right</a>
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
        Btn: use('EntradaJS/Components/Button.vue'),
        CardHeading: use('EntradaJS/Components/CardHeading.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Popover: use('EntradaJS/Components/Popover.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Radio: use('EntradaJS/Components/Radio.vue'),
        SelectInput: use('EntradaJS/Components/SelectInput.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "PopoverDocumentation",

        components: importedComponents,

        data () {
            return {
                topPopover: false,
                bottomPopover: false,
                leftPopover: false,
                rightPopover: false
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

<style lang="scss">
    #popovers {
        .btn {
            position: relative;
        }
    }
</style>
