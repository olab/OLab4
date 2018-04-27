<div id="tag-attributes" style="min-height: 330px;">
    <?php if (isset($map_versions) && !empty($map_versions)) { ?>
        <h2 title="Map Versions" data-toggle="collapse" data-target="#map-version" class="collapsable"><?php echo $translate->_("Curriculum map version"); ?></h2>
        <div id="map-version" class="collapse in">
            <div class="control-group">
                <label for="choose-mapversion-btn" class="control-label"><?php echo $translate->_("Map Version"); ?></label>
                <div class="controls">
                    <select id="map_version_id" name="map_version_id">
                        <option value="0">-- Select a Map Version --</option>
                        <?php foreach ($map_versions as $map_version) : ?>
                            <option value="<?php echo $map_version["version_id"]; ?>">
                                <?php echo $map_version["title"] . " (" . $map_version["status"] .")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    <?php }
    if (isset($linked_objectives) && !empty($linked_objectives)) { ?>
        <h2 title="Mapped From" data-toggle="collapse" data-target="#mapped-from" class="collapsable"><?php echo $objective->getShortMethod() . $translate->_(" is mapped from:"); ?></h2>
        <div id="mapped-from" class="collapse in">
            <div id="linked_objectives">
                <div class="control-group">
                    <div class="row-fluid">
                        <?php
                        foreach ($linked_objectives as $linked_objective) {
                            $obj = Models_Objective::fetchRow($linked_objective->getObjectiveId());
                            $path = $obj->getPath($obj->getID());
                            $string = "";
                            foreach ($path as $item) {
                                $string = (!empty($string) ? $string . " > " : $string) . $item ;
                            }
                            echo    "<div class=\"row-fluid\"><h3>" . $string . "</h3><p class=\"span10 no-margin\" >" . $obj->getLongMethod() . "</p></div>";
                        } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    if (isset($attributes) && !empty($attributes)) { ?>
        <h2 title="Mapped To" data-toggle="collapse" data-target="#mapped-to" class="collapsable"><?php echo  (isset($mode) && $mode == "edit" && isset($objective) ? ("Tags mapped to " . $objective->getShortMethod()) : $translate->_("Tags mapping")); ?></h2>
        <div id="mapped-to" class="collapse in">
            <div class="control-group">
                <label for="choose-tagset-btn" class="control-label"><?php echo $translate->_("Tag Sets"); ?></label>
                <div class="controls">
                    <button id="choose-tagset-btn" type="button" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Tag Sets"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                </div>
            </div>
            <script>
                    jQuery("#choose-tagset-btn").advancedSearch({
                        api_url: '<?php echo ENTRADA_URL; ?>/api/curriculum-tags.api.php',
                        build_form: true,
                        resource_url: ENTRADA_URL,
                        filters: {},
                        target_name: "linked_tags",
                        no_results_text: "<?php echo $translate->_("No Tags found matching the search criteria"); ?>",
                        parent_form: jQuery("#objectivesForm"),
                        width: 400,
                        select_all_enabled: false,
                        modal: true
                    });
                    jQuery.getJSON(ENTRADA_URL+"/api/curriculum-tags.api.php?set_id=<?php echo $objective_set->getID(); ?>",
                        {
                            method: "get-tag-attributes"
                        } , function (json) {
                            jQuery.each(json.data, function (key, value) {
                                var name = value.target_label.split(" ").join("-");
                                jQuery("#choose-tagset-btn").data("settings").filters[name] = {
                                    label: value.target_label,
                                    api_params: {
                                        first_level: value.target_id,
                                    },
                                    data_source: "get-tags",
                                    secondary_data_source: "get-tags",
                                    selector_control_name: "linked_tags"
                                }
                            });
                        });

                    function buildAdvancedSearchList(search_btn) {
                        jQuery("input[name=\"" + search_btn.data("settings").target_name + "[]\"]").each(function () {
                            var input = jQuery(this);
                            var filter = input.attr("id").split("_")[0];
                            var filter_name = filter.split("-").join(" ");
                            var list_item = "<li class=\"" + filter + "_target_item " + filter + "_" + input.data("id") + "\" data-id=\"" + input.data("id") + '"><span class="selected-list-container"><span class="selected-list-item">' + filter_name.replace(/\b\w/g, function (l) {
                                    return l
                                }) + "</span><span class=\"remove-selected-list-item remove-target-toggle\" data-id=\"" + input.data("id") + "\" data-filter=\"" + filter + "\">×</span></span>" + input.data("label") + "</li>";
                            if (jQuery("#" + filter + "_list_container").length > 0) {
                                jQuery("#" + filter + "_list_container").append(list_item);
                            } else {
                                search_btn.after("<ul id=\"" + filter + "_list_container\" class=\"selected-items-list\">" + list_item + "</ul>")
                            }
                        });
                    }

                    buildAdvancedSearchList(jQuery("#choose-tagset-btn"));

                    jQuery("select[name*='map_version_id']").on("change", function() {
                        jQuery.each(jQuery(".search-target-control"), function () {
                            id = jQuery(this).attr("value");
                            jQuery('input[type="hidden"][value="'+ id +'"]').remove();
                            jQuery(".selected-items-list").find('[data-id="'+ id +'"]').remove();
                        });

                        <?php if (isset($mode) && $mode == "edit") { ?>
                            jQuery.getJSON(ENTRADA_URL + "/api/curriculum-tags.api.php", {
                                method: "get-linked-tags-by-map-version",
                                map_version: this.value,
                                objective_id: <?php echo(isset($objective) && $objective ? $objective->getID() : 0); ?> }, function (json) {
                                var status = json.status;
                                if (status === "success") {
                                    jQuery.each(json.data, function (key, value) {
                                        var objective_input = document.createElement("input");
                                        jQuery(objective_input).attr({
                                            type: "hidden",
                                            id: value.root_title + "_" + value.id,
                                            class: "search-target-control " + value.root_id + "_search_target_control",
                                            name: "linked_tags[]",
                                            value: value.id
                                        });
                                        jQuery(objective_input).attr("data-id", value.id);
                                        jQuery(objective_input).attr("data-label", value.title);
                                        jQuery("#objectivesForm").append(objective_input);

                                    });
                                    buildAdvancedSearchList(jQuery("#choose-tagset-btn"));
                                }
                            });
                       <?php } ?>
                    });
            </script>
        </div>
    <?php } ?>
</div>