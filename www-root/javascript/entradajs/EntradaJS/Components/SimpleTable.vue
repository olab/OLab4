<template>
    <div class="data-table table-wrapper" ref="tableWrapper">
        <div class="table">
            <div class="left-end-cap" ref="leftEndCap">
                <slot name="left-end-cap"></slot>
            </div>

            <div class="table-content" :style="{width: this.wrapperWidth, marginLeft: this.leftCapWidth, marginRight: this.rightCapWidth}">
                <slot></slot>
            </div>

            <div class="right-end-cap" ref="rightEndCap">
                <slot name="right-end-cap"></slot>
            </div>
        </div>
    </div>
</template>

<script>
'use strict';module.exports={name:'SimpleTable',data:function data(){return{wrapperWidth:0,leftCapWidth:0,rightCapWidth:0}},computed:{sidebarToggled:function sidebarToggled(){return this.$store.state.sidebarCollapsed},leftEndCapWidth:function leftEndCapWidth(){return this.$refs.leftEndCap.clientWidth},rightEndCapWidth:function rightEndCapWidth(){return this.$refs.rightEndCap.clientWidth},capWidths:function capWidths(){return this.leftEndCapWidth+this.rightEndCapWidth}},watch:{toggleMainNavCollapsed:function toggleMainNavCollapsed(){this.getWrapperWidth()},sidebarToggled:function sidebarToggled(){this.getWrapperWidth()}},mounted:function mounted(){this.leftCapWidth=this.leftEndCapWidth+'px';this.rightCapWidth=this.rightEndCapWidth+'px';this.$nextTick(function(){window.addEventListener('resize',this.getWrapperWidth);this.getWrapperWidth()})},methods:{getWrapperWidth:function getWrapperWidth(){this.wrapperWidth=this.$refs.tableWrapper.clientWidth-this.capWidths+'px'}},beforeDestroy:function beforeDestroy(){window.removeEventListener('resize',this.getWrapperWidth)}};
</script>

<style>
.ejs .data-table.table-wrapper{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px;-webkit-box-shadow:0 0 0 1px #d9dee2;-moz-box-shadow:0 0 0 1px #d9dee2;-ms-box-shadow:0 0 0 1px #d9dee2;box-shadow:0 0 0 1px #d9dee2;margin-bottom:30px;overflow:hidden;width:100%}.ejs .data-table.table-wrapper .table{background-color:#fff;display:table;position:relative;width:100%}.ejs .data-table.table-wrapper .table .table-content{overflow-x:scroll}.ejs .data-table.table-wrapper .table .table-content::-webkit-scrollbar-track{background-color:#f4f7fa}.ejs .data-table.table-wrapper .table .table-content::-webkit-scrollbar{background-color:#f4f7fa;height:6px;width:6px}.ejs .data-table.table-wrapper .table .table-content::-webkit-scrollbar-thumb{-webkit-border-radius:3px;-moz-border-radius:3px;-ms-border-radius:3px;border-radius:3px;-webkit-box-shadow:0 0 0 3px #fff;-moz-box-shadow:0 0 0 3px #fff;-ms-box-shadow:0 0 0 3px #fff;box-shadow:0 0 0 3px #fff;background-color:#d9dee2}.ejs .data-table.table-wrapper .table .left-end-cap{background-color:#fff;border-right:1px solid #ecf0f3;left:0;padding-bottom:6px;position:absolute;top:0}.ejs .data-table.table-wrapper .table .left-end-cap::before{background-color:#f4f7fa;bottom:0;content:"";display:block;height:6px;left:0;position:absolute;right:0}.ejs .data-table.table-wrapper .table .right-end-cap{background-color:#fff;border-left:1px solid #ecf0f3;padding-bottom:6px;position:absolute;right:0;top:0}.ejs .data-table.table-wrapper .table .right-end-cap::before{background-color:#f4f7fa;bottom:0;content:"";display:block;height:6px;left:0;position:absolute;right:0}
</style>
