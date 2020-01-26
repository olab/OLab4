<template>
    <div class="two-thirds">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"TwoThirds"};
</script>

<style>
.ejs .two-thirds{float:left;padding:0 15px;width:66.66%}@media (max-width: 1279px){.ejs .two-thirds{width:50%}}@media (max-width: 600px){.ejs .two-thirds{width:100%}}
</style>
