<template>
    <div class="one-third">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"OneThird"};
</script>

<style>
.ejs .one-third{float:left;padding:0 15px;width:33.33%}@media (max-width: 1279px){.ejs .one-third{width:50%}.ejs .one-third:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .one-third{width:100%}}
</style>
