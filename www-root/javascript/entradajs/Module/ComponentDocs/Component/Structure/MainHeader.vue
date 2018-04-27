<template>
    <header class="main-header">
        <div>
            <img class="brand" src="/src/Module/ComponentDocs/Assets/images/brand.svg" />
            <div class="brand-text">Component Library</div>
        </div>

        <button :class="{ 'menu-toggle': true, 'close': !close }" @click="togglingMainNav">
            <div>
                <div class="toggle-bar top-bar"></div>
                <div class="toggle-bar bottom-bar"></div>
            </div>
        </button>
    </header>
</template>

<script>
    const store = use('./../../Store/Store.js');

    module.exports = {
        name: "MainHeader",
        store,
        computed: {
            close () {
                return this.$store.state.mainNavCollapsed;
            }
        },

        methods: {
            togglingMainNav () {
                this.testData = !this.testData;
                this.$store.commit('toggleMainNavCollapsed');
            }
        },

        created () {
            if (window.innerWidth > 0 && window.innerWidth < 600) {
                this.$store.commit('mainNavIsCollapsed');
            }
        }
    }
</script>

<style lang="scss">
    @import 'theme.scss';

    .main-header {
        @include align-items(center);
        @include display-flex;
        background-color: $brand-secondary;
        height: 70px;
        position: fixed;
        top: 0;
        width: 100%;

        .brand {
            height: 24px;
            margin-left: $spacing-xl;
        }

        .brand-text {
            color: $white;
            font-family: $primary-font;
            font-size: $type-size-sm;
            line-height: $type-size-sm;
            margin-left: $spacing-xl;
        }

        .menu-toggle {
            @include align-items(center);
            @include appearance(none);
            @include border-radius($border-radius);
            @include flex-direction(column);
            @include justify-content(center);
            @include no-shrink;
            @include transition(background-color 0.2s);
            background-color: transparent;
            border: none;
            cursor: pointer;
            display: none;
            height: 40px;
            margin-left: auto;
            margin-right: $spacing-xl / 2;
            position: relative;
            width: 40px;

            &:hover,
            &:focus {
                background-color: rgba(0, 0, 0, 0.2);
            }

            .toggle-bar {
                @include transition(transform 0.2s, top 0.4s, bottom 0.4s);
                background-color: rgb(255, 255, 255);
                height: 4px;
                width: 30px;
                position: relative;
                border-radius: 2px;
                margin: 6px 0px;
            }
        }
    }

    @media #{$mobile} {
        .main-header {
            .brand {
                height: 23px;
                margin-left: $spacing-xl / 2;
            }

            .brand-text {
                margin-left: $spacing-xl / 2;
            }

            .menu-toggle {
                @include display-flex;

                &.close {
                    .top-bar {
                        transform: rotate(45deg);
                        top: 5px;
                    }

                    .bottom-bar {
                        transform: rotate(-45deg);
                        bottom: 5px;
                    }
                }
            }
        }
    }
</style>
