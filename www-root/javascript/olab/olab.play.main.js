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
var olab = null;

var OlabNodePlayer = function(params) {

    var vm = this;

    vm.Utilities = new OLabUtilities(params.siteRoot, params.location, params.authToken);

    // get the map and nodeId from the url location hash
    var paramArray = vm.Utilities.getUrlParameters(params.location.hash);
    vm.urlParameters = {};
    vm.urlParameters.mapId = paramArray[0];
    vm.urlParameters.nodeId = paramArray[1];

    vm.targetId = vm.Utilities.normalizeDivId(params.targetId);
    vm.websiteUrl = params.siteRoot;
    vm.moduleUrl = params.siteRoot + '/olab';

    vm.qs = vm.Utilities.convertQSToArray(window.location.href);

    vm.server = [];
    vm.map = [];
    vm.node = [];

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
    vm.log = vm.Utilities.log;

    // these are the methods/properties we expose to the outside
    vm.service = {
        app:vm.nodeVue,
        downloadFile:downloadFile,
        navigate:navigate,
        play:play,
        onHashChanged:onHashChanged
    };

    return vm.service;

    /**
    * Applies the current node state to the view model
    */
    function applyStateToCounters() {

        jQuery.each(vm.state.state_data.server,
            function(index, value) {

                // search for counter at server level
                var counter = vm.Utilities.searchObjectArray(vm.server.Counters, value.id);

                if (counter !== null) {
                    counter.item.value = value.value;
                }
            });

        jQuery.each(vm.state.state_data.map,
            function(index, value) {

                // search for counter at server level
                var counter = vm.Utilities.searchObjectArray(vm.map.Counters, value.id);

                if (counter !== null) {
                    counter.item.value = value.value;
                }
            });

        jQuery.each(vm.state.state_data.node,
            function(index, value) {

                // search for counter at map level
                var counter = vm.Utilities.searchObjectArray(vm.node.Counters, value.id);

                if (counter !== null) {
                    counter.item.value = value.value;
                }
            });
    }

    /**
     * Autoloads server and map-level javascript
     * @returns {} 
    */
    function autoloadScripts() {

        // autoload and execute server-level scripts
        jQuery.each(vm.server.Scripts,
            function(index, value) {

                var url = vm.websiteUrl + "/javascript/olab/autoload" + value;
                // autoload the server and map-level javascript scripts
                jQuery.getScript(url)
                    .done(function(script, textStatus) {console.log(textStatus);})
                    .fail(function(jqxhr, settings, exception) {alert(url + ":" + exception);});

            });

        // autoload and execute map-level scripts
        jQuery.each(vm.map.Scripts,
            function(index, value) {

                var url = vm.websiteUrl + "/javascript/olab/autoload" + value;
                // autoload the server and map-level javascript scripts
                jQuery.getScript(url)
                    .done(function(script, textStatus) {console.log(textStatus);})
                    .fail(function(jqxhr, settings, exception) {alert(url + ":" + exception);});

            });

    }

    /**
     * Builds node urls for node links in the current node
     * @param {} link array 
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
     * Makes the node text compatible with Vue (single HTML root)
     * @param {} nodeText 
     * @returns {} 
     */
    function encapsulateNodeMarkup(type, nodeText) {
        return '<div id="olab' + type + 'Content">' + nodeText + '</div>';
    }

    /**
     * Substitutes and resolves the WIKI tags in the node markup 
     * @param {} node markup
     * @returns {} 
     */
    function dewikifyMarkup(markup) {

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
                    markup = String(markup).replace("[[" + value + "]]", renderer.render(tagParts));

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
     * Utility function to get a requested counter from the scoped counter objects
     * @param {} counter id
     * @returns { counter } 
     */
    function getCounter(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.Counters;
            items = items.concat(vm.map.Counters);
            items = items.concat(vm.node.Counters);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.Counters, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.Counters, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.Counters, id);

        if (item !== null) {
            return item;
        }

        return null;
    }

    /**
     * Builds a vue.js binding variable to one-way bind to an UI object in the form
     * @param {} id 
     * @returns {} 
     */
    function getCounterBindingVariable(id) {

        var item = vm.Utilities.searchObjectArray(vm.server.Counters, id);
        if (item !== null) {
            return "server.Counters[" + item.index + "]";
        }

        item = vm.Utilities.searchObjectArray(vm.map.Counters, id);
        if (item !== null) {
            return "map.Counters[" + item.index + "]";
        }

        item = vm.Utilities.searchObjectArray(vm.node.Counters, id);
        if (item !== null) {
            return "node.Counters[" + item.index + "]";
        }

        return null;
    }

    /**
     * Utility function to get a requested file from the scoped file objects
     * @param {} file id
     * @returns { file } 
     */
    function getFile(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.Files;
            items = items.concat(vm.map.Files);
            items = items.concat(vm.node.Files);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.Files, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.Files, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.Files, id);

        if (item !== null) {
            return item;
        }

        return null;
    }

    /**
     * Utility function to get a requested question from the scoped question objects
     * @param {} question id
     * @returns { question } 
     */
    function getQuestion(id) {

        // if no id, return everything
        if (id === null) {
            var items = vm.server.Questions;
            items = items.concat(vm.map.Questions);
            items = items.concat(vm.node.Questions);
            return items;
        }

        var item = vm.Utilities.searchObjectArray(vm.server.Questions, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.map.Questions, id);

        if (item === null)
            item = vm.Utilities.searchObjectArray(vm.node.Questions, id);

        if (item !== null) {
            return item;
        }

        return null;
    }

    /**
     * Navigate to a new map node
     * @param {} node id
     * @returns { } 
     */
    function navigate(nodeId) {
        vm.urlParameters.nodeId = nodeId;
        var param = vm.urlParameters.mapId + ":" + vm.urlParameters.nodeId;
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
        if (data.parameters.questionShowSubmit) {
            jQuery(data.parameters.submitId).hide();
        }

        // save state data to model
        vm.state = data.state;
        vm.state.state_data = JSON.parse(vm.state.state_data);

        // overlay state counter values on top of count objects
        applyStateToCounters();
    }

    /**
     * Node markup retrieval failed
     * @param {} node data error
     * @returns {} 
     */
    function onNodeLoadError(data) {

        alert(data);

    }

    /**
     * Node markup retrieval successful
     * @param {} node data
     * @returns {} 
     */
    function onNodeLoadSucceeded(data) {

        if (data.node !== null) {

            // save map data
            if (vm.haveMapData === false) {

                vm.server = data.server;
                vm.map = data.map;

                // flag that we have map data so subsequent 'play' calls don't re-ask for it.
                vm.haveMapData = true;

                // autoload any server or map-level scripts
                autoloadScripts();
            }

            // translate node link to get valid urls for this server/site     
            data.node.MapNodeLinks = buildNodeUrls(data.node.MapNodeLinks);
            vm.node = data.node;

            // save state data to model
            vm.state = data.state;
            vm.state.state_data = JSON.parse(vm.state.state_data);

            // overlay state counter values on top of count objects
            applyStateToCounters();

            renderNodeContent(data.node);

            // register a onclick handler for all question elements in the node

            vm.nodeVue.node = data.node;
            document.title = data.node.title;

        } else {
            vm.nodeVue.content = "error";
        }
    }

    /**
     * Plays the map
     * @param {} node id
     * @returns {} 
     */
    function play(nodeId) {

        // test if no node passed in - get nodeId from Url parameters
        if (typeof (nodeId) !== 'undefined') {
            vm.urlParameters.nodeId = nodeId;
        }

        var url = vm.restApiUrl + '/play/' + vm.urlParameters.mapId + "/" + vm.urlParameters.nodeId;

        // if don't have map data yet, signal server to get and return it with request
        if (vm.haveMapData === false) {
            if (url.indexOf("?") === -1) {
                url += '?includeMapData=1';
            } else {
                url += '&includeMapData=1';
            }
        }

        vm.Utilities.getJson(url, vm.urlParameters, onNodeLoadSucceeded, onNodeLoadError);
    }

    /**
     * Spins up VUE js with the node markup
     * @returns {} 
     */
    function renderNodeContent(node) {

        var nodeHtml = encapsulateNodeMarkup('Node', node.text);

        // test if bypassing Wiki tag rendering
        if (vm.qs["showWiki"] !== "1")
            nodeHtml = dewikifyMarkup(nodeHtml);

        // compile the markup so Vue components resolve 
        var res = Vue.compile(nodeHtml);
        vm.nodeVue = createNodeVue('#olabNodeContent', res);

        if (node.header !== null) {
            nodeHtml = encapsulateNodeMarkup('Header', node.header);

            // test if bypassing Wiki tag rendering
            if (vm.qs["showWiki"] !== "1")
                nodeHtml = dewikifyMarkup(nodeHtml);

            // compile the markup so Vue components resolve 
            var res = Vue.compile(nodeHtml);
            vm.headerVue = createNodeVue('#olabHeaderContent', res);
        }

        if (node.footer !== null) {

            nodeHtml = encapsulateNodeMarkup('Footer', node.footer);

            // test if bypassing Wiki tag rendering
            if (vm.qs["showWiki"] !== "1")
                nodeHtml = dewikifyMarkup(nodeHtml);

            // compile the markup so Vue components resolve 
            var res = Vue.compile(nodeHtml);
            vm.footerVue = createNodeVue('#olabFooterContent', res);
        }

    }

    function createNodeVue(targetId, res) {

        // spin up Vue to load the compiled markup
        return new Vue({
            el:targetId,
            data:{
                websiteRoot:vm.websiteUrl,
                server:vm.server,
                map:vm.map,
                node:vm.node
            },
            render:res.render,
            staticRenderFns:res.staticRenderFns,
            methods:{
                constant:function(id) {

                    // if no id, return everything
                    if (id === null) {
                        var items = this.server.Constants;
                        items = items.concat(this.map.Constants);
                        items = items.concat(this.node.Constants);
                        return items;
                    }

                    var item = vm.Utilities.searchObjectArray(this.server.Constants, id);

                    if (item === null)
                        item = vm.Utilities.searchObjectArray(this.map.Constants, id);

                    if (item === null)
                        item = vm.Utilities.searchObjectArray(this.node.Constants, id);

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
                            var items = this.server.Files;
                            items = items.concat(this.map.Files);
                            items = items.concat(this.node.Files);
                            return items;
                        }

                        var item = vm.Utilities.searchObjectArray(this.server.Files, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.map.Files, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.node.Files, id);

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

                        var item = vm.Utilities.searchObjectArray(this.server.Questions, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.map.Questions, id);

                        if (item === null)
                            item = vm.Utilities.searchObjectArray(this.node.Questions, id);

                        if (item !== null) {
                            return item.item;
                        }

                        // if no id, return everything
                        if (id === null) {
                            var items = this.server.Questions;
                            items = items.concat(this.map.Questions);
                            items = items.concat(this.node.Questions);
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

                onDropdownResponseChanged:function(data) {

                    try {

                        vm.Utilities.log.debug('Xmit: ' + data.responseId + "= selected");
                        data.state_data = vm.state.state_data;
                        data.submitId = "#submit_" + data.questionId;
                        if (data.questionShowSubmit) {
                            jQuery( data.submitId ).show();
                        }

                        var url = vm.restApiUrl + '/question/dropdown/' + this.node.id;
                        vm.Utilities.postJson(url, data, onQuestionResponseSucceeded, onQuestionResponseFailed);

                    } catch (e) {
                        vm.Utilities.log.fatal('onDropdownResponseChanged: ' + e.message );
                    } 


                },

                // handler for question responses
                onMultichoiceResponseChanged:function(data) {

                    try {

                        vm.Utilities.log.debug('Xmit: ' + data.responseId + "=" + data.value);
                        data.state_data = vm.state.state_data;
                        data.value = data.value;
                        data.submitId = "#submit_" + data.questionId + "_" + data.responseId;
                        if (data.questionShowSubmit) {
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
                        if (data.questionShowSubmit) {
                            jQuery( data.submitId ).show();
                        }

                        var url = vm.restApiUrl + '/question/radio/' + this.node.id;
                        vm.Utilities.postJson(url, data, onQuestionResponseSucceeded, onQuestionResponseFailed);

                    } catch (e) {
                        vm.Utilities.log.fatal('onMultichoiceResponseChanged: ' + e.message );
                    } 
                    
                }
            }

        });
    }

};

/**
 * Document onloaded function
 */
jQuery(document).ready(function ($) {

  try {

    var params = {
      siteRoot: WEBSITE_ROOT,
      apiRoot: API_URL,
      targetId: 'olabNodeContent',
      location: document.location,
      authToken: JWT
    };

    // spin up class that does all the work
    olab = new OlabNodePlayer(params);
    olab.play();

  } catch (e) {
    alert(e.message);
  }

});