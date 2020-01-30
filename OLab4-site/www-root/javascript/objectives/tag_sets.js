var columns = {
    c: "code",
    t: "title",
    d: "description"
};

function createMaxLevelControl(level) {
    var div = jQuery(document.createElement("div")).attr({id: "level-" + level, class: "space-above", style: "margin-left: " + (level*15) + "px"});
    var level_label = jQuery(document.createElement("label")).addClass("control-label");
    var level_input = jQuery(document.createElement("input"));
    var controls = jQuery(document.createElement("div")).addClass("controls");

    jQuery(level_label).html("<i class=\"fa fa-share fa-flip-vertical\"></i> Level " + level + " Label");
    jQuery(level_input).val((existing_labels ? levels_label[level-1] : ""));

    jQuery(level_input).attr({name: "level[]", id: "level_" + level, type: "text"});
    jQuery("#level-labels").append(div.append(level_label).append(controls.append(level_input)));
}

function deleteMaxLevelControl(level) {
    var div = jQuery("#level-" + level);
    jQuery(div).remove();
}

function validateShortname(shortname) {
    if((shortname) && (shortname != "")) {
        shortname = shortname.replace(/\W/g, "");
        shortname = shortname.toLowerCase();
    } else {
        shortname	= "";
    }

    if(shortname.length > 20) {
        shortname	= shortname.truncate(20, "");
    }

    jQuery("#objective_shortname").val(shortname);

    return;
}

function buildAdvancedSearchList(search_btn) {
    jQuery("input[name=\"" + search_btn.data("settings").target_name + "[]\"]").each(function () {
        var input = jQuery(this);
        var filter = input.attr("id").split("_")[0];
        var filter_name = filter.split("-").join(" ");
        var list_item = "<li class=\"" + filter + "_target_item " + filter + "_" + input.data("id") + "\" data-id=\"" + input.data("id") + '"><span class="selected-list-container"><span class="selected-list-item">' + filter_name.replace(/\b\w/g, function (l) {
                return l.toUpperCase()
            }) + "</span><span class=\"remove-selected-list-item remove-target-toggle\" data-id=\"" + input.data("id") + "\" data-filter=\"" + filter + "\">×</span></span>" + input.data("label") + "</li>";
        if (jQuery("#" + filter + "_list_container").length > 0) {
            jQuery("#" + filter + "_list_container").append(list_item);
        } else {
            search_btn.after("<ul id=\"" + filter + "_list_container\" class=\"selected-items-list\">" + list_item + "</ul>")
        }
    });
}

jQuery(document).ready(function() {
    buildShortMethod();
    buildLongMethod();
    jQuery("input[name='objective_audience']").on("change", function() {
        var value = jQuery("input[name='objective_audience']:checked").val();
        if (value == "selected") {
            jQuery("#choose-course-btn").removeClass("hide");
        } else {
            jQuery("#choose-course-btn").addClass("hide");
            jQuery(".course_search_target_control").remove();
            jQuery("#course_list_container").remove();
        }
    });

    jQuery("input[name*='languages[]']").on("click", function(e) {
        var languages = jQuery("input[name*='languages[]']:checked").length;
        if (languages == 0) {
            this.checked = true;
        }
    });

    jQuery("input[name='objective_audience']").trigger("change");

    jQuery("#max_level").on("keyup change", function (e) {
        var max = jQuery(this).attr("maxlength");
        if (jQuery(this).val().length > 1) {
            var level = parseInt(jQuery(this).val());
        } else {
            var level = jQuery(this).val();
        }
        if (level > max) {
            jQuery("#max_level").val(max);
            level = max;
        }
        if (old_level < level) {
            for (var i = +old_level + 1; i <= level; i++) {
                createMaxLevelControl(i);
            }
        } else if (old_level > level) {
            for (var i = +level + 1; i <= old_level; i++) {
                deleteMaxLevelControl(i);
            }
        }
        old_level = (level > 0 ? level : 0);
    });

    jQuery("#max_level").trigger("change");

    // Short and Long display method functions

    jQuery("input[name*='short_method[]']").on("click", buildShortMethod);

    jQuery("#short_method_input").on("keyup change", function (e) {
        var text = jQuery(this).val();
        jQuery("input[name*='short_method[]']").each(function() {
            if (text.indexOf(jQuery(this).val()) != -1) {
                if (typeof  jQuery("input[name='requirements[]'][value='" + columns[jQuery(this).val().replace("%", "")] + "']").attr("checked") !== "undefined") {
                    jQuery(this).attr("checked", "checked");
                } else {
                    jQuery("#short_method_input").val(text.replace(jQuery(this).val(), ""))
                }
            } else {
                jQuery(this).removeAttr("checked");
            }
        });
        jQuery("#short_method_preview").html(jQuery("#short_method_input").val().replace("%t", "Title Ipsum").replace("%c", "Code Ipsum").replace("%d", "Description Ipsum"));
        e.preventDefault();
    });

    jQuery("input[name*='long_method[]']").on("click", buildLongMethod);

    function buildLongMethod(e) {
        var text = "";
        var code = "";
        var title = "";
        var description = "";
        jQuery("#long_method_input").empty();
        jQuery("input[name*='long_method[]']:checked").each(function() {
            value = jQuery(this).val();

            switch (value) {
                case "%c":
                    code = "<h4 class=\"tag-code\">" + value + "</h4>";
                    break;
                case "%t":
                    if (code) {
                        title = "<h4 class=\"tag-title\">" + value + " <small class=\"tag-code\">%c</small></h4>";
                        code = "";
                    } else {
                        title = "<h4 class=\"tag-title\">" + value + "</h4>";
                    }
                    break;
                case "%d":
                    description = "<p class=\"tag-description\">" + value + "</p>";
                    break;
            }
        });
        text = code + title + description;
        jQuery("#long_method_input").val(text);
        jQuery("#long_method_preview").html(text.replace("%t", "Title Ipsum").replace("%c", "Code Ipsum").replace("%d", "Description ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."));
    }

    function buildShortMethod(e) {
        var text = [];
        jQuery("#short_method_input").empty();
        jQuery("input[name*='short_method[]']:checked").each(function() {
            text.push(jQuery(this).val());
        });

        jQuery("#short_method_input").val(text.join(" "));
        jQuery("#short_method_preview").html(jQuery("#short_method_input").val().replace("%t", "Title Ipsum").replace("%c", "Code Ipsum").replace("%d", "Description ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."));
    }

    jQuery("input[name*='required[]']").on("click", function(e) {
        var required_num = jQuery("input[name*='required[]']:checked").length;
        if (required_num == 0) {
            this.checked = true;
        }
    });

    // END - Short and Long display method functions

    jQuery("input[name*='requirements[]']").on("click", function(e) {
        var values_num = jQuery("input[name*='requirements[]']:checked").length;
        if (values_num <= 1) {
            last_input = jQuery("#req_" + jQuery("input[name*='requirements[]']:checked").val());
            last_input.find("input").attr("checked", "checked");
            if (values_num == 0) {
                this.checked = true;
            }
            jQuery("." + last_input.find("input").val()).show();
            jQuery("." + last_input.find("input").val() + " input").attr("checked", "checked");
            jQuery("#req_" + last_input.find("input").val()).show();
        }
        if (this.checked) {
            jQuery(this).attr("checked", "checked");
            jQuery("." + this.value).show();
            jQuery("." + this.value + " input").attr("checked", "checked");
            jQuery("#req_" + this.value).show();
        } else {
            jQuery(this).removeAttr("checked");
            jQuery("." + this.value).hide();
            jQuery("." + this.value + " input").removeAttr("checked");
            jQuery("#" + this.value + "_checkbox").removeAttr("checked");
            jQuery("#req_" + this.value).hide();
        }
        buildShortMethod();
        buildLongMethod();
    });

    jQuery("input[name*='requirements[]']").each(function () {
        if (this.checked) {
            jQuery("." + this.value).show();
            jQuery("#req_" + this.value).show();
        }
    });

    buildAdvancedSearchList(jQuery("#choose-course-btn"));
    buildAdvancedSearchList(jQuery("#choose-tagset-btn"));
});