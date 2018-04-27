<template>
    <div class="row">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"Row"};
</script>

<style>
.ejs .row{margin-bottom:30px;margin-left:-15px;margin-right:-15px}.ejs .row::after{clear:both;content:"";display:block}@media (max-width: 1279px){.ejs .row>*{margin-bottom:30px}.ejs .row>*:nth-last-child(2){margin-bottom:0}}@media (max-width: 600px){.ejs .row>*:nth-last-child(2){margin-bottom:30px}}
</style>
