'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var Headers=use('./Headers');module.exports=function(){function Response(status,buffer,headers,request){_classCallCheck(this,Response);this.status=Number(status);this.buffer=buffer;this.headers=new Headers(headers);this.request=request;this.ok=this.status>=200&&this.status<400;Object.freeze(this)}_createClass(Response,[{key:'arrayBuffer',value:function arrayBuffer(){return this.buffer}},{key:'blob',value:function blob(){return new Blob([new Uint8Array(this.buffer)],{type:this.headers.get('content-type')})}},{key:'json',value:function json(){return JSON.parse(this.text())}},{key:'text',value:function text(){var array=new Uint8Array(this.buffer);var chunkSize=10000;var string='';if('TextDecoder'in window){var decoder=new TextDecoder('utf-8');string=decoder.decode(array)}else{for(var i=0;i<array.length;i+=chunkSize){var slice=array.slice(i,i+chunkSize);string+=String.fromCodePoint.apply(null,slice)}}return string}},{key:'bodyText',get:function get(){return this.text()}}]);return Response}();