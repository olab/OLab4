<template>
    <div class="two-fifths">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"TwoFifths"};
</script>

<style>
.ejs .two-fifths{float:left;padding:0 15px;width:40%}@media (max-width: 1279px){.ejs .two-fifths{width:50%}.ejs .two-fifths:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .two-fifths{width:100%}}
</style>
