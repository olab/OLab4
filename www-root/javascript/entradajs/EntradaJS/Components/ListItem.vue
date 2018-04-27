<template>
    <li class="list-item">
        <div v-if="hasHeaderSlot" class="list-header">
            <checkbox v-if="checkbox" :id="checkboxId" :name="checkboxName" :label="checkboxLabel" :dissabled="checkboxDisabled" :hide-label="true" @check-event="toggleCheckbox">Label</checkbox>

            <span v-if="prependIcon" :class="computedPrependIcon"></span>

            <slot></slot>

            <span v-if="appendIcon" :class="computedAppendIcon"></span>

            <div v-if="contentToggle" class="content-toggle">
                <button @click="togglingContent" :class="{ 'open': content }"><tooltip position="left">{{ tooltip }}</tooltip><span class="fa fa-chevron-down"></span></button>
            </div>
        </div>

        <div v-if="hasContentSlot" v-show="content" class="list-content">
            <slot name="content"></slot>
        </div>
    </li>
</template>

<script>
'use strict';var Checkbox=use('./Checkbox.vue');var Tooltip=use('./Tooltip.vue');module.exports={name:'ListItem',components:{Checkbox:Checkbox,Tooltip:Tooltip},data:function data(){return{content:true,checkboxState:false}},props:{checkbox:{type:Boolean,default:false},checkboxId:{type:String},checkboxLabel:{type:String},checkboxName:{type:String},checkboxDisabled:{type:Boolean,default:false},contentToggle:{type:Boolean,default:false},prependIcon:{type:String},appendIcon:{type:String}},computed:{hasHeaderSlot:function hasHeaderSlot(){return!!this.$slots.default},hasContentSlot:function hasContentSlot(){return!!this.$slots['content']},computedPrependIcon:function computedPrependIcon(){return this.prependIcon+' prepend-input-icon'},computedAppendIcon:function computedAppendIcon(){return this.appendIcon+' append-input-icon'},tooltip:function tooltip(){if(this.content){return'Collapse'}else{return'Expand'}}},mounted:function mounted(){if(this.contentToggle){this.content=false}},methods:{togglingContent:function togglingContent(){this.content=!this.content},toggleCheckbox:function toggleCheckbox(payload){this.checkboxState=payload.isChecked;this.$emit('list-item-check-event',payload)}}};
</script>

<style>
.ejs .list-item{background-color:#fff;border-bottom:1px solid #ecf0f3;list-style:none;margin:0;padding:0}.ejs .list-item:first-child .list-header>a{-webkit-border-radius:10px 10px 0 0;-moz-border-radius:10px 10px 0 0;-ms-border-radius:10px 10px 0 0;border-radius:10px 10px 0 0}.ejs .list-item:last-child{border-bottom:none}.ejs .list-item:last-child .list-header>a{-webkit-border-radius:0 0 10px 10px;-moz-border-radius:0 0 10px 10px;-ms-border-radius:0 0 10px 10px;border-radius:0 0 10px 10px}.ejs .list-item .list-header{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;background-color:#fff}.ejs .list-item .list-header .media{margin:0}.ejs .list-item .list-header .badge{margin-bottom:0;margin-top:5px;padding:7px 10px}.ejs .list-item .list-header>a{display:block;position:relative;width:100%}.ejs .list-item .list-header>a:only-child{padding-right:50px}.ejs .list-item .list-header>a:only-child::after{color:#6b8d98;content:"\f105";font:normal normal normal 1em/1em FontAwesome;font-size:1.2em;position:absolute;right:20px}.ejs .list-item .list-header>a:hover,.ejs .list-item .list-header>a:focus{background-color:#f4f7fa;text-decoration:none}.ejs .list-item .list-header>a:focus{-webkit-box-shadow:inset 0 0 0 2px #028ed4;-moz-box-shadow:inset 0 0 0 2px #028ed4;-ms-box-shadow:inset 0 0 0 2px #028ed4;box-shadow:inset 0 0 0 2px #028ed4}.ejs .list-item .list-header>a.router-link-active{background-color:#f4f7fa;color:#17323c;cursor:default}.ejs .list-item .list-header>*{padding:10px 0 10px 10px}.ejs .list-item .list-header>*:first-child{padding-left:20px}.ejs .list-item .list-header>*:last-child{padding-right:20px}.ejs .list-item .list-header .content-toggle{margin-left:auto}.ejs .list-item .list-header .content-toggle button{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;-webkit-appearance:none;-moz-appearance:none;-webkit-border-radius:50%;-moz-border-radius:50%;-ms-border-radius:50%;border-radius:50%;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-webkit-justify-content:center;justify-content:center;-webkit-box-shadow:0 0 0 1px #ecf0f3;-moz-box-shadow:0 0 0 1px #ecf0f3;-ms-box-shadow:0 0 0 1px #ecf0f3;box-shadow:0 0 0 1px #ecf0f3;-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;background-color:transparent;border:none;color:#6b8d98;cursor:pointer;height:32px;padding:6px 7px;position:relative;width:32px}.ejs .list-item .list-header .content-toggle button:hover{-webkit-box-shadow:0 0 0 1px #6b8d98;-moz-box-shadow:0 0 0 1px #6b8d98;-ms-box-shadow:0 0 0 1px #6b8d98;box-shadow:0 0 0 1px #6b8d98}.ejs .list-item .list-header .content-toggle button:focus{-webkit-box-shadow:0 0 0 2px #028ed4;-moz-box-shadow:0 0 0 2px #028ed4;-ms-box-shadow:0 0 0 2px #028ed4;box-shadow:0 0 0 2px #028ed4}.ejs .list-item .list-header .content-toggle button .fa{-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;position:relative;top:0}.ejs .list-item .list-header .content-toggle button.open .fa{-ms-transform:rotate(-180deg);-webkit-transform:rotate(-180deg);transform:rotate(-180deg)}.ejs .list-item .list-content{padding:0 20px 20px 20px}.ejs .list-item .list-content p:first-child{margin-top:0}.ejs .list-item .list-content p:last-child{margin-bottom:0}.ejs .list-item .list-content p:only-child{margin-bottom:0;margin-top:0}
</style>
