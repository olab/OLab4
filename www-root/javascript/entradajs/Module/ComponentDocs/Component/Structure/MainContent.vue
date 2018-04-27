<template>
    <main :class="{ 'main-content': true, 'expanded': expanded }">
        <slot />
    </main>
</template>

<script>
    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "MainContent",
        store,
        computed: {
            expanded () {
                return this.$store.state.mainNavCollapsed;
            }
        },

        created () {
            if ( window.innerWidth > 0 && window.innerWidth < 600 ) {
                this.$store.commit( 'mainNavIsCollapsed' );
            }
        }
    }
</script>

<style lang="scss">
    @import 'theme.scss';

    .main-content {
        @include display-flex;
        bottom: 0;
        overflow-x: hidden;
        overflow-y: scroll;
        position: absolute;
        right: 0;
        top: 70px;
        width: calc(100% - 230px);

        &.expanded {
            width: calc(100% - 70px);
        }
    }

    @media #{$tablet} {
        .main-content {
            width: calc(100% - 70px);
        }
    }

    @media #{$mobile} {
        .main-content {
            width: 100%;

            &.expanded {
                width: 100%;
            }
        }
    }
</style>
