<template>
    <div class="content-inner">
        <div :class="{'breadcrumb': true, 'expanded': expanded}">
            <container>
                <div class="breadcrumb-wrapper">
                    <slot name="breadcrumb" />
                </div>
            </container>
        </div>

        <div :class="{'content-wrapper': true, 'expanded': expanded}">
            <div class="content">
                <slot />
            </div>
        </div>
    </div>
</template>

<script>
    const Container = use('EntradaJS/Components/Layout/Container.vue');
    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "ContentInner",
        store,
        components: { Container },

        computed: {
            expanded () {
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

    .content-inner {
        .content-wrapper {
            bottom: 0;
            left: 0;
            overflow-y: scroll;
            position: absolute;
            right: 400px;
            top: 39px;

            &::-webkit-scrollbar-track {
                background-color: $background-medium;
            }

            &::-webkit-scrollbar {
                background-color: $background-medium;
                height: 8px;
                width: 8px;
            }

            &::-webkit-scrollbar-thumb {
                @include border-radius(4px);
                @include shadow(0 0 0 4px $body);
                background-color: $border-color;
            }

            &.expanded {
                right: 70px;
            }

            .content {
                padding: $spacing-xl 0;
            }

            section {
                margin-bottom: $spacing-xl;

                & > * {
                    &:last-child {
                        margin-bottom: 0;
                    }
                }
            }
        }

        .breadcrumb {
            @include shadow(inset 0 -6px 0 $background-medium);
            background-color: $body;
            height: 39px;
            left: 0;
            position: absolute;
            right: 400px;
            z-index: 1;

            &::before {
                background-color: $border-color;
                bottom: -1px;
                content: "";
                display: block;
                height: 1px;
                position: absolute;
                width: 100%;
            }

            .ejs-container {
                &::after {
                    background: -moz-linear-gradient(left, rgba(244, 247, 250, 0) 0%, rgba(244, 247, 250, 1) 100%);
                    background: -webkit-linear-gradient(left, rgba(244, 247, 250, 0) 0%, rgba(244, 247, 250, 1) 100%);
                    background: linear-gradient(to right, rgba(244, 247, 250, 0) 0%, rgba(244, 247, 250, 1) 100%);
                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#00f4f7fa', endColorstr='#f4f7fa',GradientType=1 );
                    bottom: 6px;
                    content: "";
                    right: 0;
                    position: absolute;
                    top: 0;
                    width: $gutter / 2;
                }

                .breadcrumb-wrapper {
                    margin: 0 -15px;

                    &::after {
                        background: -moz-linear-gradient(left, rgba($body-rgb, 1) 0%, rgba($body-rgb, 0) 100%);
                        background: -webkit-linear-gradient(left, rgba($body-rgb, 1) 0%, rgba($body-rgb, 0) 100%);
                        background: linear-gradient(to right, rgba($body-rgb, 1) 0%, rgba($body-rgb, 0) 100%);
                        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr=$body, endColorstr=$body,GradientType=1 );
                        bottom: 6px;
                        content: "";
                        left: 0;
                        position: absolute;
                        top: 0;
                        width: $gutter / 2;
                    }

                    ul {
                        @include display-flex;
                        height: 39px;
                        margin: 0;
                        overflow-x: scroll;
                        padding: 4px 0 0 0;
                        position: relative;
                        white-space: nowrap;
                        width: 100%;

                        &::-webkit-scrollbar-track {
                            background-color: $background-medium;
                        }

                        &::-webkit-scrollbar {
                            background-color: $background-medium;
                            height: 6px;
                            width: 6px;
                        }

                        &::-webkit-scrollbar-thumb {
                            @include border-radius(3px);
                            @include shadow(0 0 0 3px $body);
                            background-color: $border-color;
                        }

                        li {
                            font-size: 16px;
                            list-style: none;
                            margin: 0;
                            position: relative;

                            a {
                                font-size: 16px;
                            }

                            &::after {
                                content: "/";
                                font-size: 16px;
                                padding: 0 8px;
                            }

                            &:first-child {
                                padding-left: $gutter / 2;
                            }

                            &:last-child {
                                padding-right: $gutter / 2;

                                &::after {
                                    content: "";
                                    padding: 0;
                                }
                            }
                        }
                    }
                }
            }

            &.expanded {
                right: 70px;
            }
        }

        &:only-child {
            .content-wrapper,
            .breadcrumb {
                right: 0;

                &.expanded {
                    right: 0;
                }
            }
        }
    }

    @media #{$desktop} {
        .content-inner {
            .content-wrapper {
                right: 0;
                top: 40px;

                &.expanded {
                    right: 0;
                }
            }

            .breadcrumb {
                height: 40px;
                right: 0;

                .ejs-container {
                    .breadcrumb-wrapper {
                        ul {
                            height: 40px;
                        }
                    }
                }

                &.expanded {
                right: 0;
                }
            }
        }
    }

    @media #{$mobile} {
        .content-inner {
            .breadcrumb {
                right: 70px;

                &.expanded {
                    right: 70px;
                }
            }
        }
    }
</style>
