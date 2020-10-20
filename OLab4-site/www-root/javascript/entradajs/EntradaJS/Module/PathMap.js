'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}module.exports=function(){function PathMap(namespace){_classCallCheck(this,PathMap);this.namespace=namespace}_createClass(PathMap,[{key:'assetPath',value:function assetPath(){return this.namespace+'/Assets'}},{key:'componentPath',value:function componentPath(){return this.namespace+'/Component'}},{key:'controllerPath',value:function controllerPath(){return this.namespace+'/Controller'}},{key:'imagePath',value:function imagePath(){return this.assetPath()+'/images'}},{key:'languagePath',value:function languagePath(){return this.assetPath()+'/languages'}},{key:'modelPath',value:function modelPath(){return this.namespace+'/Models'}},{key:'stylesheetPath',value:function stylesheetPath(){return this.assetPath()+'/css'}},{key:'testPath',value:function testPath(){return this.namespace+'/Tests'}},{key:'videoPath',value:function videoPath(){return this.assetPath()+'/videos'}}]);return PathMap}();