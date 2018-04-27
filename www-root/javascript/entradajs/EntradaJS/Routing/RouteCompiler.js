'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var CompiledRoute=use('./CompiledRoute');module.exports=function(){function RouteCompiler(){_classCallCheck(this,RouteCompiler)}_createClass(RouteCompiler,[{key:'normalizePath',value:function normalizePath(path){if(path.startsWith('#')){path=path.substr(1)}if(path.startsWith('/')){path=path.substr(1)}if(path.endsWith('/')){path=path.substr(0,path.length-1)}return path}},{key:'expandPattern',value:function expandPattern(route){var pattern=route.getPattern();var collection=route.getCollection();if(collection){do{pattern=collection.getPathPrefix()+pattern}while(collection=collection.getParentCollection())}return this.normalizePath(pattern)}},{key:'isParameter',value:function isParameter(string){return /[{}]/.test(string)}},{key:'parseParameterName',value:function parseParameterName(string){return string.replace(/[{}]/g,'')}},{key:'findPositionalParameterNames',value:function findPositionalParameterNames(pattern){pattern=this.normalizePath(pattern);var parts=pattern.split('/');var regex=/^{(\w+)}$/;var params=[];var _iteratorNormalCompletion=true;var _didIteratorError=false;var _iteratorError=undefined;try{for(var _iterator=parts[Symbol.iterator](),_step;!(_iteratorNormalCompletion=(_step=_iterator.next()).done);_iteratorNormalCompletion=true){var part=_step.value;var matches=regex.exec(part);if(matches){params.push(matches[1])}}}catch(err){_didIteratorError=true;_iteratorError=err}finally{try{if(!_iteratorNormalCompletion&&_iterator.return){_iterator.return()}}finally{if(_didIteratorError){throw _iteratorError}}}return params}},{key:'mapParameterRequirements',value:function mapParameterRequirements(parameterNames,requirements){var parameterRequirements={};var _iteratorNormalCompletion2=true;var _didIteratorError2=false;var _iteratorError2=undefined;try{for(var _iterator2=parameterNames[Symbol.iterator](),_step2;!(_iteratorNormalCompletion2=(_step2=_iterator2.next()).done);_iteratorNormalCompletion2=true){var parameterName=_step2.value;if(requirements[parameterName]){parameterRequirements[parameterName]=requirements[parameterName]}else{throw new Error('A positional parameter is missing a requirement: '+parameterName)}}}catch(err){_didIteratorError2=true;_iteratorError2=err}finally{try{if(!_iteratorNormalCompletion2&&_iterator2.return){_iterator2.return()}}finally{if(_didIteratorError2){throw _iteratorError2}}}return parameterRequirements}},{key:'matchParameters',value:function matchParameters(pattern,path,requirements){var defaults=arguments.length>3&&arguments[3]!==undefined?arguments[3]:{};var parameters={};var patternParts=pattern.split('/').reverse();var pathParts=path.split('/').reverse();for(var i=0;i<patternParts.length;i++){var patternPart=patternParts[i];var pathPart=pathParts[i];if(this.isParameter(patternPart)){var parameterName=this.parseParameterName(patternPart);var requirement=new RegExp(requirements[parameterName].source);if(requirement.test(pathPart)){parameters[parameterName]=pathPart}else{throw new Error('Failed to compile route, parameter does not meet requirement: '+parameterName)}}}return parameters}},{key:'replaceParameters',value:function replaceParameters(pattern,parameters){var _iteratorNormalCompletion3=true;var _didIteratorError3=false;var _iteratorError3=undefined;try{for(var _iterator3=Object.getOwnPropertyNames(parameters)[Symbol.iterator](),_step3;!(_iteratorNormalCompletion3=(_step3=_iterator3.next()).done);_iteratorNormalCompletion3=true){var parameterName=_step3.value;var parameterValue=parameters[parameterName];pattern=pattern.replace('{'+parameterName+'}',parameterValue)}}catch(err){_didIteratorError3=true;_iteratorError3=err}finally{try{if(!_iteratorNormalCompletion3&&_iterator3.return){_iterator3.return()}}finally{if(_didIteratorError3){throw _iteratorError3}}}return pattern}},{key:'compile',value:function compile(route,path){path=this.normalizePath(path);var pattern=this.expandPattern(route);var parameterNames=this.findPositionalParameterNames(pattern);var parameterRequirements=this.mapParameterRequirements(parameterNames,route.getRequirements());var parameters=this.matchParameters(pattern,path,parameterRequirements,route.getDefaults());var compiledPath='/'+this.replaceParameters(pattern,parameters);return new CompiledRoute(compiledPath,parameters,route)}}]);return RouteCompiler}();