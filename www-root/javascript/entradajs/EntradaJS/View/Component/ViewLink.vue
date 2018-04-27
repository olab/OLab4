<template>
    <a :href="href" :class="{ 'view-link': true, 'view-link-error': !href, 'view-link-active': isActive }">
        <slot></slot>
    </a>
</template>

<script>
'use strict';module.exports={name:'view-link',props:{to:{type:String,default:null},params:{type:Object,default:function _default(){}},exact:{type:Boolean,default:false}},created:function created(){jQuery(window).on('hashchange',this.refresh)},computed:{isActive:function isActive(){var currentPath=window.location.hash.replace('#','');if(this.exact){return currentPath===this.href}else{return currentPath.startsWith(this.href)}},isError:function isError(){return!this.href},href:function href(){if(this.to){try{return'#'+this.$generatePath(this.to,this.params)}catch(ex){console.error('ViewLink is unable to generate a path to route \''+this.to+'\': '+ex)}}else{return this.$attrs.href}}}};
</script>

<style>
.ejs .view-link-error{text-decoration:line-through}
</style>
