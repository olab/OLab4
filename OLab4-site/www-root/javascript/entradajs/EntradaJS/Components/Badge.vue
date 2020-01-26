<template>
    <span :class="{ 'badge': true, 'badge-lg': large, 'badge-default': type == 'default', 'badge-primary': type == 'primary', 'badge-info': type == 'info', 'badge-success': type == 'success', 'badge-danger': type == 'danger' }">
        <slot></slot>
    </span>
</template>

<script>
"use strict";module.exports={name:"Badge",props:{type:{type:String,default:"default"},large:{type:Boolean,default:false}}};
</script>

<style>
.ejs .badge{-webkit-border-radius:5px;-moz-border-radius:5px;-ms-border-radius:5px;border-radius:5px;color:#fff;display:inline-block;font-size:.9em;font-weight:600;line-height:1em;padding:7px 10px;margin:0 5px 5px 0}.ejs .badge.badge-lg{-webkit-border-radius:50px;-moz-border-radius:50px;-ms-border-radius:50px;border-radius:50px;font-size:1em;line-height:1em;padding:10px 15px;margin:0 10px 10px 0}.ejs .badge.badge-default{background-color:#8b959d}.ejs .badge.badge-primary{background-color:#069}.ejs .badge.badge-info{background-color:#028ed4}.ejs .badge.badge-success{background-color:#57b856}.ejs .badge.badge-danger{background-color:#f32753}
</style>
