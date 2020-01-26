<template>
    <div :class="{ 'media': true, 'media-title': title }" :style="{ width: widthComputed }">
        <div v-if="title" class="title">{{ title }}</div>

        <div :class="{ 'media-wrapper': true, 'image': type == 'image', 'video': type == 'video', 'audio': type == 'audio', 'rounded': rounded, 'circle': type == 'image' &amp;&amp; circle == true }" :style="{ maxHeight: heightComputed, maxWidth: widthComputed }">
            <img v-if="type == 'image' &amp;&amp; src" :src="src" :alt="alt">
            <video v-if="type == 'video' &amp;&amp; src" :src="src" :poster="poster" :autoplay="autoplay" :loop="loop" :controls="controls" :muted="muted" :alt="alt">
            <audio v-if="type == 'audio' &amp;&amp; src" :src="src" :autoplay="autoplay" :loop="loop" :controls="controls" :muted="muted" :alt="alt">
            <span v-if="icon" :class="icon" :style="{ fontSize: iconSizeComputed, height: iconSizeComputed, width: iconSizeComputed, marginLeft: iconPosition, marginTop: iconPosition }"></span>
        </audio></video></div>

        <small v-if="hasSlot" class="media-caption"><slot></slot></small>
	</div>
</template>

<script>
'use strict';module.exports={name:'Media',props:{type:{type:String,default:'image'},src:{type:String},alt:{type:String,required:true},icon:{type:String},title:{type:String},rounded:{type:Boolean,default:false},circle:{type:Boolean,default:false},height:{type:Number,default:100},width:{type:Number,default:100},pixelHeight:{type:Boolean,default:false},pixelWidth:{type:Boolean,default:false},autoplay:{type:Boolean,default:false},loop:{type:Boolean,default:false},controls:{type:Boolean,default:true},poster:{type:String},muted:{type:Boolean,default:false}},computed:{hasSlot:function hasSlot(){return!!this.$slots.default},heightComputed:function heightComputed(){if(this.pixelHeight==true){return this.height+'px'}else{return this.height+'%'}},widthComputed:function widthComputed(){if(this.pixelWidth==true){return this.width+'px'}else{return this.width+'%'}},iconSizeComputed:function iconSizeComputed(){return this.width/2+'px'},iconPosition:function iconPosition(){return'-'+this.width/4+'px'}}};
</script>

<style>
.ejs .media{display:block;margin-bottom:30px;position:relative}.ejs .media.media-title{padding-top:30px}.ejs .media.media-title .title{color:#17323c;font-family:"Lucida Grande","Lucida Sans","Lucida Sans Unicode","Geneva",Verdana,sans-serif;font-weight:bold;margin-bottom:10px;position:absolute;top:-5px}.ejs .media .media-caption{display:block;line-height:1.2em;margin-top:10px}.ejs .media .media-wrapper{-webkit-box-shadow:0 0 0 1px #d9dee2;-moz-box-shadow:0 0 0 1px #d9dee2;-ms-box-shadow:0 0 0 1px #d9dee2;box-shadow:0 0 0 1px #d9dee2;background-color:#ecf0f3;overflow:hidden;position:relative;width:100% !important}.ejs .media .media-wrapper .fa{-webkit-box-align:center;-moz-box-align:center;-ms-flex-align:center;-webkit-align-items:center;align-items:center;display:-webkit-box;display:-moz-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-webkit-justify-content:center;justify-content:center;color:#6b8d98;left:50%;position:absolute;top:50%;z-index:0}.ejs .media .media-wrapper.image.rounded{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px}.ejs .media .media-wrapper.image.circle{-webkit-border-radius:50%;-moz-border-radius:50%;-ms-border-radius:50%;border-radius:50%;height:auto !important}.ejs .media .media-wrapper.image.circle::before{content:"";display:block;padding-top:100%}.ejs .media .media-wrapper.image.circle img{position:absolute;top:0}.ejs .media .media-wrapper.image img{display:block;height:100%;font-size:0;margin:0;width:100%;object-fit:cover;position:relative;z-index:1}.ejs .media .media-wrapper.video::before{content:"";display:block;padding-top:56.25%}.ejs .media .media-wrapper.video.rounded{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px}.ejs .media .media-wrapper.video video{display:block;position:absolute;top:0;width:100%;z-index:1}.ejs .media .media-wrapper.audio.rounded{-webkit-border-radius:10px;-moz-border-radius:10px;-ms-border-radius:10px;border-radius:10px}.ejs .media .media-wrapper.audio audio{background-color:#000;display:block;position:relative;width:100%;z-index:1}
</style>
