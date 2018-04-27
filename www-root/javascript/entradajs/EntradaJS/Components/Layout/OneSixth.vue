<template>
    <div class="one-sixth">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"OneSixth"};
</script>

<style>
.ejs .one-sixth{float:left;padding:0 15px;width:16.66%}@media (max-width: 1279px){.ejs .one-sixth{width:50%}.ejs .one-sixth:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .one-sixth{width:100%}}
</style>
