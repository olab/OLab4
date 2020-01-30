<template>
    <div id="app">
        <div id="home">
            <content-inner>
                <ul slot="breadcrumb">
                    <li>Home</li>
                </ul>

                <container>
                    <page-heading>Entrada Component Library</page-heading>

                    <lead>A library of components with functionality and styles built for Entrada.</lead>

                    <subheading>Components</subheading>
                    <p>Components are the base components of the Entrada Component Library and are built to be flexible, reusable, and accessible. Examples of Components include buttons, badges, dropdown menus, links and lists.</p>
                    <a href="#/components/components/">View Components</a>

                    <subheading>Composites</subheading>
                    <p>Composites are comprised of Components to construct specific parts of a user interface.</p>
                    <a href="#/components/objects/">View Composites</a>
                </container>
            </content-inner>
        </div>
    </div>
</template>

<script>
    const importedComponents = {
        ContentInner: use('./../Structure/ContentInner.vue'),
        Container: use('EntradaJS/Components/Layout/Container.vue'),
        Lead: use('EntradaJS/Components/Lead.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue')
    };

    module.exports = {
        name: 'app',

        components: importedComponents
    };
</script>
