<template>
    <div class="preformatted">
        <div v-if="title" class="preformatted-title" v-text="title"></div>

        <div class="pre-wrapper">
            <pre><slot></slot></pre>
        </div>
    </div>
</template>

<script>
"use strict";module.exports={name:"Preformatted",props:{title:{type:String}}};
</script>

<style>
.ejs .preformatted{margin-bottom:30px}.ejs .preformatted .pre-wrapper{-webkit-border-radius:5px;-moz-border-radius:5px;-ms-border-radius:5px;border-radius:5px;background-color:#e6ebee;margin:0 0 30px 0;overflow:hidden}.ejs .preformatted .pre-wrapper pre{display:block;word-break:break-all;word-wrap:break-word;white-space:pre-wrap;color:#17323c;padding:15px 20px;font-family:"Source Code Pro",monospace;font-size:.9em;line-height:1.8em;margin:0;overflow-x:scroll}.ejs .preformatted .pre-wrapper pre::-webkit-scrollbar-track{background-color:#e1e5e8}.ejs .preformatted .pre-wrapper pre::-webkit-scrollbar{background-color:#e1e5e8;height:6px;width:6px}.ejs .preformatted .pre-wrapper pre::-webkit-scrollbar-thumb{-webkit-border-radius:3px;-moz-border-radius:3px;-ms-border-radius:3px;border-radius:3px;-webkit-box-shadow:0 0 0 3px #e6ebee;-moz-box-shadow:0 0 0 3px #e6ebee;-ms-box-shadow:0 0 0 3px #e6ebee;box-shadow:0 0 0 3px #e6ebee;background-color:#ccd3d8}.ejs .preformatted .pre-wrapper pre strong{font-family:"Source Code Pro",monospace;font-size:1em}.ejs .preformatted .preformatted-title{color:#17323c;font-family:"Lucida Grande","Lucida Sans","Lucida Sans Unicode","Geneva",Verdana,sans-serif;font-weight:bold;margin-bottom:10px}
</style>
