/**
 * Main olab player class
 * @param {} authToken = current auth token
 * @param {} targetId = main content view div name for data binding
 * @param {} websiteRootUrl = root url for web site
 * @param {} pageUrl = current page url (document.location)
 * @returns {} Service definition
 */

"use strict";
// main view class
var OlabNodePlayer = function(params) {

    var vm = this;

    vm.Utilities = new OLabUtilities(params.siteRoot, params.location, params.authToken);

    // get the map and nodeId from the url location hash
    var paramArray = vm.Utilities.getUrlParameters(params.location.hash);
    vm.urlParameters = {};
    vm.urlParameters.mapId = paramArray[0];
    vm.urlParameters.nodeId = paramArray[1];
    vm.urlParameters.linkId = null;

    vm.targetId = vm.Utilities.normalizeDivId(params.targetId);
    vm.websiteUrl = params.siteRoot;
    vm.mediaUrl = vm.websiteUrl + "/images/olab/files";
    vm.moduleUrl = params.siteRoot + '/olab';

    vm.qs = vm.Utilities.convertQSToArray(window.location.href);

    vm.server = [];
    vm.map = [];
    vm.node = [];
    vm.H5P = [];
    vm.state = [];
    vm.haveMapData = false;

    // vue.js containers
    vm.nodeVue = [];
    vm.headerVue = [];
    vm.footerVue = [];

    vm.restApiUrl = params.apiRoot + '/olab';

    // set the event handler for when the url location hash changes
    window.onhashchange = onHashChanged;

    // expose methods to 'friend' objects that get this object
    // via dependancy injection
    vm.getCounterBindingVariable = getCounterBindingVariable;
    vm.getQuestion = getQuestion;
    vm.getCounter = getCounter;
    vm.getFile = getFile;
    vm.getAvatar = getAvatar;
    vm.log = vm.Utilities.log; 

    // these are the methods/properties we expose to the outside
    vm.service = {
        app:vm.nodeVue,
        downloadFile:downloadFile,
        info:info,
        navigate:navigate,
        parameters: vm.urlParameters,
        play:play,
        onHashChanged:onHashChanged,
        utilities: vm.Utilities,
        getCounter: getCounter,
        getConstant: getConstant,
        getQuestion: getQuestion
    };

    return vm.service;

    /**
    * Applies the current node state to the view model
    */
    function applyStateToCounters() {

        jQuery.each(vm.state.state_data.server,
            function(index, value) {

                // search for counter at server level
                var counter = vm.Utilities.searchObjectArray(vm.server.counters, value.id);

                if (counter !== null) {
                    counter.item.value = value.value;
                }
            });

        jQuery.each(vm.state.state_data.map,
            function(index, value) {

                // search for counter at server level
                var counter = vm.Utilities.searchObjectArray(vm.map.counters, value.id);

                if (counter !== null) {
                    counter.item.value = value.value;
                }
            });

        jQuery.each(vm.state.state_data.node,
            function(index, value) {

                // search for counter at map level
                var counter = vm.Utilities.searchObjectArray(vm.node.counters, value.id);

                if (counter !== null) {
                    counter.item.value = value.value;
                }
            });
    }

    /**
     * Autoloads server and map-level javascript
     * @returns {undefined} 
    */
    function autoloadScripts() {

        var list = vm.server.scripts;
        // autoload and execute server-level scripts
        jQuery.each(list, function(index, value) {

                var url = vm.websiteUrl + "/javascript/olab/autoload/script" + value;
                // autoload the server and map-level javascript scripts
                jQuery.getScript(url)
                    .done(function(script, textStatus) {console.log(textStatus);})
                    .fail(function(jqxhr, settings, exception) {alert(url + ":" + exception);});

            });

        list = vm.map.scripts;
        // autoload and execute map-level scripts
        jQuery.each(list, function(index, value) {

                var url = vm.websiteUrl + "/javascript/olab/autoload/script" + value;
                // autoload the server and map-level javascript scripts
                jQuery.getScript(url)
                    .done(function(script, textStatus) {console.log(textStatus);})
                    .fail(function(jqxhr, settings, exception) {alert(url + ":" + exception);});

            });

        list = vm.node.scripts;
        // autoload and execute node-level scripts
        jQuery.each(list, function(index, value) {

                var url = vm.websiteUrl + "/javascript/olab/autoload/script" + value;
                // autoload the server and map-level javascript scripts
                jQuery.getScript(url)
                    .done(function(script, textStatus) {console.log(textStatus);})
                    .fail(function(jqxhr, settings, exception) {alert(url + ":" + exception);});

            });
    }

    /**
     * Builds node urls for node links in the current node
     * @param {string} data array 
     * @returns {array} urls
    */
    function buildNodeUrls(data) {
        jQuery.each(data,
            function(index, value) {
                value.url = vm.moduleUrl + '/play#' + value.map_id + ":" + value.DestinationNode.id;
            });
        return data;
    }

    /**
     * Creates vue instance with source markup
     * @param {any} targetId HTML target DIV id
     * @param {any} source OLab wikitag-ified markup
     * @returns {null} nothing
     */
    function createNodeVue(targetId, source ) {

        // compile the markup so Vue components resolve 
        var res = Vue.compile( source.text );

        // spin up Vue to load the compiled markup
        return new Vue({
            el:targetId,
            data:{
                websiteRoot:vm.websiteUrl,
                server:vm.server,
                map:vm.map,
                node:vm.node,
                loadingList: false
            },
            render:res.render,

            staticRenderFns:res.staticRenderFns,

            mounted: function() {

                //let snippet = new DOMParser().parseFromString(source.scripts, 'text/html').querySelector("script");
                //let recaptchaScript = document.createElement('script');
                //recaptchaScript.setAttribute('type', 'text/javascript');
                //recaptchaScript.setAttribute('src',
                //    'http://www.conceptispuzzles.com/index.aspx?uri=channel/bat-starter-1/1/js');
                //document.head.appendChild(recaptchaScript);

                //var header = document.head.children;

            },

            methods:{

                constant:function(id) {

                    // if no id, return everything
                    if (id === null) {
                        var items = this.server.constants;
                        items = items.concat(this.map.constants);
                        items = items.concat(this.node.constants);
                        return items;
                    }

                    var item = vm.Utilities.searchObjectArray(this.server.constants, id);

                    if (item === null)
                        item = vm.Utilities.searchObjectArray(this.map.constants, id);

                    if (item === null)
                        item = vm.Utilities.searchObjectArray(this.node.constants, id);

                    if (item !== null) {
                        return item.item;
                    }

                    item = {};
                    item.value = "[[CONST:" + id + " ERROR: 'not found']]";
                    return item;
                },

                file:function(id) {

                    try {
                      
                        // if no id, return everything
                        if (id === null) {
                            var items = this.server.files;
                            items = items.concat(this.map.files);
                            items = items.concat(this.node.files);
                            return items;
                        }

                        var item = vm.Utilities.searchObjectArray(this.server.files, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.map.files, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.node.files, id);

                        if (item !== null) {
                            return item.item;
                        }
                      
                    } catch (e) {
                        vm.Utilities.log.fatal('file: ' + e.message );
                    } 

                    item = {};
                    item.value = "[[MR:" + id + " ERROR: 'not found']]";
                    return item;
                },

                question:function(id) {

                    try {

                        var item = vm.Utilities.searchObjectArray(this.server.questions, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.map.questions, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.node.questions, id);

                        if (item !== null) {
                            return item.item;
                        }

                        // if no id, return everything
                        if (id === null) {
                            var items = this.server.questions;
                            items = items.concat(this.map.questions);
                            items = items.concat(this.node.questions);
                            return items;
                        }

                    } catch (e) {
                        vm.Utilities.log.fatal('question: ' + e.message );
                    } 

                    item = {};
                    item.value = "[[QU:" + id + " ERROR: 'not found']]";
                    return item;
                },

                link:function(id) {

                    try {

                        for (var key in this.node.MapNodeLinks) {

                            var data = this.node.MapNodeLinks[key];
                            if (data.DestinationNode.id === id) {
                                return data;
                            }
                        }

                    } catch (e) {
                        vm.Utilities.log.fatal('link: ' + e.message );
                    } 

                    return null;

                },

                onDropdownResponseChanged: function(data) {

                    try {

                        vm.Utilities.log.debug('Xmit: ' + data.responseId + "= selected");
                        data.state_data = vm.state.state_data;
                        data.submitId = "#submit_" + data.questionId;
                        if (data.questionshowSubmit) {
                            jQuery( data.submitId ).show();
                        }

                        var url = vm.restApiUrl + '/question/dropdown/' + this.node.id;
                        vm.Utilities.postJson(url, data, onQuestionResponseSucceeded, onQuestionResponseFailed);

                    } catch (e) {
                        vm.Utilities.log.fatal('onDropdownResponseChanged: ' + e.message );
                    } 


                },

                onSliderResponseChanged: function(questionId, data) {

                  try { 

                    vm.Utilities.log.debug('Xmit: ' + questionId + "=" + data.value);
                    data.state_data = vm.state.state_data;
                    data.value = data.value;
                    data.submitId = "#submit_" + questionId;
                    if (data.questionshowSubmit) {
                      jQuery( data.submitId ).show();
                    }

                    var url = vm.restApiUrl + '/question/slider/' + this.node.id;
                    vm.Utilities.postJson(url, data, onQuestionResponseSucceeded, onQuestionResponseFailed);
                        
                  } catch (e) {
                    vm.Utilities.log.fatal('onSliderResponseChanged: ' + e.message );
                  } 

                },

                // handler for question responses
                onMultichoiceResponseChanged: function(data) {

                    try {

                        vm.Utilities.log.debug('Xmit: ' + data.responseId + "=" + data.value);
                        data.state_data = vm.state.state_data;
                        data.value = data.value;
                        data.submitId = "#submit_" + data.questionId + "_" + data.responseId;
                        if (data.questionshowSubmit) {
                            jQuery( data.submitId ).show();
                        }

                        var url = vm.restApiUrl + '/question/multichoice/' + this.node.id;
                        vm.Utilities.postJson(url, data, onQuestionResponseSucceeded, onQuestionResponseFailed);
                        
                    } catch (e) {
                        vm.Utilities.log.fatal('onMultichoiceResponseChanged: ' + e.message );
                    } 

                },

                // handler for question responses
                onRadioResponseChanged:function(data) {

                    try {
                    
                        vm.Utilities.log.debug('Xmit: ' + data.responseId + "=" + data.value);
                        data.state_data = vm.state.state_data;
                        data.submitId = "#submit_" + data.questionId + "_" + data.responseId;
                        if (data.questionshowSubmit) {
                            jQuery( data.submitId ).show();
                        }

                        var url = vm.restApiUrl + '/question/radio/' + this.node.id;
                        vm.Utilities.postJson(url, data, onQuestionResponseSucceeded, onQuestionResponseFailed);

                    } catch (e) {
                        vm.Utilities.log.fatal('onMultichoiceResponseChanged: ' + e.message );
                    } 
                    
                },

                onSuspendClicked: function(data) {

                  try {
                    
                    vm.Utilities.log.debug('Xmit: suspend');
                    var url = vm.restApiUrl + '/suspend/' + this.map.id + '/' + this.node.id;
                    vm.Utilities.postJson(url, data, onSuspendSucceeded, onSuspendFailed);

                  } catch (e) {
                    vm.Utilities.log.fatal('onSuspendClicked: ' + e.message );
                  } 

                }
            }

        });
    }

    function createScriptTag( id, href ) {

      // check if script loaded already
      var script = document.getElementById(id);

      if (script != null) {

        vm.Utilities.log.debug('script ' + id + ': already loaded. removing.');
        script.parentNode.removeChild(script);
      }

      script = document.createElement('script');
      script.async = false;
      script.setAttribute('type', 'text/javascript');
      script.setAttribute('id', id );
      script.setAttribute('src', href);

      return script;
    }

    /**
     * Makes the node text compatible with Vue (single HTML root)
     * @param {} nodeText 
     * @returns {string} HTML div string
     */
    function encapsulateNodeMarkup(type, nodeText) {
        return '<div id="olab' + type + 'Content">' + nodeText + '</div>';
    }

    /**
     * Substitutes WIKI tags in the node markup and replaces
     * them with VUE.JS components
     * @param {} node markup
     * @returns {string} markup
     */
    function dewikifyMarkup(show_wiki, markup) {

        // get a list of Wiki tags
        var tags = vm.Utilities.getWikiTags(markup);

        // loop through all remaining wiki tags in the markup
        jQuery.each(tags,
            function(key, value) {

                // split up wiki tag into parts
                var tagParts = vm.Utilities.getWikiTagParts(value);
                try {

                    // try and spin up handler for wiki tag by 'new'ing the object.
                    // the various classes are autoloaded via PHP in the main view html file
                    var renderer = new window["Olab" + tagParts[0] + "Tag"](vm);

                    // pass all the parts into the handler for processing and store the rendered
                    // contents back to the main markup
                    var text = renderer.render(tagParts);
                    var wikiTag = "[[" + value + "]]";

                    if (show_wiki === "0") {
                      markup = String(markup).replace(wikiTag, text);
                    }
                    else if (show_wiki === "2") {
                      markup = String(markup).replace(wikiTag, wikiTag + text);
                    }

                } catch (e) {

                    // tag isn't found (TypeError), flag it as such, else flag error from exception
                    if (e instanceof TypeError) {
                        markup = String(markup).replace("[[" + value + "]]",
                            "&lt;&lt;unsupported tag '" + tagParts[0] + "'&gt;&gt;");
                    } else {
                        markup = String(markup).replace("[[" + value + "]]",
                            "&lt;&lt;error '" + value + "': " + e.message + "'&gt;&gt;");
                    }
                }

            });

        return markup;
    }

    /**
     * Downloads a file from the server
     * @param {} file resource id
     * @returns {} 
     */
    function downloadFile(id) {
        vm.Utilities.downloadFile(id);
    }

    /**
     * Utility function to get a requested constant from the scoped counter objects
     * @param {} id or name
     * @returns { constant } 
     */
    function getConstant(id) {

      // if no id, return everything
      if (id === null) {
        var items = vm.server.constants;
        items = items.concat(vm.map.constants);
        items = items.concat(vm.node.constants);
        return items;
      }

      var item = vm.Utilities.searchObjectArray(vm.server.constants, id);

      if (item === null)
        item = vm.Utilities.searchObjectArray(vm.map.constants, id);

      if (item === null)
        item = vm.Utilities.searchObjectArray(vm.node.constants, id);

      if (item !== null) {
        return item.item;
      }

      return null;
    }

    /**
     * Utility function to get a requested counter from the scoped counter objects
     * @param {} id or name
     * @returns { counter } 
     */
    function getCounter(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.counters;
            items = items.concat(vm.map.counters);
            items = items.concat(vm.node.counters);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.counters, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.counters, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.counters, id);

        if (item !== null) {
            return item.item;
        }

        return null;
    }

    /**
     * Builds a vue.js binding variable to one-way bind to an UI object in the form
     * @param {} id 
     * @returns {} 
     */
    function getCounterBindingVariable(id) {

        var item = vm.Utilities.searchObjectArray(vm.server.counters, id);
        if (item !== null) {
            return "server.counters[" + item.index + "]";
        }

        item = vm.Utilities.searchObjectArray(vm.map.counters, id);
        if (item !== null) {
            return "map.counters[" + item.index + "]";
        }

        item = vm.Utilities.searchObjectArray(vm.node.counters, id);
        if (item !== null) {
            return "node.counters[" + item.index + "]";
        }

        return null;
    }

    /**
     * Utility function to get a requested avatar from the scoped objects
     * @param {} file id
     * @returns { file } 
     */
    function getAvatar(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.Avatars;
            items = items.concat(vm.map.Avatars);
            items = items.concat(vm.node.Avatars);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.Avatars, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.Avatars, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.Avatars, id);

        if (item !== null) {
            return item.item;
        }

        return null;
    }

    /**
     * Utility function to get a requested file from the scoped file objects
     * @param {} id or name
     * @returns { file } 
     */
    function getFile(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.files;
            items = items.concat(vm.map.files);
            items = items.concat(vm.node.files);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.files, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.files, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.files, id);

        if (item !== null) {
            return item.item;
        }

        return null;
    }

    /**
     * Utility function to get a requested question from the scoped question objects
     * @param {} id or name
     * @returns { question } 
     */
    function getQuestion(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.questions;
            items = items.concat(vm.map.questions);
            items = items.concat(vm.node.questions);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.questions, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.questions, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.questions, id);

        if (item !== null) {
            return item.item;
        }

        return null;
    }

    /**
     * Plays the node info
     * @param {number} node id
     * @returns {} 
     */
    function info(nodeId) {

        // test if no node passed in - get nodeId from Url parameters
        if (typeof nodeId !== 'undefined') {
            vm.urlParameters.nodeId = nodeId;
        }

        var url = vm.restApiUrl + '/info/' + vm.urlParameters.mapId + "/" + vm.urlParameters.nodeId;
        vm.Utilities.log.debug('info url: ' + url);

        vm.Utilities.getJson(url, vm.urlParameters, onInfoLoadSucceeded, onLoadError);

    }

    function injectCssAssets(assets) {

      if (!assets) {
        return;
      }

      for (var i = 0; i < assets.length; i++) {

        var id = vm.Utilities.getFileName( assets[i] );

        // check if style loaded already
        var link = document.getElementById(id);

        if (link == null) {

          link = document.createElement("link");
          link.type = "text/css";
          link.setAttribute('id', assets[i]);
          link.href = assets[i];
          link.setAttribute('id', id );
          link.rel = "stylesheet";
          document.getElementsByTagName("body")[0].appendChild(link);

          vm.Utilities.log.debug('injecting css ' + id + ": " + assets[i]);

        } else {

          vm.Utilities.log.debug('css ' + assets[i].id + ": already loaded");

        }

      }

    }

    function injectH5P() {

      // test if H5P integration object defined
      var t = typeof( H5PIntegration );
      if ( ( t != "object") || ( H5PIntegration == null ) )
        return;

      // inject H5P core assets
      injectCssAssets( H5PIntegration.core.styles || [] );
      injectCssAssets( H5PIntegration.loadedCss || [] );

      var scripts = H5PIntegration.core.scripts;
      scripts.splice( 1, 0, H5PIntegration.loadedJs );
      testLoad( scripts || [] );
      //testLoad( H5PIntegration.loadedJs || [] );

      //injectScriptAssets( H5PIntegration.core.scripts || [] );
      //injectScriptAssets( H5PIntegration.loadedJs || [] );

    }

    /* Execute raw script */
    function injectRawScriptAssets(assets) {

      if (!assets) {
        return;
      }

      // loop through all assets and eval() it to ensure it's executed SYNCHRONOUSLY.
      for (var i = 0; i < assets.length; i++) {

        try {

          var src = assets[i].src;
          // yes, I know: people say eval is evil.  It is used here to ensure
          // the code is executed synchronously.  Later loads of server-side
          // .js files may rely on the raw script to be executed completely
          // before they are loaded.
          eval(src);

          vm.Utilities.log.debug('raw script ' + assets[i].id + ': executed.');

        } catch (e) {

          vm.Utilities.log.error('raw script ' + assets[i].id + ': error:');
          vm.Utilities.log.error(e);

        }

      }

    }

    function injectScriptAssets(assets) {

      if (!assets) {
        return;
      }

      for (var i = 0; i < assets.length; i++) {

        var url = assets[i].src;
        var id = assets[i].id;

        vm.Utilities.log.debug('injecting script ' + id + ": " + url);

        // create script tag and insert into DOM
        var script = createScriptTag( id, url );

        script.onload = function() {
          onPostLoadScript( this );
        }

        document.getElementsByTagName('body')[0].appendChild(script);
      }

    }

    /**
     * Navigate to a new map node
     * @param {} node id
     * @returns { } 
     */
    function navigate(nodeId, linkId) {

        vm.log.debug("navigate from node " + vm.urlParameters.nodeId + " to node: " + nodeId );

        // save previous node
        vm.urlParameters.linkId = linkId;

        var param = vm.urlParameters.mapId + ":" + nodeId;
        window.location.hash = param;
    }

    /**
     * Handler if the url hash changes (navigate to new node)
     * @returns { } 
     */
    function onHashChanged() {

        var paramArray = vm.Utilities.getUrlParameters(window.location.hash);
        vm.urlParameters.mapId = paramArray[0];
        vm.urlParameters.nodeId = paramArray[1];

        play(vm.urlParameters.nodeId);
    }

    /**
     * Node info retrieval successful
     * @param {} node data
     * @returns {} 
     */
    function onInfoLoadSucceeded(data) {

    }

    /**
     * Node markup retrieval failed
     * @param {} node data error
     * @returns {} 
     */
    function onLoadError(data) {

      alert(data);

    }

    function onLoadScript_h5pcorejsh5p( source ) {
      vm.H5P = window.H5P = window.H5P || {};  
    }

    function onLoadScript_h5pcorejsjquery( source ) {

      var headerObj = {};
      headerObj[ 'Authorization'] = vm.Utilities.getAuthHeader();
      H5P.jQuery.ajaxSetup({ headers: headerObj });

    }

    function onPostLoadScript( source ) {

      var id = source.id;
      vm.Utilities.log.debug('injected/executed script ' + id );

      if ( id.indexOf( 'jquery.js' ) != -1 ) { 
        onLoadScript_h5pcorejsjquery( source );
      }

      else if ( id.indexOf( 'h5p.js' ) != -1 ) { 
        onLoadScript_h5pcorejsh5p( source );
      }

    }

    /**
     * Handler for a error found in posting of a question response to the server
     * @returns { } 
     */
    function onQuestionResponseFailed(data) {
        alert(data);
    }

    /**
     * Handler for a successful posting of a question response to the server
     * @returns { } 
     */    
    function onQuestionResponseSucceeded(data) {

        // turn off any spinning img's for REST call
        if (data.parameters.questionshowSubmit) {
            jQuery(data.parameters.submitId).hide();
        }

        // save state data to model
        vm.state = data.state;
        vm.state.state_data = JSON.parse(vm.state.state_data);

        // overlay state counter values on top of count objects
        applyStateToCounters();
    }

    /**
     * Node markup retrieval successful
     * @param {} node data
     * @returns {} 
     */
    function onNodeLoadSucceeded(data) {

        if (data.node !== null) {

            // save the current node id (since root nodes are requested with id = 0
            // and we need to know/save the actual node id)
            vm.urlParameters.nodeId = data.node.id;

            // translate node link to get valid urls for this server/site     
            data.node.MapNodeLinks = buildNodeUrls(data.node.MapNodeLinks);
            vm.node = data.node;

            // save map data
            if (vm.haveMapData === false) {

                vm.server = data.server;
                vm.map = data.map;

                // flag that we have map data so subsequent 'play' calls don't re-ask for it.
                vm.haveMapData = true;

            }

            // autoload any server, map, or node-level scripts
            // autoloadScripts();

            // save state data to model
            vm.state = data.state;
            vm.state.state_data = JSON.parse(vm.state.state_data);

            // overlay state counter values on top of count objects
            applyStateToCounters();

            // render any map-level content (header/footer, etc)
            renderMapContent( vm.map );

            // render node content
            renderNodeContent(vm.node);

            vm.nodeVue.node = vm.node;
          
            //injectScriptAssets(data.scripts_stack || [] );
            injectRawScriptAssets(data.raw_scripts_stack || [] );

            // load document-level H5P infrastructure, if there
            // is any.
            injectH5P();

            //injectCssAssets(data.styles_stack);
            //injectScriptAssets(data.scripts_stack);

            // set the browser page title to the node title
            document.title = vm.node.title;

        } else {
            vm.nodeVue.content = "error";
        }
    }

    function onSuspendSucceeded(data) {

    }
  
    function onSuspendFailed(data) {
      alert(data);
    }

    /**
     * Plays the map
     * @param {number} node id
     * @returns {} 
     */
    function play(nodeId) {

      // test if no node passed in - get nodeId from Url parameters
      if (typeof nodeId !== 'undefined') {
        vm.urlParameters.nodeId = nodeId;
      }

      // build URL for node to play
      var url = vm.restApiUrl + '/play/' + vm.urlParameters.mapId + "/" + vm.urlParameters.nodeId;

      // if don't have map data yet, signal server to get and return it with request
      if (vm.haveMapData === false) {

        if (url.indexOf("?") === -1) {
          url += '?includeMapData=1';
        } else {
          url += '&includeMapData=1';
        }
      }

      // if there's was a link involved, then add it's id as a querystring so we can
      // have server-side test/record if it's a 'visit once' link
      if (vm.urlParameters.linkId !== null) {

        if (url.indexOf("?") === -1) {
          url += '?linkId=' + vm.urlParameters.linkId;
        } else {
          url += '&linkId=' + vm.urlParameters.linkId;
        }
      }

      vm.Utilities.log.debug('play url: ' + url);

      vm.Utilities.getJson(url, null, onNodeLoadSucceeded, onLoadError);
    }

    /**
     * Translates raw OLab markup into vue-ready markup
     * @param {any} contentName target div id "#olab<contentName>Content"
     * @param {any} source olab (wikitag-ified) markup
     * @returns {any} new vue-able html markup
     */
    function renderContent( contentName, source) {

      // test if any olab markup passed in
      if (source.text !== null) {

        source.text = encapsulateNodeMarkup( contentName, source.text);

        // test if bypassing Wiki tag rendering
        var show_wiki = vm.qs["showWiki"];
        // test if not set
        if (typeof show_wiki === 'undefined') {
          show_wiki = "0";
        }

        if (show_wiki !== "1")
          source.text = dewikifyMarkup(show_wiki, source.text);

        return createNodeVue('#olab' + contentName + 'Content', source);
      }

      return null;
    }

    /**
     * Spins up VUE js with the map markups (header/footer)
     * @returns {} 
     */
    function renderMapContent( map ) {

      if (typeof map.header !== 'undefined') {
        map.text = map.header;
        vm.headerVue = renderContent('Header', map);
      }
      if (typeof map.footer !== 'undefined') {
        map.text = map.footer;
        vm.footerVue = renderContent('Footer', map);
      }
    }

    /**
     * Spins up VUE js with the node markup
     * @argument {any} node HTML
     * @returns {undefined} 
     */
    function renderNodeContent(node) {

      vm.nodeVue = renderContent('Node', node ); 

      // if any node annotations, add them to markup DIV
      if (node.annotation != null) {

        if (node.annotation.length > 0) {

          jQuery("#olabAnnotationContent").html(node.annotation);
          // add the annotation CSS style class so we don't 
          // see the annotation style if there nothing to display
          jQuery("#olabAnnotationContent").addClass("annotation");

        }
      }
    }

    function testLoad( hrefArray ) {

      jsuLoader
        .reset()
        .addScript( hrefArray )
        .onComplete(() => console.info("* ALL SCRIPTS LOADED *"))
        .load();
    }

};
