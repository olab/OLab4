<template>
    <div class="switch-toggle">
        <input type="checkbox" :id="id" :value="value" :name="name" v-model="currentState" :disabled="disabled" @keyup.space="toggling">

        <label :for="id" @click="toggling">
            <screen-reader>
                <slot></slot>
            </screen-reader>

            <div :class="{ 'switch': true, 'true': currentState, 'false': !currentState }"></div>
        </label>

        <div :class="{ 'false-label': true, 'disabled': disabled }" @click="togglingFalse" v-text="falseState"></div>

        <div :class="{ 'true-label': true, 'disabled': disabled }" @click="togglingTrue" v-text="trueState"></div>
    </div>
</template>

<script>
'use strict';var ScreenReader=use('EntradaJS/Components/ScreenReader.vue');module.exports={name:'SwitchToggle',components:{ScreenReader:ScreenReader},data:function data(){return{currentState:false}},props:{id:{type:String,required:true},value:{type:String},name:{type:String,required:true},disabled:{type:Boolean,default:false},trueState:{type:String,required:true},falseState:{type:String,required:true},defaultState:{type:Boolean,default:false}},computed:{currentLabel:function currentLabel(){if(this.currentState){return this.trueState}else{return this.falseState}}},mounted:function mounted(){this.currentState=this.defaultState},methods:{toggling:function toggling(){if(!this.disabled){this.currentState=!this.currentState;this.$emit('toggle-event',this.currentState)}},togglingFalse:function togglingFalse(){if(!this.disabled){this.currentState=false;this.$emit('toggle-event',this.currentState)}},togglingTrue:function togglingTrue(){if(!this.disabled){this.currentState=true;this.$emit('toggle-event',this.currentState)}}}};
</script>

<style>
.ejs .switch-toggle{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;margin:0 0 30px 0}.ejs .switch-toggle [type="checkbox"]:checked,.ejs .switch-toggle [type="checkbox"]:not(:checked){position:absolute;left:-9999px}.ejs .switch-toggle [type="checkbox"]:checked:hover+label .switch,.ejs .switch-toggle [type="checkbox"]:not(:checked):hover+label .switch{-webkit-box-shadow:0 0 0 1px #8b959d;-moz-box-shadow:0 0 0 1px #8b959d;-ms-box-shadow:0 0 0 1px #8b959d;box-shadow:0 0 0 1px #8b959d}.ejs .switch-toggle [type="checkbox"]:checked:focus+label .switch,.ejs .switch-toggle [type="checkbox"]:checked:active+label .switch,.ejs .switch-toggle [type="checkbox"]:not(:checked):focus+label .switch,.ejs .switch-toggle [type="checkbox"]:not(:checked):active+label .switch{-webkit-box-shadow:0 0 0 2px #028ed4;-moz-box-shadow:0 0 0 2px #028ed4;-ms-box-shadow:0 0 0 2px #028ed4;box-shadow:0 0 0 2px #028ed4}.ejs .switch-toggle [type="checkbox"]:disabled+label{color:#8b959d;cursor:not-allowed}.ejs .switch-toggle [type="checkbox"]:disabled+label .switch{background-color:#ecf0f3}.ejs .switch-toggle [type="checkbox"]:disabled+label .switch:hover{-webkit-box-shadow:0 0 0 1px #d9dee2;-moz-box-shadow:0 0 0 1px #d9dee2;-ms-box-shadow:0 0 0 1px #d9dee2;box-shadow:0 0 0 1px #d9dee2}.ejs .switch-toggle [type="checkbox"]:disabled+label .switch::before{background-color:#8b959d}.ejs .switch-toggle label{cursor:pointer}.ejs .switch-toggle .false-label{-webkit-box-ordinal-group:0;-webkit-order:-1;-ms-flex-order:-1;order:-1;color:#37555e;cursor:pointer;padding-right:8px}.ejs .switch-toggle .false-label.disabled{color:#8b959d;cursor:not-allowed}.ejs .switch-toggle .true-label{color:#37555e;cursor:pointer;padding-left:8px}.ejs .switch-toggle .true-label.disabled{color:#8b959d;cursor:not-allowed}.ejs .switch-toggle .switch{-webkit-border-radius:22px;-moz-border-radius:22px;-ms-border-radius:22px;border-radius:22px;-webkit-box-shadow:0 0 0 1px #d9dee2;-moz-box-shadow:0 0 0 1px #d9dee2;-ms-box-shadow:0 0 0 1px #d9dee2;box-shadow:0 0 0 1px #d9dee2;-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;background-color:#fff;height:30px;padding:3px;position:relative;width:56px}.ejs .switch-toggle .switch::before{-webkit-border-radius:50%;-moz-border-radius:50%;-ms-border-radius:50%;border-radius:50%;-webkit-transition:0.1s;-moz-transition:0.1s;-ms-transition:0.1s;transition:0.1s;content:"";display:block;height:24px;position:absolute;width:24px}.ejs .switch-toggle .switch.false::before{background-color:#8b959d;margin-right:26px}.ejs .switch-toggle .switch.true::before{background-color:#028ed4;margin-left:26px}
</style>
