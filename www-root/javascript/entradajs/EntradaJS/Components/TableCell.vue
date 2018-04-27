<template>
    <div :class="{'table-cell': true, 'cell-lg': large}">
        <slot></slot>
    </div>
</template>

<script>
"use strict";module.exports={name:"TableCell",props:{large:{type:Boolean,default:false}}};
</script>

<style>
.ejs .table .table-row .table-cell,.ejs .table .header-row .table-cell{border-bottom:1px solid #ecf0f3;border-right:1px solid #ecf0f3;color:#37555e;display:table-cell;height:50px;padding:0 20px;vertical-align:middle;white-space:nowrap}.ejs .table .table-row .table-cell.cell-lg,.ejs .table .header-row .table-cell.cell-lg{width:100%}.ejs .table .table-row .table-cell:last-child,.ejs .table .header-row .table-cell:last-child{border-right:none}.ejs .table .table-row .table-cell .btn,.ejs .table .header-row .table-cell .btn{margin-bottom:0;margin-right:5px}.ejs .table .table-row .table-cell .btn:last-child,.ejs .table .header-row .table-cell .btn:last-child{margin-right:0}.ejs .table .table-row:last-child .table-cell{border-bottom:none}.ejs .table .header-row .table-cell{border-bottom:2px solid #ecf0f3;color:#17323c;font-family:"Lucida Grande","Lucida Sans","Lucida Sans Unicode","Geneva",Verdana,sans-serif;font-size:.9em}.ejs .table .header-rowlast-child .table-cell{border-bottom:none}@media (max-width: 600px){.ejs .table-row .table-cell .btn,.ejs .header-row .table-cell .btn{display:inline-block}}
</style>
