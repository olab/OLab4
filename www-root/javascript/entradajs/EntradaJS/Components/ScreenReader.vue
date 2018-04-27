<template>
    <div class="sr-only">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"ScreenReader"};
</script>

<style>
.ejs .sr-only{position:absolute;width:1px;height:1px;padding:0;overflow:hidden;clip:rect(0, 0, 0, 0);white-space:nowrap;clip-path:inset(50%);border:0}
</style>
