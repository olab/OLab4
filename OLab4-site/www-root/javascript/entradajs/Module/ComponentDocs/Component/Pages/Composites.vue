<template>
    <div id="composites">
        <content-inner v-if="showPageLinks">
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li>Composites</li>
            </ul>

            <container>
                <page-heading>Composites<badge type="success" :large="true">{{ composites.length }}</badge></page-heading>

                <single-line-input v-model="search" id="search-composites" type="search" placeholder="Type here to begin searching" append-icon="fa fa-search">Search Composites</single-line-input>

                <card v-for="composite in filteredComposites" :key="composite.title" :link="composite.link" :icon="composite.icon">
                    <header>
                        <card-heading>{{ composite.title }}</card-heading>
                    </header>

                    <div slot="content">
                        <p>{{ composite.description }}</p>
                    </div>

                    <footer slot="footer">
                        <div>View Documentation</div>
                    </footer>
                </card>
            </container>
        </content-inner>

        <router-view @showing="showPageLinks = !showPageLinks"></router-view>
    </div>
</template>

<script>
    const importedComponents = {
        ContentInner: use('./../Structure/ContentInner.vue'),
        Container: use('EntradaJS/Components/Layout/Container.vue'),
        Badge: use('EntradaJS/Components/Badge.vue'),
        CardHeading: use('EntradaJS/Components/CardHeading.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue')
    };

    module.exports = {
        name: "Composites",

        components: importedComponents,

        data () {
            return {
                search: '',

                showPageLinks: true
            }
        },

        computed: {
            composites () {
                return this.$store.state.compositesData;
            },

            filteredComposites () {
                var self=this;
                return this.composites.filter( function (obj) {
                    return obj.title.toLowerCase().indexOf( self.search.toLowerCase() )>=0;
                });
            }
        }
    }
</script>

<style lang="scss">
    #composites {
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
