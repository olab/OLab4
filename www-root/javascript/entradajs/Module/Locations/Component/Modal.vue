<template>
    <div class="modal">
        <div class="modal fade in" v-if="visible">
            <div class="modal-header">
                <h4>{{ title }}</h4>
            </div>
            <div class="modal-body">
                <slot></slot>
            </div>
            <div class="modal-footer" id="leave-modal-footer">
                <div class="pull-left">
                    <a class="btn btn-default" @click="cancel()">{{ closebutton }}</a>
                </div>
                <button :class="'btn ' + saveclass" @click="ok()">{{ savebutton }}</button>
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
            saveclass: {
                type: String,
                default: "btn-primary"
            }

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
</style>