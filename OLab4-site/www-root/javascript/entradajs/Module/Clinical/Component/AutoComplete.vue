<template>
    <div class="controls" style="position:relative">
        <div class="input-append">
            <input v-bind:readonly="isReadOnly" type="text" v-model="value"
                   @input="onInput($event.target.value)"
                   @keyup.esc="isOpen = false"
                   @blur="onBlur"
                   >
            <span @click="cleanInput" v-show="isReadOnly" style="cursor:pointer;" class="add-on"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        </div>
            <div v-show="isOpen" class="audience-selector-container">
                <ul class="audience-selector-list">
                    <li v-for="(option, index) in filtered_options"
                        class="audience-selector-item"
                        @mousedown="select(index)">
                        <slot name="item"
                              :title="option.title"
                              :description="option.email">
                        </slot>
                    </li>
                    <li v-if="filtered_options.length == 0">
                        No results found.
                    </li>
                </ul>
            </div>
    </div>
</template>

<script>
    Array.prototype.filter = window.filter;

    module.exports = {
        name: 'auto-complete',
        props: {
            options: {
                type: Array,
                required: true
            }
        },
        data () {
            return {
                isOpen: false,
                value: '',
                isReadOnly: false,
                selected_id: 0
            }
        },
        computed: {
            filtered_options () {
                const re = new RegExp(this.value, 'i');
                return this.options.filter(o => o.fullname.trim().match(re));
            }
        },
        methods: {
            onInput (value) {
                this.isOpen = !!value;
            },
            onBlur () {
                this.isOpen = false;
                if (!this.isReadOnly) {
                    this.value = "";
                }
            },
            select (index) {
                this.selected_id = this.filtered_options[index].id;
                this.$emit('select', this.selected_id);
                this.isOpen = false;
                this.value = this.filtered_options[index].title;
                this.isReadOnly = true;
            },
            cleanInput () {
                this.isReadOnly = false;
                this.value = "";
            },
            getId() {
                return this.selected_id ;
            },
        },
    };
</script>

<style>
    .audience-selector-container {
        position:absolute;
        z-index:2051!important;
        background:#fff;
        width:218px;
        height:200px;
        border:1px solid #ccc;
        border-top-width: 0px;
        border-radius: 0px 0px 5px 5px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
        overflow-x:hidden;
        overflow-y:scroll;
    }
    .audience-selector-list {
        list-style:none;
        margin:0px;
        padding:0px;
    }
    .audience-selector-list .audience-selector-item {
        padding:4px 6px;
    }
    .audience-selector-list .audience-selector-item:hover {
        cursor:pointer;
        background:#eee;
    }
    li.error-adding-member {
        color: #CC0000;
        font-weight: 800;
    }
</style>