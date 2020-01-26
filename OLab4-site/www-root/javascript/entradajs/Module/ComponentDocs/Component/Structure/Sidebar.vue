<template>
    <div :class="{'sidebar': true, 'collapsed': collapsed}">
        <button class="sidebar-toggle" @click="togglingSidebar"></button>

        <div class="sidebar-inner">
            <slot v-if="!collapsed" />
        </div>
    </div>
</template>

<script>
    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "Sidebar",
        store,
        computed: {
            collapsed () {
                return this.$store.state.sidebarCollapsed;
            }
        },

        methods: {
            togglingSidebar () {
                this.$store.commit('toggleSidebarCollapsed');
            }
        }
    }
</script>

<style lang="scss">
    @import 'theme.scss';

    .sidebar {
        background-color: $sidebar;
        bottom: 0;
        overflow-y: scroll;
        position: absolute;
        right: 0;
        top: 0;
        width: 400px;

        &::-webkit-scrollbar-track {
            background-color: $sidebar-track;
        }

        &::-webkit-scrollbar {
            background-color: $sidebar-track;
            height: 8px;
            width: 8px;
        }

        &::-webkit-scrollbar-thumb {
            @include border-radius(4px);
            @include shadow(0 0 0 4px $sidebar);
            background-color: $sidebar-bar;
        }

        .sidebar-toggle {
            @include appearance(none);
            @include border-radius(0);
            @include no-shrink;
            @include shadow(inset 0 -1px 0 $border-color);
            @include transition(background-color 0.2s);
            background-color: $sidebar;
            border: none;
            cursor: pointer;
            height: 40px;
            position: fixed;
            right: 0;
            top: 70px;
            width: 400px;
            z-index: 2;

            &:hover,
            &:focus {
                background-color: $sidebar-hov;
            }

            &::before {
                color: $text-disabled;
                content: "\f101";
                font: $icon-font;
                left: 20px;
                position: absolute;
                top: 10px;
            }
        }

        .sidebar-inner {
            padding: $spacing-xl;
            position: relative;
            top: 40px;
            width: 100%;
        }

        &.collapsed {
            width: 70px;

            .sidebar-toggle {
                width: 70px;

                &::before {
                    @include transform(rotate(180deg));
                    left: 30px;
                    top: 12px;
                }
            }

            .sidebar-inner {
                padding: 0;
            }
        }
    }

    @media #{$desktop} {
        .sidebar {
            @include shadow((-5px) 0 25px rgba(0, 0, 0, 0.08));

            .sidebar-toggle {
                @include shadow(0 1px 0 $border-color);
                width: 70px;

                &::before {
                    left: 30px;
                }
            }

            &.collapsed {
                @include shadow(0 1px 0 $border-color);
                width: 0;
            }
        }
    }

    @media (max-width: 600px) {
        .sidebar {
            @include shadow(none);
            right: 0;
            width: 100%;

            &.collapsed {
                width: 0;
            }

            .sidebar-inner {
                padding: $spacing-lg $spacing-md;
            }
        }
    }

    @media #{$mobile} {
        .sidebar {
            .sidebar-toggle {
                width: 70px !important;

                &::before {
                    left: 30px !important;
                }
            }
        }
    }
</style>
