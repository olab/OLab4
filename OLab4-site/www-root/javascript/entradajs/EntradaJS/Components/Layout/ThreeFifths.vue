<template>
    <div class="three-fifths">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"ThreeFifths"};
</script>

<style>
.ejs .three-fifths{float:left;padding:0 15px;width:60%}@media (max-width: 1279px){.ejs .three-fifths{width:50%}.ejs .three-fifths:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .three-fifths{width:100%}}
</style>
