<template>
    <div class="select-input">
        <label v-if="label" v-text="label"></label>

        <btn :append-icon="selectInputIcon" @click.native="togglingOptions"><slot name="value"></slot></btn>

        <div v-show="selectInputOptions" :class="{ 'options': true, 'fixed': fixed }">
            <div v-if="hasSearchSlot" class="search">
                <slot name="search"></slot>
            </div>

            <div class="options-inner">
                <span class="empty" v-text="emptyMessage"></span>

                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script>
'use strict';var Btn=use('./Button.vue');module.exports={name:'SelectInput',components:{Btn:Btn},props:{label:{type:String},fixed:{type:Boolean,default:false},search:{type:Boolean,default:false},searchId:{type:String},emptyMessage:{type:String,default:'No options'}},data:function data(){return{selectInputOptions:false,selectInputIcon:'fa fa-angle-down'}},computed:{hasSearchSlot:function hasSearchSlot(){return!!this.$slots['search']}},methods:{togglingOptions:function togglingOptions(){this.selectInputOptions=!this.selectInputOptions;if(this.selectInputOptions==true){return this.selectInputIcon='fa fa-angle-up'}else{return this.selectInputIcon='fa fa-angle-down'}},closeOptions:function closeOptions(){this.selectInputOptions=false;return this.selectInputIcon='fa fa-angle-down'}}};
</script>

<style>
.ejs .select-input{margin-bottom:30px;position:relative}.ejs .select-input label{display:block;font-size:0.9em;margin-top:-8px;margin-bottom:1px;margin-left:2px}.ejs .select-input .btn,.ejs .select-input button{margin:0;width:100%}.ejs .select-input .btn .append-icon,.ejs .select-input button .append-icon{color:#6b8d98}.ejs .select-input .search{border-bottom:1px solid #ecf0f3;padding:15px}.ejs .select-input .search .input{margin:0}.ejs .select-input .options{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px;background-color:#fff;border:1px solid #d9dee2;margin-top:10px;overflow:hidden;position:relative}.ejs .select-input .options .options-inner{max-height:200px;overflow-y:scroll}.ejs .select-input .options .options-inner::-webkit-scrollbar-track{background-color:#f4f7fa}.ejs .select-input .options .options-inner::-webkit-scrollbar{background-color:#f4f7fa;height:6px;width:6px}.ejs .select-input .options .options-inner::-webkit-scrollbar-thumb{-webkit-border-radius:3px;-moz-border-radius:3px;-ms-border-radius:3px;border-radius:3px;-webkit-box-shadow:0 0 0 3px #fff;-moz-box-shadow:0 0 0 3px #fff;-ms-box-shadow:0 0 0 3px #fff;box-shadow:0 0 0 3px #fff;background-color:#d9dee2}.ejs .select-input .options .options-inner .empty{background-color:#f4f7fa;color:#8b959d;display:none;font-style:italic;margin:0;padding:10px 20px}.ejs .select-input .options .options-inner .empty:only-child{display:block}.ejs .select-input .options.fixed{-webkit-box-shadow:0 5px 25px rgba(0,0,0,0.08);-moz-box-shadow:0 5px 25px rgba(0,0,0,0.08);-ms-box-shadow:0 5px 25px rgba(0,0,0,0.08);box-shadow:0 5px 25px rgba(0,0,0,0.08);left:0;position:absolute;right:0}.ejs .select-input .options .radio [type="radio"]:checked+label{background-color:#ecf0f3}.ejs .select-input .options .radio [type="radio"]:checked:focus+label{background-color:#028ed4;color:#fff}.ejs .select-input .options .radio label{-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;border-bottom:1px solid #ecf0f3;margin:0 !important;padding:10px 20px 10px 54px;width:100%}.ejs .select-input .options .radio label::before{left:15px !important}.ejs .select-input .options .radio label::after{left:21px !important;top:16px !important}.ejs .select-input .options .radio label:hover{background-color:#ecf0f3}.ejs .select-input .options .radio:last-child label{border-bottom:none}.ejs .select-input .options .checkbox [type="checkbox"]:checked+label{background-color:#ecf0f3;margin:0}.ejs .select-input .options .checkbox [type="checkbox"]:checked:focus+label{background-color:#028ed4;color:#fff}.ejs .select-input .options .checkbox [type="checkbox"]:not(:checked)+label{margin:0}.ejs .select-input .options .checkbox label{-webkit-transition:0.2s;-moz-transition:0.2s;-ms-transition:0.2s;transition:0.2s;border-bottom:1px solid #ecf0f3;margin:0 !important;padding:10px 20px;width:100%}.ejs .select-input .options .checkbox label::before{left:15px !important}.ejs .select-input .options .checkbox label::after{left:20px !important;top:16px !important}.ejs .select-input .options .checkbox label:hover{background-color:#ecf0f3}.ejs .select-input .options .checkbox:last-child label{border-bottom:none}
</style>
