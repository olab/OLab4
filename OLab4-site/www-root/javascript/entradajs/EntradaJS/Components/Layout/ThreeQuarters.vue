<template>
    <div class="three-quarters">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"ThreeQuarters"};
</script>

<style>
.ejs .three-quarters{float:left;padding:0 15px;width:75%}@media (max-width: 1279px){.ejs .three-quarters{width:50%}.ejs .three-quarters:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .three-quarters{width:100%}}
</style>
