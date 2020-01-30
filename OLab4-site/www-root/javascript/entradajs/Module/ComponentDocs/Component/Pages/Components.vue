<template>
    <div id="components">
        <content-inner v-if="showPageLinks">
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li>Components</li>
            </ul>

            <container>
                <page-heading>Components<badge type="success" :large="true">{{ components.length }}</badge></page-heading>

                <single-line-input v-model="search" id="search-components" type="search" placeholder="Type here to begin searching" append-icon="fa fa-search">Search Components</single-line-input>

                <card v-for="component in filteredComponents" :key="component.title" :link="component.link" :icon="component.icon">
                    <header>
                        <card-heading>{{ component.title }}</card-heading>
                    </header>

                    <div slot="content">
                        <p>{{ component.description }}</p>
                    </div>

                    <footer slot="footer">
                        <div>View Documentation</div>
                    </footer>
                </card>
            </container>
        </content-inner>

        <!--
        <router-view @showing="showPageLinks = !showPageLinks"></router-view>
        -->
    </div>
</template>

<script>
    const importedComponents = {
        ContentInner: use('./../Structure/ContentInner.vue'),
        Container: use('EntradaJS/Components/Layout/Container.vue'),
        Badge: use('EntradaJS/Components/Badge.vue'),
        Card: use('EntradaJS/Components/Card.vue'),
        CardHeading: use('EntradaJS/Components/CardHeading.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue')
    };

    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "Components",
        store,
        components: importedComponents,

        data () {
            return {
                showPageLinks: true,

                search: ''
            }
        },

        computed: {
            components () {
                return this.$store.state.componentsData;
            },

            filteredComponents () {
                var self = this;
                return this.components.filter( function (comp) {
                    return comp.title.toLowerCase().indexOf( self.search.toLowerCase() )>=0;
                });
            }
        }
    }
</script>

<style lang="scss">
    #components {
        .page-heading {
            .badge {
                font-size: .7em;
                margin-left: 8px;
                position: relative;
                top: -3px;
            }
        }
    }
</style>
