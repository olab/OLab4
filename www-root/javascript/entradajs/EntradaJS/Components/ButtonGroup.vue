<template>
    <div class="btn-group">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"ButtonGroup"};
</script>

<style>
.ejs .btn-group{margin-bottom:30px}.ejs .btn-group::after{clear:both;content:"";display:block}.ejs .btn-group .btn{float:left;margin:0}.ejs .btn-group .btn a,.ejs .btn-group .btn button{-webkit-border-radius:0;-moz-border-radius:0;-ms-border-radius:0;border-radius:0;position:relative;z-index:0}.ejs .btn-group .btn a:focus,.ejs .btn-group .btn button:focus{z-index:1}.ejs .btn-group .btn a.btn-default,.ejs .btn-group .btn button.btn-default{-webkit-box-shadow:0 0 0 1px #d9dee2;-moz-box-shadow:0 0 0 1px #d9dee2;-ms-box-shadow:0 0 0 1px #d9dee2;box-shadow:0 0 0 1px #d9dee2}.ejs .btn-group .btn a.btn-default:focus,.ejs .btn-group .btn button.btn-default:focus{-webkit-box-shadow:0 0 0 2px #028ed4;-moz-box-shadow:0 0 0 2px #028ed4;-ms-box-shadow:0 0 0 2px #028ed4;box-shadow:0 0 0 2px #028ed4}.ejs .btn-group .btn:first-child a,.ejs .btn-group .btn:first-child button{-webkit-border-radius:5px 0 0 5px;-moz-border-radius:5px 0 0 5px;-ms-border-radius:5px 0 0 5px;border-radius:5px 0 0 5px}.ejs .btn-group .btn:last-child a,.ejs .btn-group .btn:last-child button{-webkit-border-radius:0 5px 5px 0;-moz-border-radius:0 5px 5px 0;-ms-border-radius:0 5px 5px 0;border-radius:0 5px 5px 0}@media (max-width: 600px){.ejs .btn-group .btn{float:none}.ejs .btn-group .btn:first-child a,.ejs .btn-group .btn:first-child button{-webkit-border-radius:5px 5px 0 0;-moz-border-radius:5px 5px 0 0;-ms-border-radius:5px 5px 0 0;border-radius:5px 5px 0 0}.ejs .btn-group .btn:last-child a,.ejs .btn-group .btn:last-child button{-webkit-border-radius:0 0 5px 5px;-moz-border-radius:0 0 5px 5px;-ms-border-radius:0 0 5px 5px;border-radius:0 0 5px 5px}}
</style>
