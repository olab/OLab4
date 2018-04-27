<template>
    <article :class="{ 'card': true, 'no-header-border': noHeaderBorder }">
        <div v-if="!link">
            <div v-if="hasHeaderSlot" class="card-header">
                <checkbox v-if="checkbox" :id="checkboxId" :name="checkboxName" :label="checkboxLabel" :disabled="checkboxDisabled" :hide-label="true" @check-box-update="updatingCardCheckBox"></checkbox>

                <span v-if="icon" :class="icon"></span>

                <slot></slot>

                <div v-if="contentToggle" class="content-toggle">
                    <button @click="togglingContent" :class="{ 'open': content }"><tooltip position="left">{{ tooltip }}</tooltip><span class="fa fa-chevron-down"></span></button>
                </div>
            </div>

            <div v-show="content" v-if="hasContentSlot" class="card-content">
                <slot name="content"></slot>
            </div>

            <div v-if="hasFooterSlot" class="card-footer">
                <slot name="footer"></slot>
            </div>
        </div>

        <a v-if="link" :href="link">
            <div v-if="hasHeaderSlot" class="card-header">
                <checkbox v-if="checkbox" :id="checkboxId" :name="checkboxName" :label="checkboxLabel" :hide-label="true" @check-event="toggleCheckbox">Label</checkbox>

                <span v-if="icon" :class="icon"></span>

                <slot></slot>

                <div v-if="contentToggle" class="content-toggle">
                    <button @click="togglingContent" :class="{ 'open': content }"><tooltip position="left">{{ tooltip }}</tooltip><span class="fa fa-chevron-down"></span></button>
                </div>
            </div>

            <div v-if="hasContentSlot" v-show="content" class="card-content">
                <slot name="content"></slot>
            </div>

            <div v-if="hasFooterSlot" class="card-footer">
                <slot name="footer"></slot>
            </div>
        </a>
    </article>
</template>

<script>
"use strict";var Checkbox=use("./Checkbox.vue");var Tooltip=use("./Tooltip.vue");module.exports={name:"Card",components:{Checkbox:Checkbox,Tooltip:Tooltip},data:function data(){return{content:true,cardCheckBox:false}},props:{link:{type:String},noHeaderBorder:{type:Boolean,default:false},icon:{type:String},checkbox:{type:Boolean,default:false},checkboxId:{type:String},checkboxLabel:{type:String},checkboxName:{type:String},checkboxDisabled:{type:Boolean,default:false},contentToggle:{type:Boolean,default:false}},computed:{hasHeaderSlot:function hasHeaderSlot(){return!!this.$slots.default},hasContentSlot:function hasContentSlot(){return!!this.$slots["content"]},hasFooterSlot:function hasFooterSlot(){return!!this.$slots["footer"]},tooltip:function tooltip(){if(this.content){return"Collapse"}else{return"Expand"}}},watch:{contentToggle:function contentToggle(){if(!this.contentToggle){this.content=true}else{this.content=false}}},methods:{togglingContent:function togglingContent(){this.content=!this.content},updatingCardCheckBox:function updatingCardCheckBox(isChecked){this.cardCheckBox=isChecked;this.$emit("card-checkbox-update",isChecked)}},mounted:function mounted(){if(this.contentToggle){this.content=false}}};
</script>

<style>
.ejs .card{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px;background-color:#fff;border:1px solid #d9dee2;margin-bottom:30px}.ejs .card>a{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px;-webkit-transition:box-shadow 0.2s;-moz-transition:box-shadow 0.2s;-ms-transition:box-shadow 0.2s;transition:box-shadow 0.2s;display:block}.ejs .card>a:hover{-webkit-box-shadow:0 0 0 1px #8b959d;-moz-box-shadow:0 0 0 1px #8b959d;-ms-box-shadow:0 0 0 1px #8b959d;box-shadow:0 0 0 1px #8b959d;text-decoration:none}.ejs .card>a:focus{-webkit-box-shadow:0 0 0 2px #028ed4;-moz-box-shadow:0 0 0 2px #028ed4;-ms-box-shadow:0 0 0 2px #028ed4;box-shadow:0 0 0 2px #028ed4;text-decoration:none}.ejs .card .card-header{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;-webkit-border-radius:10px 10px 0 0;-moz-border-radius:10px 10px 0 0;-ms-border-radius:10px 10px 0 0;border-radius:10px 10px 0 0;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;border-bottom:1px solid #ecf0f3}.ejs .card .card-header>.fa{color:#6b8d98;position:relative;top:-1px}.ejs .card .card-header>*{padding:15px 0 15px 10px}.ejs .card .card-header>*:first-child{padding-left:20px}.ejs .card .card-header>*:last-child{padding-right:20px}.ejs .card .card-header .content-toggle{margin-left:auto}.ejs .card .card-header .content-toggle button{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;-webkit-appearance:none;-moz-appearance:none;-webkit-border-radius:50%;-moz-border-radius:50%;-ms-border-radius:50%;border-radius:50%;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-webkit-justify-content:center;justify-content:center;-webkit-box-shadow:0 0 0 1px #ecf0f3;-moz-box-shadow:0 0 0 1px #ecf0f3;-ms-box-shadow:0 0 0 1px #ecf0f3;box-shadow:0 0 0 1px #ecf0f3;-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;background-color:transparent;border:none;color:#6b8d98;cursor:pointer;height:32px;padding:6px 7px;position:relative;width:32px}.ejs .card .card-header .content-toggle button:hover{-webkit-box-shadow:0 0 0 1px #6b8d98;-moz-box-shadow:0 0 0 1px #6b8d98;-ms-box-shadow:0 0 0 1px #6b8d98;box-shadow:0 0 0 1px #6b8d98}.ejs .card .card-header .content-toggle button:focus{-webkit-box-shadow:0 0 0 2px #028ed4;-moz-box-shadow:0 0 0 2px #028ed4;-ms-box-shadow:0 0 0 2px #028ed4;box-shadow:0 0 0 2px #028ed4}.ejs .card .card-header .content-toggle button .fa{-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;position:relative;top:0}.ejs .card .card-header .content-toggle button.open .fa{-ms-transform:rotate(-180deg);-webkit-transform:rotate(-180deg);transform:rotate(-180deg)}.ejs .card .card-content{padding:20px}.ejs .card .card-content:first-child{-webkit-border-radius:10px 10px 0 0;-moz-border-radius:10px 10px 0 0;-ms-border-radius:10px 10px 0 0;border-radius:10px 10px 0 0}.ejs .card .card-content:last-child{-webkit-border-radius:0 0 10px 10px;-moz-border-radius:0 0 10px 10px;-ms-border-radius:0 0 10px 10px;border-radius:0 0 10px 10px}.ejs .card .card-content:only-child{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px}.ejs .card .card-content>*>*:first-child{margin-top:0}.ejs .card .card-content>*>*:last-child{margin-bottom:0}.ejs .card .card-content>*>*:only-child{margin-bottom:0;margin-top:0}.ejs .card .card-footer footer{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;-webkit-border-radius:0 0 10px 10px;-moz-border-radius:0 0 10px 10px;-ms-border-radius:0 0 10px 10px;border-radius:0 0 10px 10px;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-webkit-justify-content:space-between;justify-content:space-between;background-color:#f4f7fa;border-top:1px solid #ecf0f3}.ejs .card .card-footer footer>*{padding:15px 0 15px 10px}.ejs .card .card-footer footer>*:first-child{padding-left:20px}.ejs .card .card-footer footer>*:last-child{padding-right:20px}.ejs .card .card-footer footer p:first-child{margin-top:0}.ejs .card .card-footer footer p:last-child{margin-bottom:0}.ejs .card .card-footer footer p:only-child{margin:0}.ejs .card .card-footer footer .btn{margin:0}.ejs .card.no-header-border .card-header{border:none}.ejs .card.no-header-border .card-content{padding-top:0}
</style>
