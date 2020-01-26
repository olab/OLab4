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
var olabPlayer = null;

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
    olabPlayer = new OlabNodePlayer(params);
    olabPlayer.play();

  } catch (e) {
    alert(e.name + ":" + e.message);
  }

});