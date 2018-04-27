<template>
    <div class="ejs-container">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"Container"};
</script>

<style>
.ejs .ejs-container{margin:0 auto;padding:0 30px;width:100%}@media (min-width: 1280px){.ejs .ejs-container{max-width:1279px}}@media (max-width: 1279px){.ejs .ejs-container{max-width:920px}}@media (max-width: 920px){.ejs .ejs-container{max-width:767px}}@media (max-width: 600px){.ejs .ejs-container{padding:0 15px}}
</style>
