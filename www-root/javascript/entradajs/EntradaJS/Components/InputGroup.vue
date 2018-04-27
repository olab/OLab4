<template>
    <div :class="{'input-group': true, 'inline': inline}">
        <h3 v-if="title" class="input-group-title" v-text="title"></h3>
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"InputGroup",props:{inline:{type:Boolean,default:false},title:{type:String}}};
</script>

<style>
.ejs .input-group{display:block;margin-bottom:20px}.ejs .input-group::after{content:"";clear:both;display:block}.ejs .input-group .input-group-title{color:#17323c;font-family:"Lucida Grande","Lucida Sans","Lucida Sans Unicode","Geneva",Verdana,sans-serif;font-size:1.2em;margin:0 0 20px 0}@media (max-width: 600px){.ejs .input-group .input-group-title{font-size:1.15em}}
</style>
