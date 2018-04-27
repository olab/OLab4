function renderDOM(jsonResponse, link) {
	if (jsonResponse.child_objectives.length > 0) {
		var new_list = jQuery(document.createElement("ul"));
		
		if (jsonResponse.objective_parent != jQuery("#objective-breadcrumb .objective-link").first().attr("data-id")) {
			var back = jQuery(document.createElement("li"));
			back.append("<a href=\"#\" data-id=\""+jsonResponse.objective_parent+"\" class=\"back objective-link\"><span style=\"margin-top:0px;\"><i style=\"margin-top:0px;\" class=\"icon-chevron-left\"></i></span>Back</a>")
			new_list.append(back);
		}
		for (var i=0; i < jsonResponse.child_objectives.length; i++) {
			var new_list_item = jQuery(document.createElement("li"));
			var count;

			if (jsonResponse.child_objectives[i].course_count || jsonResponse.child_objectives[i].event_count || jsonResponse.child_objectives[i].assessment_count) {
				count = (parseInt(jsonResponse.child_objectives[i].course_count)) + parseInt(jsonResponse.child_objectives[i].event_count) + parseInt(jsonResponse.child_objectives[i].assessment_count);
			} else {
				count = parseInt((jsonResponse.courses != null && jsonResponse.courses.length > 0 ? jsonResponse.courses.length : 0)) + parseInt((jsonResponse.events != null ? jsonResponse.events.length : 0));
			}
			var percent = count / current_total;
			var color = "badge";
			
			new_list_item.append(
				jQuery(document.createElement("a"))
					.addClass("objective-link")
					.attr("href", SITE_URL + "/curriculum/explorer?objective_parent="+jsonResponse.child_objectives[i].objective_parent + "&id=" + jsonResponse.child_objectives[i].objective_id + "&step=2")
					.attr("data-id", jsonResponse.child_objectives[i].objective_id)
					.html("<span class=\"" + color + "\">" + count + "</span>" + jsonResponse.child_objectives[i].objective_name))
			new_list.append(new_list_item);
		}
		jQuery("#objective-list").html(new_list);
	}

	var courses = false;
	var events = false;
    var assessments = false;
	jQuery("#objective-details").html("");
	jQuery("#objective-details").append("<h1>"+link.html()+"</h1>");
	if (jsonResponse.objective_description != null && jsonResponse.objective_description.length > 0) {
		jQuery("#objective-details").append("<p>"+jsonResponse.objective_description+"</p>");
	}
    
    var tabbable = jQuery(document.createElement("div")).addClass("tabbable");
    if ((jsonResponse.courses != null && jsonResponse.courses.length > 0) || jsonResponse.events != null || jsonResponse.assessments != null) {
        var nav_list = jQuery(document.createElement("ul"))
        
        var nav_tabs = new Array;
        if (jsonResponse.courses != null && jsonResponse.courses.length > 0) {
            nav_tabs.push(jQuery(document.createElement("li")).html("<a href=\"#courses-tab\" data-toggle=\"tab\">Courses</a>"));
        }
        if (jsonResponse.events != null) {
            nav_tabs.push(jQuery(document.createElement("li")).html("<a href=\"#events-tab\" data-toggle=\"tab\">Events</a>"));
        }
        if (jsonResponse.assessments != null) {
            nav_tabs.push(jQuery(document.createElement("li")).html("<a href=\"#assessments-tab\" data-toggle=\"tab\">Assessments</a>"));
        }
        
        jQuery(nav_tabs).each(function(i, v) {
            if (i == 0) {
                v.addClass("active");
            }
            nav_list.append(v);
        });
        
        nav_list.addClass("nav nav-tabs");
        tabbable.append(nav_list);
    }
    
    var tab_content_container = jQuery(document.createElement("div"));
    tab_content_container.addClass("tab-content");
    var tab_panes = new Array;
	if (jsonResponse.courses != null && jsonResponse.courses.length > 0) {
        if (jsonResponse.courses.length > 0) {
            var course_pane = jQuery(document.createElement("div")).addClass("tab-pane").attr("id", "courses-tab");
            for (var i=0; i < jsonResponse.courses.length; i++) {
                var new_course = jQuery(document.createElement("div"));
                new_course.addClass("course-container").attr("data-id", jsonResponse.courses[i].course_id);
                new_course.append(
                    jQuery(document.createElement("p")).append(
                        jQuery(document.createElement("a"))
                                .attr("href", SITE_URL+"/courses?id="+jsonResponse.courses[i].course_id)
                                .html("<strong>"+jsonResponse.courses[i].course_code+":</strong> " + jsonResponse.courses[i].course_name)
                    )
                );
                course_pane.append(new_course);
            }
            tab_panes.push(course_pane);
            courses = true;
        }
	}

	if (jsonResponse.events != null) {
        var event_pane      = jQuery(document.createElement("div")).addClass("tab-pane").attr("id", "events-tab");
		for (var v in jsonResponse.events) {
			var course_container = jQuery(document.createElement("div")).addClass("course-container");
			var new_course = jQuery(document.createElement("h4"));
			new_course.html(v);
			course_container.append(new_course);
			for (var i=0; i < jsonResponse.events[v].length; i++) {
				var event_date = new Date(jsonResponse.events[v][i].event_start * 1000);
				var new_event = jQuery(document.createElement("div"));
				new_event.addClass("event-container").attr("data-id", jsonResponse.events[v][i].event_id);
				new_event.append(
					jQuery(document.createElement("p")).append(
						jQuery(document.createElement("a"))
								.attr("href", SITE_URL+"/events?rid="+jsonResponse.events[v][i].event_id)
								.html(jsonResponse.events[v][i].event_title)
					).append("<br /><span class=\"content-small\">Event on " + event_date.toDateString() + "</span>")
				);
				course_container.append(new_event);
				delete(event_date);
			}
			event_pane.append(course_container);
		}
        tab_panes.push(event_pane);
		events = true;
	}
    
    if (jsonResponse.assessments != null) {
        var assessment_pane = jQuery(document.createElement("div")).addClass("tab-pane").attr("id", "assessments-tab");
        for (var key in jsonResponse.assessments) {
            var new_course = jQuery(document.createElement("h4"));
			new_course.html(key);
            assessment_pane.append(new_course);
            jQuery(jsonResponse.assessments[key]).each(function(i, v) {
                var event_date = new Date(v.event_start * 1000);
                var assessment = jQuery(document.createElement("p"));
				var assessment_link_event = jQuery(document.createElement("a"));
				assessment_link_event.html("Associated Learning Event on " + event_date.toDateString()).attr({"href": SITE_URL+"/events?rid=" + v.event_id, "target": "_blank"});
				if (v.permission) {
					var assessment_link = jQuery(document.createElement("a"));
					assessment_link.html(v.name).attr({"href": SITE_URL+"/admin/gradebook/assessments?section=grade&id=" + v.course_id + "&assessment_id=" + v.assessment_id, "target": "_blank"});
					assessment.append(assessment_link);
				} else {
					var assessment_link_span = jQuery(document.createElement("span"));
					assessment_link_span.html(v.name);
					assessment.append(assessment_link_span);
				}
				assessment.append("<br/>").append(assessment_link_event);
                assessment_pane.append(assessment);
            });
        }
        tab_panes.push(assessment_pane);
		var assessments = true;
    }
    
    jQuery(tab_panes).each(function(i, v) {
        if (i == 0) {
            v.addClass("active");
        }
        tab_content_container.append(v);
    });
    tabbable.append(tab_content_container);
    
    jQuery("#objective-details").append(tabbable);
    
    
	if (courses == false && events == false && assessments == false) {
		if (jsonResponse.child_objectives.length > 0) {
			jQuery("#objective-details").append("<div class=\"display-generic\">Please select an objective from the menu on the left.</div>");
		} else {
			jQuery("#objective-details").append("<div class=\"display-generic\">There are no objectives or events at this level.</div>");
		}
	}

	if (typeof jsonResponse.breadcrumb != "undefined") {
		jQuery("#objective-breadcrumb").html(jsonResponse.breadcrumb);
	}
}
jQuery(function(){
	jQuery(document).on("click", ".objective-link", function(e){
		if (jQuery(this).hasClass("back")) {
			jQuery("#objective-breadcrumb .objective-link").last().click();
		} else {
			jQuery("#objective-list .objective-link.active").removeClass("active");
			jQuery(this).addClass("active");
			var link = jQuery(this).clone();
			link.children("span").remove();
			jQuery("#objective-details").html("<h1>"+link.html()+"</h1>" + "<div class=\"throbber\">Loading...</div>");
			jQuery.ajax({
                type: "GET",
				url: SITE_URL + "/curriculum/explorer",
                data: {mode: "ajax", objective_parent : jQuery(this).attr("data-id"), year : YEAR, course_id: COURSE, count: COUNT, group_id: COHORT},
				success: function(data) {
                    var jsonResponse = JSON.parse(data);
					current_total = 0;
					jQuery.each(jsonResponse.child_objectives, function (i, v) {
						current_total = current_total + v.event_count + v.course_count + v.assessment_count;
					});
					renderDOM(jsonResponse, link);
				}
			});
			location.hash = "id-" + jQuery(this).attr("data-id");
		}
		e.preventDefault();
	});
    
    jQuery("#course").on("change", function() {
        /*var COURSE_ID = jQuery(this).val();
        jQuery(".course-specific-objectiveset").remove();
        jQuery.ajax({
				url: SITE_URL + "/curriculum/explorer?mode=ajax&method=course_specific_objective_sets&course_id=" + COURSE_ID,
				success: function(data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        jQuery.each(jsonResponse.data, function( i, v ) {
                            var option = jQuery(document.createElement("option"));
                            option.val(v.objective_id).html(v.objective_name).addClass("course-specific-objectiveset");
                            jQuery("#objective-set").append(option);
                        });
                    }
                }
        });*/
    });
});
