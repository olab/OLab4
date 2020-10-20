'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}module.exports=function(){function Resolver(){_classCallCheck(this,Resolver)}_createClass(Resolver,[{key:'basename',value:function basename(filename){var filenameParts=filename.split('/');return filenameParts.pop()}},{key:'pathname',value:function pathname(filename){var filenameParts=filename.replace(/^\.\//,'').split('/');filenameParts.pop();return filenameParts.join('/')}},{key:'makeAbsolute',value:function makeAbsolute(path){var parts=path.split('/');for(var i=0;i<parts.length;i++){if(parts[i]==='..'){parts.splice(i-1,2)}}return parts.join('/')}},{key:'resolve',value:function resolve(filename,context_filename){var pathname=this.pathname(context_filename);if(filename.startsWith('./')){filename=pathname+'/'+filename.replace(/^\.\//,'')}return this.makeAbsolute(filename)}}]);return Resolver}();