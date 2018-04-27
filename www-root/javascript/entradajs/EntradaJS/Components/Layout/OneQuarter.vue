<template>
    <div class="one-quarter">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"OneQuarter"};
</script>

<style>
.ejs .one-quarter{float:left;padding:0 15px;width:25%}@media (max-width: 1279px){.ejs .one-quarter{width:50%}.ejs .one-quarter:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .one-quarter{width:100%}}
</style>
