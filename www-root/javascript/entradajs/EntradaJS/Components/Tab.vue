<template>
    <div v-show="isActive" role="tab">
        <slot></slot>
    </div>
</template>

<script>
'use strict';module.exports={name:'Tab',data:function data(){return{isActive:false}},props:{name:{type:String,required:true},badge:{type:String},badgeType:{type:String,default:'default'},icon:{type:String,default:''},selected:{type:Boolean,default:false}},computed:{href:function href(){return'#'+this.name.toLowerCase().replace(/ /g,'-')}},mounted:function mounted(){this.isActive=this.selected}};
</script>
