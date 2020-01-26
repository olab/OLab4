<template>
    <div class="five-sixths">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"FiveSixths"};
</script>

<style>
.ejs .five-sixths{float:left;padding:0 15px;width:83.33%}@media (max-width: 1279px){.ejs .five-sixths{width:50%}}@media (max-width: 600px){.ejs .five-sixths{width:100%}}
</style>
