var RSS_EDITING = false;

// XML RSS feed parsing wrapper
var RSSFeed = Class.create({
	channel_title: "",
	url: "",
	items:[],
	initialize: function(xml){
		this.items = [];
		// retrieve the channel and title of the RSS feed
		var channel = xml.getElementsByTagName('channel')[0];
		this.channel_title = channel.getElementsByTagName('title')[0].firstChild.nodeValue;
		// retrieve the items
		var items = channel.getElementsByTagName('item');
		if(items.length === 0) {
			//No items inside the channel, search the entire document
			items = xml.getElementsByTagName('item');
		}
		for (var i = 0; i < items.length; i++) {
	        // retrieve title, link, desc from the item
	        var title = items[i].getElementsByTagName('title')[0].firstChild.nodeValue;
	        var link = items[i].getElementsByTagName('link')[0].firstChild.nodeValue;
	        var description = items[i].getElementsByTagName('description')[0].firstChild.nodeValue;

			this.items.push({title: title, link: link, description:description});
		}
	}
});

var RSSReader = Class.create({});

Object.extend(RSSReader, {
	// Uses the cross domain proxy to retrieve the feed xml and then creates an RSSFeed object from it,
	// and passes that to a callback.
	getFeed: function(url, callback, error_callback) {
		if(Object.isUndefined(CROSS_DOMAIN_PROXY_URL)) { return false; }

		var request_url = CROSS_DOMAIN_PROXY_URL + "?" + Object.toQueryString({url: url});
		var ajax = new Ajax.Request(request_url, {
			method: 'get',
			onSuccess: function(response) {
				// Below is the client side javascript parsing. If the cross domain proxy returns XML, this should be used.
				// var feed = new RSSFeed(response.responseXML);
				// Instead, our cross domain proxy returns a JSON object looking like the above RSSFeed wrapper, but parsed on the server side.
				var feed = response.responseJSON;
				feed.url = url;
				if (feed.items.length === 0) {
					//Invalid RSS Feed
					error_callback(response);
				} else {
					callback(feed);
				}
			},
			onFailure: function(response) {
				error_callback(response);
			}
		});
		return true;
	}
});

Element.addMethods({
	// Make an element show some feed items from an RSS feed, the url for which is either provided
	// in the options or as an attribute, 'data-feedurl' on the element itsself.
	readRSS: function(element, provided_options) {
		var options = $H({
			max_items: 5,
			url: false,
			spinner: false,
			spinner_url: SPINNER_URL,
			onComplete: Prototype.emptyFunction,
			onError: Prototype.emptyFunction
		}).merge(provided_options);

		// Allow feed url to be passed in as an attribute on the element or as an argument to the function
		if(!Object.isString(options.get('url'))) {
			if(element.hasAttribute('data-feedurl')) {
				options.set('url', element.readAttribute('data-feedurl'));
			} else {
				return false;
			}
		}

		if(options.get('spinner')) {
			if(Object.isString(options.get('spinner_url'))) {
				element.update(new Element('img', {src: options.get('spinner_url')}));
			}
		}
		RSSReader.getFeed(options.get('url'), options.get('onComplete').curry(element), options.get('onError').curry(element));
		return true;
	}
});

var EntradaRSS = Class.create();
Object.extend(EntradaRSS, {
	// Array of the feed columns, each becomes its own sortable
	feedLists: ['rss-list-1', 'rss-list-2'],
	//Renders the feed as HTML inside the passed element
	feedToHTML: function(element, feed) {
		count = Math.min(feed.items.length, 5);
		$(element).update('');
		for(var i = 0; i < count; i++) {
			var item = feed.items[i];
			var new_element = new Element('div', {'class': "rss-item-title"}).update(new Element('a', {href: item.link, title: item.title, target: "_blank"}).update(item.title));
			$(element).insert({bottom: new_element});
		}
	},
	feedErrorHTML: function(element, response) {
		element.update("There was an error reading the feed at <a href=\""+response.responseJSON.url+"\">"+response.responseJSON.url+"</a>, please try again.");
		if(!Object.isUndefined(console)) {
			console.log(response);
		}
	},
	// Removes the HTML representing a feed from the list. Called by the remove links.
	removeRSSFeed: function(event) {
		$(event.target).up().remove();
		Event.stop(event);
		EntradaRSS.saveRSSFeeds.defer();
	},
	destroySortables: function() {
		this.feedLists.each(function(list, index) {
			Sortable.destroy(list);
		});
	},
	createSortables: function() {
		var options = {
			containment: this.feedLists,
			dropOnEmpty: true,
			onUpdate: function(container) {
				EntradaRSS.saveRSSFeeds.defer();
				return true;
			}
		};

		this.feedLists.each(function(list, index) {
			Sortable.create(list, options);
		});
	},
	resetSortables: function() {
		this.destroySortables();
		this.createSortables();
	},
	saveRSSFeeds: function(e) {

		var feeds = [];
		EntradaRSS.feedLists.each(function(list, index) {
			feeds = feeds.concat(Sortable.sequence(list));
		});

		// Integer index of the first element in the second column
		var col2_start = Sortable.sequence(EntradaRSS.feedLists[0]).length;

		var params = feeds.inject({title: [], url: []}, function(acc, id) {
			acc.title.push($('anonymous_element_'+id).down('h2').innerHTML);
            acc.url.push($('anonymous_element_'+id).down('div.rss-content').readAttribute('data-feedurl'));

            return acc;
		});

		params["break"] = col2_start;
		params = Object.toQueryString(params).gsub('title', 'title[]').gsub('url', 'url[]');

		var ajax = new Ajax.Request(DASHBOARD_API_URL+"?action=save", {
			parameters: params,
			onCreate: function() {
				$("rss-save-results").setStyle({display: "inline", opacity: 1}).update(new Element('img', {src: SPINNER_URL, width: 16, height: 16}));
			},
			onSuccess: function() {
				$($("rss-save-results").setStyle({display: "inline", opacity: 1}).update(new Element('img', {src: SUCCESS_IMAGE_URL, width: 16, height: 16}))).fade();
			},
			onFailure: function() {
				$("rss-save-results").setStyle({display: "inline", opacity: 1}).update(new Element('img', {src: ERROR_IMAGE_URL, width: 16, height: 16})).fade();
			}
		});
	},
	resetRSSFeeds: function(e) {
		var ajax = new Ajax.Request(DASHBOARD_API_URL+"?action=reset", {
			onCreate: function() {
				$("rss-save-results").update(new Element('img', {src: SPINNER_URL, width: 16, height: 16})).show();
			},
			onSuccess: function() {
				$($("rss-save-results").update(new Element('img', {src: SUCCESS_IMAGE_URL, width: 16, height: 16}))).fade();
				window.location.reload();
			},
			onFailure: function() {
				$("rss-save-results").update(new Element('img', {src: ERROR_IMAGE_URL, width: 16, height: 16})).show();
			}
		});
	}
});

document.observe("dom:loaded", function() {

	$$('.rss-list li').invoke('identify');

	$$('.rss-content').invoke('readRSS', {
		spinner: true,
		onComplete: EntradaRSS.feedToHTML,
		onError: EntradaRSS.feedErrorHTML
	});

	$$('.rss-remove-link').invoke('observe', 'click', EntradaRSS.removeRSSFeed);

	//Modal Window
	var add_rss_modal = new Control.Modal($('rss-add-details'),{
	    overlayOpacity: 0.75,
	    className: 'modal',
	    fade: true,
		fadeDuration: 0.30
	});

	function addRSSFeed(e) {
		add_rss_modal.open();
	}

	function CancelAddRSSFeed(e) {
		$('rss-add-status').update("");

		add_rss_modal.close();
	}

	$('rss-add-form').observe('submit', function(e) {
		Event.stop(e);
		var url = $F('rss-add-url');
		var title = $F('rss-add-title');
		if(url.match(/https?:\/\/([\-\w\.]+)+(:\d+)?(\/([\w\/_\.]*(\?\S+)?)?)?/i)) {
			if(title.match(/^[A-Za-z0-9\s]*$/)) {
				$('rss-add-status').update(new Element('img', {src: SPINNER_URL}));
				var div = new Element('div', {'class': "rss-content"}).writeAttribute('data-feedurl', url);

				div.readRSS({
					spinner: true,
					onComplete: function(element, feed) {
						$('rss-add-status').update("");
						$('rss-add-url').value = 'http://';
						$('rss-add-title').value = '';

						var li = new Element('li');
						li.identify(); // Give the element a unique, autogenerated ID
						$('rss-list-1').insert({
							bottom: li
						});

						li.update(
							new Element('h2', {'class': "rss-title"}).update(title)
							).insert({
								bottom: element
							});

						element.insert({
							after: new Element('a', {'class': "rss-remove-link", href: "#"}).observe('click', EntradaRSS.removeRSSFeed).update("Remove this Feed")
						});

						EntradaRSS.feedToHTML(element, feed);
						EntradaRSS.resetSortables();
						EntradaRSS.saveRSSFeeds();
						if(!RSS_EDITING) {
							EntradaRSS.destroySortables();
						}

						add_rss_modal.close();
					},
					onError: function(element, feed) {
						$('rss-add-status').update("<div class=\"display-error\">There was an error adding this RSS feed. Please ensure that the feed URL is a valid RSS/Atom feed.</div>");
					}
				});
			} else {
				$('rss-add-status').update("<div class=\"display-error\">There was an error adding this RSS feed. Please ensure that the feed title is present and contains no special characters.</div>");
			}
		} else {
			$('rss-add-status').update("<div class=\"display-error\">There was an error adding this RSS feed. Please ensure that the feed URL is a valid RSS/Atom feed.</div>");
		}
	});

	function editRSSFeed(e) {
		$('rss-edit-details').toggle();
		$('dashboard-syndicated-content').toggleClassName('editing');

		if(RSS_EDITING) {
			//destroy sortables
			EntradaRSS.destroySortables();
			$('edit-rss-feeds-link').update("Modify RSS Feeds");
		} else {
			//create sortables
			EntradaRSS.createSortables();
			$('edit-rss-feeds-link').update("Stop Modifying RSS Feeds");
		}

		RSS_EDITING = !RSS_EDITING;
		//Event.stop(e); - dont stop the event so the link anchors work properly
	}


	$('edit-rss-feeds-link').observe('click', editRSSFeed);
	$('add-rss-feeds-link').observe('click', addRSSFeed);
	$('add-rss-feeds-close-link').observe('click', CancelAddRSSFeed);
	$('rss-feed-reset').observe('click', EntradaRSS.resetRSSFeeds);
});