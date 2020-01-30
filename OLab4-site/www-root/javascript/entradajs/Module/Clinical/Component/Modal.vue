<template>
    <div class="modal">
        <div class="modal fade in" v-if="visible">
            <div class="modal-header">
                <h4>{{ title }}</h4>
            </div>
            <div class="modal-body">
                <slot></slot>
            </div>
            <div class="modal-footer">
                <slot name="footer">
                    <div class="pull-left">
                        <a class="btn btn-default" @click="cancel()">{{ closebutton }}</a>
                    </div>
                    <button v-if="deletebutton" :class="'btn ' + deleteclass" @click="remove()">{{ deletebutton }}</button>
                    <button v-if="savebutton" :class="'btn ' + saveclass" @click="ok()">{{ savebutton }}</button>
                </slot>
            </div>
        </div>
        <div v-if="visible" class="modal-backdrop fade in"></div>
    </div>
</template>

<script>
    module.exports = {
        name: 'modal',
        props: {
            title: {
                type: String,
                required: true
            },
            savebutton: {
                type: String,
                default: "Save"
            },
            closebutton: {
                type: String,
                default: "Close"
            },
            deletebutton: {
                type: String,
                default: ""
            },
            saveclass: {
                type: String,
                default: "btn-primary"
            },
            deleteclass: {
                type: String,
                default: "btn-danger"
            },
        },
        data() {
            return {
                visible: true
            };
        },
        methods: {
            ok() {
                this.$emit('ok');
            },
            remove() {
                this.$emit('delete');
            },
            cancel() {
                this.$emit('cancel');
                this.hide();
            },
            show() {
                this.visible = true;
                this.$emit('show');
            },
            hide() {
                this.visible = false;
                this.$emit('hide');
            }
        }
    };
</script>
<style>
    .modal {
        width: 800px;
        margin-left: -420px;
    }
</style>