<template>
    <div>
        <div class="simple-dialog-overlay"></div>
        <transition name="fade" appear>
            <div class="simple-dialog" v-if="visible">
                <header>{{ title }}</header>
                <main>
                    <slot></slot>
                </main>
                <footer>
                    <button @click="ok()">Ok</button>
                    <button @click="cancel()">Cancel</button>
                </footer>
            </div>
        </transition>
    </div>
</template>

<script>
    module.exports = {
        name: 'simple-dialog',
        props: {
            title: {
                type: String,
                required: true
            },
            beforeOk: {
                type: Function,
                default: function() {}
            }
        },
        data() {
            return {
                visible: true
            };
        },
        methods: {
            ok() {
                this.beforeOk();
                this.$emit('ok');
                this.hide();
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
    .simple-dialog {
        position:fixed;

        background:#fff;
        border:1px solid #d9dee2;

        width:300px;

        left:calc(50% - 150px);
        top:150px;

        z-index:9100;
    }

    .simple-dialog > header {
        background:#f4f7fa;
        border-bottom:1px solid #d9dee2;

        padding:8px 12px;
    }

    .simple-dialog > main {
        padding:15px;
    }

    .simple-dialog > footer {
        border-top:1px solid #d9dee2;
        text-align:right;

        box-sizing:border-box;
        width:100%;

        padding:8px 12px;
    }

    .simple-dialog button {
        font-size:1em;
        padding:4px 8px;
    }

    .simple-dialog-overlay {
        position:fixed;
        background-color:rgba(0, 0, 0, 0.45);
        width:100%;
        height:100%;
        left:0;
        top:0;
        z-index:9000;
    }
</style>