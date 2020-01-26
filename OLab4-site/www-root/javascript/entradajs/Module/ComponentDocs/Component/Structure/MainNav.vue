<template>
    <aside :class="{ 'main-nav': true, 'collapsed': collapsed }">
        <button tabindex="0" class="nav-toggle" @click="togglingMainNav"></button>

        <div class="main-nav-inner">
            <nav-group>
                <nav-item link="#/components/" icon="fa fa-home" :exact="true">Home</nav-item>
                <nav-item link="#/components/components/" icon="fa fa-th" :badge="components.length">Components</nav-item>
                <nav-item link="#/components/composites" icon="fa fa-th-large" :badge="composites.length">Composites</nav-item>
            </nav-group>
        </div>
    </aside>
</template>

<script>
    const importedComponents = {
        NavGroup: use('./NavGroup.vue'),
        NavItem: use('./NavItem.vue')
    };

    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "MainNav",
        store,
        components: importedComponents,

        computed: {
            collapsed () {
                return this.$store.state.mainNavCollapsed;
            },

            components () {
                return this.$store.state.componentsData;
            },

            composites () {
                return this.$store.state.compositesData;
            }
        },

        methods: {
            togglingMainNav () {
                this.$store.commit( 'toggleMainNavCollapsed' );
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

    .main-nav {
        background-color: $main-nav;
        bottom: 0;
        left: 0;
        overflow-y: scroll;
        position: absolute;
        top: 106px;
        width: 230px;

        &::-webkit-scrollbar-track {
            background-color: $main-nav-track;
            height: 8px;
            width: 8px;
        }

        &::-webkit-scrollbar {
            background-color: $main-nav-track;
            height: 8px;
            width: 8px;
        }

        &::-webkit-scrollbar-thumb {
            @include border-radius(4px);
            @include shadow(0 0 0 4px $main-nav);
            background-color: $main-nav-bar;
        }

        .nav-toggle {
            @include appearance(none);
            @include border-radius(0);
            @include shadow(inset 0 -1px 0 $brand-secondary);
            @include transition(background-color 0.15s);
            background-color: $main-nav;
            border: none;
            cursor: pointer;
            display: block;
            height: 40px;
            margin: 0;
            padding: 0;
            position: fixed;
            top: 70px;
            width: 230px;
            z-index: 1;

            &:hover,
            &:focus {
                background-color: $main-nav-hov;
            }

            &::before {
                color: $icon-color;
                content: "\f100";
                font: $icon-font;
                position: absolute;
                right: 20px;
                top: 10px;
            }
        }

        &.collapsed {
            width: 70px;

            .nav-toggle {
                width: 70px;

                &::before {
                    @include transform(rotate(180deg));
                    right: 30px;
                    top: 11px;
                }
            }

            .nav-item {
                a {
                    @include display-flex;
                    @include justify-content(center);
                    padding: $spacing-lg;

                    .badge {
                        @include shadow(0 0 0 3px $main-nav);
                        @include transition(box-shadow 0.15s);
                        position: absolute;
                        right: 0;
                        top: 5px;
                    }

                    &:hover,
                    &:focus {
                        .badge {
                            @include shadow(0 0 0 3px $main-nav-hov);
                        }
                    }

                    &.router-link-active {
                        .badge {
                            @include shadow(0 0 0 3px $main-nav-active);
                        }

                        &:hover,
                        &:focus {
                            .badge {
                                @include shadow(0 0 0 3px $main-nav-hov);
                            }
                        }
                    }
                }

                .fa {
                    font-size: $type-size-md;
                    margin-left: 4px;
                }

                .nav-title {
                    display: none;
                }
            }
        }
    }

    @media #{$tablet} {
        .main-nav {
            top: 70px;
            width: 70px;

            .main-nav-inner {
                padding-top: 0;
            }

            .nav-toggle {
                display: none;
            }
        }
    }

    @media #{$mobile} {
        .main-nav {
            @include transition(bottom 0.3s, visibility 0.3s);
            display: block;
            width: 100%;
            visibility: visible;
            z-index: 3;

            &.collapsed {
                @include transition(bottom 0.2s, visibility 0.2s);
                bottom: 100%;
                width: 100%;
                visibility: hidden;
            }
        }
    }
</style>
