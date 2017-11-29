﻿var OLabUtilities = function(siteRoot, pageUrl, authToken) {

  var vm = this;

  vm.authHeader = null;
  vm.imageUrlBase = "/images/olab";
  vm.fileUrlBase = "/core/storage/olab-files";
  vm.apiRootBase = "/api/v2/olab/file/";

  vm.siteRoot = siteRoot;
  vm.tagNamespace = {};

  setAuthToken(authToken);

  // define logger object (write to dev tools console for now)
  vm.logger = {

    debug:function(arg) {
      console.log("[Olab DEBUG]: " + arg);
    },
    error:function(arg) {
      console.log("[Olab ERROR]: " + arg);
    },
    warning: function (arg) {
      console.log("[Olab WARN]: " + arg);
    }

  };

  // this adds support for binary file transfers via ajax
  jQuery.ajaxTransport("+binary",
    function(options, originalOptions, jqXHR) {
      // check for conditions and support for blob / arraybuffer response type
      if (window.FormData &&
      ((options.dataType && (options.dataType == 'binary')) ||
      (options.data &&
      ((window.ArrayBuffer && options.data instanceof ArrayBuffer) ||
        (window.Blob && options.data instanceof Blob))))) {
        return {
          // create new XMLHttpRequest
          send:function(headers, callback) {
            // setup all variables
            var xhr = new XMLHttpRequest(),
              url = options.url,
              type = options.type,
              async = options.async || true,
              // blob or arraybuffer. Default is blob
              dataType = options.responseType || "blob",
              data = options.data || null,
              username = options.username || null,
              password = options.password || null;
            xhr.addEventListener('load',
              function() {
                var data = {};
                data[options.dataType] = xhr.response;
                // make callback and send data
                callback(xhr.status, xhr.statusText, data, xhr.getAllResponseHeaders());
              });

            xhr.open(type, url, async, username, password);

            // setup custom headers
            for (var i in headers) {
              xhr.setRequestHeader(i, headers[i]);
            }

            xhr.responseType = dataType;
            xhr.send(data);
          },
          abort:function() {
            jqXHR.abort();
          }
        };
      }
    });

  // these are the methods/properties we expose to the outside
  var service = {
    convertQSToArray:convertQsToArray,
    convertToAssociativeArray:convertToAssociativeArray,
    downloadFile:downloadFile,
    getAuthHeader:getAuthHeader,
    getAuthToken:getAuthToken,
    getJson:getJson,
    getPreference:getPreference,
    getWikiTags:getWikiTags,
    getWikiTagParts:getWikiTagParts,
    getTokenQs:getTokenQs,
    getUrlParameters:getUrlParameters,
    imageUrlBase: vm.imageUrlBase,
    log: vm.logger,
    normalizeDivId: normalizeDivId,
    postJson:postJson,
    searchObjectArray:searchObjectArray,
    setAuthHeader:setAuthHeader,
    setAuthToken:setAuthToken,
    setPreference:setPreference,
    testServerError:testServerError
  };

  return service;

  /**
   * Trims named character from start/end of string
   * @param {} s 
   * @param {} c 
   * @returns {} 
   */
  function characterTrim(s, c) {
    if (c === "]") c = "\\]";
    if (c === "\\") c = "\\\\";
    return s.replace(new RegExp(
        "^[" + c + "]+|[" + c + "]+$",
        "g"
      ),
      "");
  }

  /*
   * Convert array with 'id' into associative array
   */
  function convertToAssociativeArray(source) {

    var target = [];
    if (typeof source != 'undefined') {
      jQuery.each(source,
        function(index, item) {
          target[item.id] = item;
        });
    }

    return target;
  }

  function convertUrlHashToArray(hash) {
    hash = hash.replace("#", "");
    return hash.split(":");
  }

  /*
   * Convert query string urlParameters into array
   * (pass in a document.location.search)
   */
  function convertQsToArray(urlSearch) {

    var queries = {};
    var pos = urlSearch.indexOf('?');

    // test if any querystring params passed in
    if (pos !== -1) {

      urlSearch = urlSearch.substr(pos);

      jQuery.each(urlSearch.substr(1).split('&'),
        function(c, q) {
          var i = q.split('=');
          queries[i[0].toString()] = i[1].toString();
        });

    }

    return queries;

  }

  /*
   * Get current auth header
   */
  function getAuthToken() {
    return vm.token;
  }

  /*
   * Get current auth header
   */
  function getAuthHeader() {
    return vm.authHeader;
  }

  function getDownloadFileName(xhr) {

    var dispositionHeader = getHttpHeader(xhr, "Content-Disposition");
    var headerParts = dispositionHeader.split(";");
    for (var i = 0; i < headerParts.length; i++) {
      var header = headerParts[i].split("=");
      if (header[0].trim() === "filename") {
        return header[1];
      }
    }

    return null;

  }

  function getHttpHeader(request, key) {
    var value = request.getResponseHeader(key);
    return value;
  }

  function downloadFile(id, onError) {

    try {
      var url = vm.siteRoot + vm.apiRootBase + id;

      jQuery.ajax({
        url:url,
        type:'GET',
        dataType:'binary',
        error:function(data, textStatus, request) {
          alert(textStatus + ' ' + request);
        },
        beforeSend:function(xhr) {
          if (vm.authHeader.length > 0) {
            setHttpHeader(xhr, 'Authorization', getAuthHeader());
          }
        },
        processData:false,
        success:function(data, textStatus, xhr) {

          var fileName = getDownloadFileName(xhr);
          var windowUrl = window.URL || window.webkitURL;
          var url = windowUrl.createObjectURL(data);
          var anchor = jQuery("#file-link-" + id);
          anchor.prop('href', url);
          anchor.prop('download', fileName);
          anchor.get(0).click();
          windowUrl.revokeObjectURL(url);
        }
      });

    } catch (e) {
      alert("getFile error: " + e.message);
    }
  }

  /*
   * Centralized method to get JSON data. 
   */
  function getJson(url, data, onSuccess, onError) {

    var options = {
      url:url,
      type:'GET',
      dataType:'json',
      success:function(data, textStatus, request) {

        if ( testServerError(data))
          return;

        if (onSuccess != null) {
          onSuccess(data);
        }

      },

      error:function(data, textStatus, request) {

        if (onError != null) {
          onError(textStatus + ' ' + request);
        }

      },

      beforeSend:function(xhr) {

        if (vm.authHeader.length > 0) {
          setHttpHeader(xhr, 'Authorization', getAuthHeader());
        }
      },

      complete:function(xhr, status) {
        var headerValue = xhr.getResponseHeader("Authorization");
        if (headerValue != null) {
          setAuthHeader(headerValue);
        }

      }

    };

    if (data != null)
      options.data = data;

    var jqxhr = jQuery.ajax( options );
  }

  /**
   * Gets a setting from the cookies
   * @param {} cname 
   * @param {} defaultValue 
   * @returns {} 
   */
  function getPreference(cname, defaultValue) {

    cname = "olab-" + cname;

    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');

    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) === ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) === 0) {
        return c.substring(name.length, c.length);
      }
    }
    return defaultValue;
  }

  /*
   * Get current auth header
   */
  function getTokenQs() {
    if (vm.token != null)
      return 'token=' + vm.token;
    return '';
  }

  function getUrlParameters(args) {
    return convertUrlHashToArray(args);
  }

  function getWikiTags(source) {

    var regex = /\[\[(.*?)\]\]/g;
    var matches = [];
    source.replace(regex,
      function(m, remaining, index) {
        matches.push(remaining);
      });
    return matches;
  }

  function getWikiTagParts(source) {

    var regex = /(:|,)/g;
    var matches = source.split(regex);

    // do some cleanup on the parts
    for (var i = 0; i < matches.length; i++) {
      matches[i] = matches[i].trim();
      matches[i] = characterTrim(matches[i], '"');
    }

    return matches;
  }

  /*
   * Helper class to normallize html ids to jquery selectors
   */
  function normalizeDivId(divId) {

    if (divId.indexOf('#') !== 0)
      return '#' + divId;

    return divId;
  }

  /*
   * Centralized method to post/recieve JSON data. 
   */
  function postJson(url, data, onSuccess, onError) {

    var options = {
      url: url,
      type: 'POST',
      data: data ,
      dataType: 'json',
      success: function (data, textStatus, request) {

        if (testServerError(data))
          return;

        if (onSuccess != null) {
          onSuccess(data);
        }

      },

      error: function (data, textStatus, request) {

        if (onError != null) {
          onError(textStatus + ' ' + request);
        }

      },

      beforeSend: function (xhr) {

        if (vm.authHeader.length > 0) {
          setHttpHeader(xhr, 'Authorization', getAuthHeader());
        }
      },

      complete: function (xhr, status) {
        var headerValue = xhr.getResponseHeader("Authorization");
        if (headerValue != null) {
          setAuthHeader(headerValue);
        }

      }

    };

    var jqxhr = jQuery.ajax(options);
  }

  /**
   * Searches for a id or a name in a system object list
   * @param {} source Source array
   * @param {} id Id to look for
   * @returns {} 
   */
  function searchObjectArray(source, id) {

    // do dumb search for record based on the id or name
    var idNumber = parseInt(id);

    // if ID is not a number, then search assuming string 
    if (isNaN(idNumber)) {

      for (var i = 0; i < source.length; i++) {
        if (source[i].name === id) {
          return {index:i, item:source[i]};
        }
      }

    } else {

      for (var i = 0; i < source.length; i++) {
        if (source[i].id === idNumber) {
          return {index:i, item:source[i]};
        }
      }

    }

    return null;
  }

  function setHttpHeader(xhr, key, value) {
    if (value)
      xhr.setRequestHeader(key, value);
  }

  function setAuthHeader(header) {
    vm.authHeader = header;
  }

  function setAuthToken(token) {
    vm.token = token;
    vm.authHeader = 'Bearer ' + vm.token;
  }

  /**
   * Sets a setting to the cookie
   * @param {} name
   * @param {} value 
   * @returns {} 
   */
  function setPreference(name, value) {

    name = "olab-" + name;

    var d = new Date();
    d.setTime(d.getTime() + (1 * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
  }

  /**
   * Handle javascript exception
   * @param {} e 
   * @returns {} 
   */
  function testJavascriptError(e) {
    alert(e);
  }

  /**
   * Test if server error is in ajax payload
   * @param {} data 
   * @returns {} true/false
   */
  function testServerError(data) {

    if (typeof data === "undefined ") {
      return false;
    }

    if (typeof data.error === "undefined") {
      return false;
    }

    vm.logger.error(data.error);
    alert(data.error);
    return true;

  }
}