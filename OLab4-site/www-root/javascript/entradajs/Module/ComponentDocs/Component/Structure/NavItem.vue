<template>
    <li class="nav-item">
        <a :href="link" :exact="exact" @click.native="closingMobileMainNav">
            <span :class="icon"></span>
            <span class="nav-title"><slot /></span>
            <badge v-if="badge" type="success" v-text="badge"></badge>
        </a>
    </li>
</template>

<script>
    const Badge = use('EntradaJS/Components/Badge.vue');
    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "NavItem",
        store,
        components: { Badge },

        props: {
            link: {
                type: String,
                required: true
            },

            icon: {
                type: String,
                required: true
            },

            exact: {
                type: Boolean,
                default: false
            },

            badge: {
                type: Number
            }
        },

        methods: {
            closingMobileMainNav () {
                if ( window.innerWidth > 0 && window.innerWidth < 600 ) {
                    this.$store.commit( 'toggleMainNavCollapsed' );
                }
            }
        }
    }
</script>

<style lang="scss">
    @import 'theme.scss';

    .nav-item {
        color: $icon-color;
        list-style: none;
        margin: 0;
        padding: 0;

        a {
            @include align-items(center);
            @include display-flex;
            @include transition(background-color 0.15s, color 0.15s);
            color: $icon-color;
            padding: $spacing-sm $spacing-sm;
            position: relative;
            width: 100%;

            &:hover,
            &:focus {
                background-color: $main-nav-hov;
                color: $white;
                text-decoration: none;
            }

            &.router-link-active {
                @include shadow(inset 4px 0 0 $brand-primary);
                background-color: $main-nav-active;
                color: $white;

                &:hover,
                &:focus {
                    background-color: $main-nav-hov;
                }
            }

            .fa {
                @include no-shrink;
                font-size: $type-size;
                padding: 0 $spacing-sm;
            }

            .nav-title {
                font-family: $primary-font;
                font-size: $type-size-sm;
            }

            .badge {
                font-size: 14px;
                margin-left: auto;
                padding: $spacing-xs;
                position: relative;
                top: 1px;
            }
        }
    }

    @media #{$tablet} {
        .main-nav {
            .nav-item {
                a {
                    @include display-flex;
                    @include justify-content(center);
                    padding: $spacing-lg;
                }

                .fa {
                    font-size: $type-size-md;
                }

                .nav-title {
                    display: none;
                }
            }
        }
    }

    @media #{$mobile} {
        .main-nav,
        .main-nav.collapsed {
            .nav-item {
                a {
                    @include justify-content(flex-start);
                    color: $white;
                    padding-left: 30px;

                    &.router-link-active {
                        @include shadow(none);
                        background-color: transparent;

                        &:hover,
                        &:focus {
                            background-color: $main-nav-hov;
                        }

                        &::before {
                            @include border-radius(50%);
                            background-color: $brand-primary;
                            content: "";
                            height: 10px;
                            left: 15px;
                            position: absolute;
                            top: 28px;
                            width: 10px;
                        }
                    }

                    &:hover {
                        padding-left: 40px;
                    }

                    .fa {
                        font-size: $type-size-md;
                        position: relative;
                        top: 2px;
                    }

                    .nav-title {
                        display: block;
                        font-size: $type-size-md - 0.1em;
                    }
                }
            }
        }

        .main-nav {
            .nav-item {
                a {
                    @include transition(padding-left 0.2s, background-color 0.2s, color 0.2s, opacity 0.5s 0.25s);
                    opacity: 1;
                }
            }
        }

        &.collapsed {
            .nav-item {
                a {
                    @include transition(padding-left 0.2s, background-color 0.2s, color 0.2s, opacity 0s);
                    opacity: 0;
                }
            }
        }
    }
</style>
