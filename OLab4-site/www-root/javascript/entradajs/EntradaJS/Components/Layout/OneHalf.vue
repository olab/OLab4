<template>
    <div class="one-half">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"OneHalf"};
</script>

<style>
.ejs .one-half{float:left;padding:0 15px;width:50%}@media (max-width: 1279px){.ejs .one-half:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .one-half{width:100%}}
</style>
