'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var Resolver=use('EntradaJS/File/Path/Resolver');module.exports=function(){function Loader(scriptPath){_classCallCheck(this,Loader);this.basePath=scriptPath||'';if(!this.basePath.endsWith('/')){this.basePath+='/'}this.resolver=new Resolver;this.handlers={};this.promises={};this.cache={}}_createClass(Loader,[{key:'registerExtension',value:function registerExtension(extension,handlerClass){this.handlers[extension]=Reflect.construct(handlerClass,[this])}},{key:'hasHandler',value:function hasHandler(extension){return!!this.handlers[extension]}},{key:'getHandler',value:function getHandler(extension){return this.handlers[extension]||null}},{key:'handle',value:function handle(filename,content){var extension='*';if(filename.match(/\.[a-z]+$/)){extension=filename.split('.').pop()}var handler=this.getHandler(extension);if(handler){return handler.handle(filename,content)}else{console.warn('A file handler is unavailable for: ',filename);return content}}},{key:'load',value:function load(filename){var _this=this;if(this.cache[filename]){return Promise.resolve(this.cache[filename])}if(this.promises[filename]){return this.promises[filename]}var url=this.basePath;if(!filename.startsWith('/')){url+='/'}url+=filename;if(!filename.match(/\.[a-z]+$/)){url+='.js'}var promise=fetch(url,{cache:'default',mode:'same-origin',credentials:'same-origin'}).then(function(response){if(response.ok){return response.text()}},function(error){console.error('Error loading file: '+filename,error);throw error}).then(function(rawFile){return _this.handle(filename,rawFile)}).then(function(handledFile){return _this.cache[filename]=handledFile});this.promises[filename]=promise;return promise}},{key:'resolveFilename',value:function resolveFilename(filename,contextFilename){return this.resolver.resolve(filename,contextFilename)}},{key:'findDependencies',value:function findDependencies(filename,file){var _this2=this;var dependencies=[];var contextFilename=filename;try{var _use=function _use(filename){filename=_this2.resolveFilename(filename,contextFilename);if(!dependencies.includes(filename)){dependencies.push(filename)}};var _module={__dirname:this.resolver.pathname(contextFilename),__filename:contextFilename,exports:null};var process={env:{}};eval(file)}catch(err){}return dependencies}},{key:'bulkLoad',value:function bulkLoad(filenames){var promises=[];var _iteratorNormalCompletion=true;var _didIteratorError=false;var _iteratorError=undefined;try{for(var _iterator=filenames[Symbol.iterator](),_step;!(_iteratorNormalCompletion=(_step=_iterator.next()).done);_iteratorNormalCompletion=true){var filename=_step.value;promises.push(this.load(filename))}}catch(err){_didIteratorError=true;_iteratorError=err}finally{try{if(!_iteratorNormalCompletion&&_iterator.return){_iterator.return()}}finally{if(_didIteratorError){throw _iteratorError}}}return Promise.all(promises)}}]);return Loader}();