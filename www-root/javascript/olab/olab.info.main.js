/**
 * Main olab player class for info windows
 * @param {} authToken = current auth token
 * @param {} targetId = main content view div name for data binding
 * @param {} websiteRootUrl = root url for web site
 * @param {} pageUrl = current page url (document.location)
 * @returns {} Service definition
 */

"use strict";
// main view class
var olab = null;

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
    olab.info();

  } catch (e) {
    alert(e.name + ":" + e.message);
  }

});