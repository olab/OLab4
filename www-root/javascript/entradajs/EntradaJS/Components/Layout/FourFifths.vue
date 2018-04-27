<template>
    <div class="four-fifths">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"FourFifths"};
</script>

<style>
.ejs .four-fifths{float:left;padding:0 15px;width:80%}@media (max-width: 1279px){.ejs .four-fifths{width:50%}.ejs .four-fifths:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .four-fifths{width:100%}}
</style>
