<template>
    <div class="filter-item" :id="id">
        <checkbox :id="checkboxId" :name="checkboxName" :label="checkboxLabel" @checkbox-update="toggleSwitchToggle"></checkbox>

        <div :class="{ 'label': true, 'disabled': switchToggleDisabledState }">
            {{ label, switchToggleDisabledState, trueState, falseState, switchToggleState | labelState }}
        </div>

        <switch-toggle @toggle-event="switchToggled" :id="switchToggleId" :name="switchToggleName" :default-state="switchToggleDefault" :disabled="switchToggleDisabledState" :true-state="trueState" :false-state="falseState"></switch-toggle>
    </div>
</template>

<script>
'use strict';var Checkbox=use('./Checkbox.vue');var SwitchToggle=use('./SwitchToggle.vue');module.exports={name:'FilterItem',components:{Checkbox:Checkbox,SwitchToggle:SwitchToggle},props:{id:{type:String},checkboxId:{type:String},checkboxName:{type:String},checkboxLabel:{type:String},switchToggleId:{type:String},switchToggleName:{type:String},switchToggleDefault:{type:Boolean},trueState:{type:String},falseState:{type:String}},data:function data(){return{label:'',switchToggleDisabledState:true,switchToggleState:false}},filters:{labelState:function labelState(value,toggleDisabledState,trueLabel,falseLabel,toggleState){if(toggleDisabledState==false){if(toggleState){return trueLabel}else{return falseLabel}}else{if(toggleState){return trueLabel+' (Disabled)'}else{return falseLabel+' (Disabled)'}};}},created:function created(){this.switchToggleState=this.switchToggleDefault},watch:{switchToggleDisabledState:function switchToggleDisabledState(){this.$emit('filter-states',this.id,this.switchToggleDisabledState,this.switchToggleState)},switchToggleState:function switchToggleState(){this.$emit('filter-states',this.id,this.switchToggleDisabledState,this.switchToggleState)}},methods:{toggleSwitchToggle:function toggleSwitchToggle(payload){this.switchToggleDisabledState=!payload.isChecked},switchToggled:function switchToggled(currentState){this.switchToggleState=currentState}}};
</script>

<style>
.ejs .filter-item{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-webkit-justify-content:center;justify-content:center;border-bottom:1px solid #ecf0f3;padding:10px 20px}.ejs .filter-item:last-child{border-bottom:none}.ejs .filter-item .label{line-height:1.3em;margin:0 8px}.ejs .filter-item .label.disabled{color:#8b959d}.ejs .filter-item>.checkbox [type="checkbox"]:checked+label,.ejs .filter-item>.checkbox [type="checkbox"]:not(:checked)+label{border-bottom:none;top:2px;padding:0}.ejs .filter-item>.checkbox [type="checkbox"]:checked+label::before,.ejs .filter-item>.checkbox [type="checkbox"]:not(:checked)+label::before{left:0 !important}.ejs .filter-item>.checkbox [type="checkbox"]:checked+label::after,.ejs .filter-item>.checkbox [type="checkbox"]:not(:checked)+label::after{left:5px !important;top:6px !important}.ejs .filter-item>.checkbox [type="checkbox"]:checked+label span,.ejs .filter-item>.checkbox [type="checkbox"]:not(:checked)+label span{display:none}.ejs .filter-item .switch-toggle{margin-bottom:0 !important;margin-left:auto}.ejs .filter-item .switch-toggle label{margin-top:0}.ejs .filter-item .switch-toggle .true-label,.ejs .filter-item .switch-toggle .false-label{display:none}
</style>
