<template>
    <div class="one-fifth">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"OneFifth"};
</script>

<style>
.ejs .one-fifth{float:left;padding:0 15px;width:20%}@media (max-width: 1279px){.ejs .one-fifth{width:50%}.ejs .one-fifth:last-child:nth-child(odd){width:100%}}@media (max-width: 600px){.ejs .one-fifth{width:100%}}
</style>
