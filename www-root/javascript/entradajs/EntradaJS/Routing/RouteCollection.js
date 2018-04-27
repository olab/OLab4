'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var Route=use('./Route');module.exports=function(){function RouteCollection(){var prefix=arguments.length>0&&arguments[0]!==undefined?arguments[0]:'';var routes=arguments.length>1&&arguments[1]!==undefined?arguments[1]:[];var collections=arguments.length>2&&arguments[2]!==undefined?arguments[2]:[];_classCallCheck(this,RouteCollection);this.prefix=prefix;this.routes=[];this.collections=[];this.parent=null;this.addRoutes(routes);this.addCollections(collections)}_createClass(RouteCollection,[{key:'getParentCollection',value:function getParentCollection(){return this.parent}},{key:'setParentCollection',value:function setParentCollection(collection){this.parent=collection}},{key:'addRoute',value:function addRoute(route){if(route instanceof Route===false){throw new TypeError('RouteCollection#addRoute expects a Route object.')}route.setCollection(this);this.routes.push(route)}},{key:'addRoutes',value:function addRoutes(routes){var _iteratorNormalCompletion=true;var _didIteratorError=false;var _iteratorError=undefined;try{for(var _iterator=routes[Symbol.iterator](),_step;!(_iteratorNormalCompletion=(_step=_iterator.next()).done);_iteratorNormalCompletion=true){var route=_step.value;this.addRoute(route)}}catch(err){_didIteratorError=true;_iteratorError=err}finally{try{if(!_iteratorNormalCompletion&&_iterator.return){_iterator.return()}}finally{if(_didIteratorError){throw _iteratorError}}}}},{key:'addCollection',value:function addCollection(collection){if(collection instanceof RouteCollection===false){throw new TypeError('RouteCollection#addCollection expects a RouteCollection object.')}collection.setParentCollection(this);this.collections.push(collection)}},{key:'addCollections',value:function addCollections(collections){var _iteratorNormalCompletion2=true;var _didIteratorError2=false;var _iteratorError2=undefined;try{for(var _iterator2=collections[Symbol.iterator](),_step2;!(_iteratorNormalCompletion2=(_step2=_iterator2.next()).done);_iteratorNormalCompletion2=true){var collection=_step2.value;this.addCollection(collection)}}catch(err){_didIteratorError2=true;_iteratorError2=err}finally{try{if(!_iteratorNormalCompletion2&&_iterator2.return){_iterator2.return()}}finally{if(_didIteratorError2){throw _iteratorError2}}}}},{key:'getRoutes',value:function getRoutes(){return this.routes}},{key:'findRouteByPath',value:function findRouteByPath(path){var _iteratorNormalCompletion3=true;var _didIteratorError3=false;var _iteratorError3=undefined;try{for(var _iterator3=this.routes[Symbol.iterator](),_step3;!(_iteratorNormalCompletion3=(_step3=_iterator3.next()).done);_iteratorNormalCompletion3=true){var route=_step3.value;if(route.getPattern()===path){return route}}}catch(err){_didIteratorError3=true;_iteratorError3=err}finally{try{if(!_iteratorNormalCompletion3&&_iterator3.return){_iterator3.return()}}finally{if(_didIteratorError3){throw _iteratorError3}}}return null}},{key:'findRouteByName',value:function findRouteByName(name){var deep=arguments.length>1&&arguments[1]!==undefined?arguments[1]:false;var routes=this.routes;if(deep){routes=this.getFlattenedRoutes()}var _iteratorNormalCompletion4=true;var _didIteratorError4=false;var _iteratorError4=undefined;try{for(var _iterator4=routes[Symbol.iterator](),_step4;!(_iteratorNormalCompletion4=(_step4=_iterator4.next()).done);_iteratorNormalCompletion4=true){var route=_step4.value;if(route.getName()===name){return route}}}catch(err){_didIteratorError4=true;_iteratorError4=err}finally{try{if(!_iteratorNormalCompletion4&&_iterator4.return){_iterator4.return()}}finally{if(_didIteratorError4){throw _iteratorError4}}}return null}},{key:'findCollectionByPrefix',value:function findCollectionByPrefix(prefix){var _iteratorNormalCompletion5=true;var _didIteratorError5=false;var _iteratorError5=undefined;try{for(var _iterator5=this.collections[Symbol.iterator](),_step5;!(_iteratorNormalCompletion5=(_step5=_iterator5.next()).done);_iteratorNormalCompletion5=true){var collection=_step5.value;if(collection.getPathPrefix()===prefix){return collection}}}catch(err){_didIteratorError5=true;_iteratorError5=err}finally{try{if(!_iteratorNormalCompletion5&&_iterator5.return){_iterator5.return()}}finally{if(_didIteratorError5){throw _iteratorError5}}}return null}},{key:'getFlattenedRoutes',value:function getFlattenedRoutes(){var routes=Array.from(this.routes);var _iteratorNormalCompletion6=true;var _didIteratorError6=false;var _iteratorError6=undefined;try{for(var _iterator6=this.collections[Symbol.iterator](),_step6;!(_iteratorNormalCompletion6=(_step6=_iterator6.next()).done);_iteratorNormalCompletion6=true){var collection=_step6.value;routes=routes.concat(collection.getFlattenedRoutes())}}catch(err){_didIteratorError6=true;_iteratorError6=err}finally{try{if(!_iteratorNormalCompletion6&&_iterator6.return){_iterator6.return()}}finally{if(_didIteratorError6){throw _iteratorError6}}}return routes}},{key:'getFlattenedCollections',value:function getFlattenedCollections(){var collections=Array.from(this.collections);var _iteratorNormalCompletion7=true;var _didIteratorError7=false;var _iteratorError7=undefined;try{for(var _iterator7=this.collections[Symbol.iterator](),_step7;!(_iteratorNormalCompletion7=(_step7=_iterator7.next()).done);_iteratorNormalCompletion7=true){var collection=_step7.value;collections=collections.concat(collection.getFlattenedCollections())}}catch(err){_didIteratorError7=true;_iteratorError7=err}finally{try{if(!_iteratorNormalCompletion7&&_iterator7.return){_iterator7.return()}}finally{if(_didIteratorError7){throw _iteratorError7}}}return collections}},{key:'getCollections',value:function getCollections(){return this.collections}},{key:'setPathPrefix',value:function setPathPrefix(path){this.prefix=path}},{key:'getPathPrefix',value:function getPathPrefix(){return this.prefix}}]);return RouteCollection}();