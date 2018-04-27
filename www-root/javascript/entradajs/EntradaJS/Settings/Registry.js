'use strict';var _createClass=function(){function defineProperties(target,props){for(var i=0;i<props.length;i++){var descriptor=props[i];descriptor.enumerable=descriptor.enumerable||false;descriptor.configurable=true;if('value'in descriptor)descriptor.writable=true;Object.defineProperty(target,descriptor.key,descriptor)}}return function(Constructor,protoProps,staticProps){if(protoProps)defineProperties(Constructor.prototype,protoProps);if(staticProps)defineProperties(Constructor,staticProps);return Constructor}}();function _classCallCheck(instance,Constructor){if(!(instance instanceof Constructor)){throw new TypeError('Cannot call a class as a function')}}var Setting=use('./Setting');var Types=use('./Types');var InvalidArgumentException=use('./Exception/InvalidArgumentException');var SettingNotFoundException=use('./Exception/SettingNotFoundException');module.exports=function(){function Registry(){_classCallCheck(this,Registry);console.log('Init registry...');this.settings={};this.types=new Types}_createClass(Registry,[{key:'createSetting',value:function createSetting(name,type,value){var settingType=this.types.findByNameOrAlias(type);return new Setting(name,settingType,value)}},{key:'registerSetting',value:function registerSetting(setting){if(setting instanceof Setting===false){throw new InvalidArgumentException('The registerSetting() method may only accept Setting objects.')}this.settings[setting.name()]=setting}},{key:'settingIsRegistered',value:function settingIsRegistered(setting_name){return!!this.settings[setting_name]}},{key:'get',value:function get(setting_name){if(!this.settingIsRegistered(setting_name)){throw new SettingNotFoundException('The requested setting has not been registered: '+setting_name)}return this.settings[setting_name]}},{key:'set',value:function set(setting_name,value){}}]);return Registry}();