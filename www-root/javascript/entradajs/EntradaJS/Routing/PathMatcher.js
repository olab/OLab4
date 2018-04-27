'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var RouteCollection=use('./RouteCollection');module.exports=function(){function PathMatcher(routes){_classCallCheck(this,PathMatcher);if(routes instanceof RouteCollection===false){throw new TypeError('PathMatcher#constructor expects its argument to be a RouteCollection object.')}this.routes=routes}_createClass(PathMatcher,[{key:'normalizePath',value:function normalizePath(path){if(path.startsWith('#')){path=path.substr(1)}if(path.startsWith('/')){path=path.substr(1)}if(path.endsWith('/')){path=path.substr(0,path.length-1)}return path}},{key:'expandPattern',value:function expandPattern(route){var pattern=route.getPattern();var collection=route.getCollection();if(collection){do{pattern=collection.getPathPrefix()+pattern}while(collection=collection.getParentCollection())}return this.normalizePath(pattern)}},{key:'isParameter',value:function isParameter(string){return /[{}]/.test(string)}},{key:'parseParameterName',value:function parseParameterName(string){return string.replace(/[{}]/g,'')}},{key:'compilePattern',value:function compilePattern(route){var pattern=this.expandPattern(route);var parts=pattern.split('/');var requirements=route.getRequirements();var compiledParts=[];var _iteratorNormalCompletion=true;var _didIteratorError=false;var _iteratorError=undefined;try{for(var _iterator=parts[Symbol.iterator](),_step;!(_iteratorNormalCompletion=(_step=_iterator.next()).done);_iteratorNormalCompletion=true){var part=_step.value;if(this.isParameter(part)){var name=this.parseParameterName(part);if(requirements[name]){compiledParts.push(requirements[name].source)}}else{compiledParts.push(part)}}}catch(err){_didIteratorError=true;_iteratorError=err}finally{try{if(!_iteratorNormalCompletion&&_iterator.return){_iterator.return()}}finally{if(_didIteratorError){throw _iteratorError}}}return this.normalizePath(compiledParts.join('/'))}},{key:'findNearestCollection',value:function findNearestCollection(path){var parts=path.split('/');var collection=this.routes;var _iteratorNormalCompletion2=true;var _didIteratorError2=false;var _iteratorError2=undefined;try{for(var _iterator2=parts[Symbol.iterator](),_step2;!(_iteratorNormalCompletion2=(_step2=_iterator2.next()).done);_iteratorNormalCompletion2=true){var part=_step2.value;if(collection.findCollectionByPrefix('/'+part)){collection=collection.findCollectionByPrefix('/'+part)}}}catch(err){_didIteratorError2=true;_iteratorError2=err}finally{try{if(!_iteratorNormalCompletion2&&_iterator2.return){_iterator2.return()}}finally{if(_didIteratorError2){throw _iteratorError2}}}return collection}},{key:'matchRoute',value:function matchRoute(route,path){var pattern=this.compilePattern(route);var patternParts=pattern.split('/');var pathParts=path.split('/');var matchCount=0;if(patternParts.length!==pathParts.length){return false}patternParts.reverse();pathParts.reverse();for(var i=0;i<patternParts.length;i++){var regex=new RegExp(patternParts[i]);if(regex.test(pathParts[i]||'')){matchCount+=1}else{break}}return matchCount===patternParts.length}},{key:'match',value:function match(path){path=this.normalizePath(path);var collection=this.findNearestCollection(path);var routes=collection.getRoutes();var _iteratorNormalCompletion3=true;var _didIteratorError3=false;var _iteratorError3=undefined;try{for(var _iterator3=routes[Symbol.iterator](),_step3;!(_iteratorNormalCompletion3=(_step3=_iterator3.next()).done);_iteratorNormalCompletion3=true){var route=_step3.value;if(this.matchRoute(route,path)){return route}}}catch(err){_didIteratorError3=true;_iteratorError3=err}finally{try{if(!_iteratorNormalCompletion3&&_iterator3.return){_iterator3.return()}}finally{if(_didIteratorError3){throw _iteratorError3}}}return null}}]);return PathMatcher}();