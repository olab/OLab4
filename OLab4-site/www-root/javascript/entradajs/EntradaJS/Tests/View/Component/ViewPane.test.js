'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var ViewPane=use('EntradaJS/View/Component/ViewPane.vue');module.exports=function(){function ViewPane_Test(){_classCallCheck(this,ViewPane_Test);QUnit.module('View/Component/ViewPane.vue')}_createClass(ViewPane_Test,[{key:'run',value:function run(){QUnit.test('ViewPane is loaded',function(assert){assert.ok(ViewPane)});QUnit.test('ViewPane sets component to render',function(assert){var testObj={};var pane=new ViewPane;pane.setView(testObj);assert.equal(testObj,pane.currentView)})}}]);return ViewPane_Test}();