<?php
$view_type = "list-view";
if (isset($_GET["view-type"]) && $tmp_var = clean_input($_GET["view-type"], array("trim", "notags"))) {
    $_SESSION["curriculum-tags-view-type"] = $tmp_var;
}
if (isset($_SESSION["curriculum-tags-view-type"]) && $tmp_var = clean_input($_SESSION["curriculum-tags-view-type"], array("trim", "notags"))) {
    $view_type = $tmp_var;
} else {
    $_SESSION["curriculum-tags-view-type"] = $view_type;
}
if ($objective_set_id == 0) {
    add_error("No curriculum tag set with this ID");
    echo display_error();
    exit();
}
$default_fields = array();
if ($tmp_var = $objective_set->getRequirements()) {
    $default_fields = json_decode($tmp_var, true);
}
?>
<style>
    #side_buttons input {
        max-width: 120px;
        text-align: center;
        border: none;
        font-weight: 600;
    }
    .load_more_results[disabled] {
        display: none;
    }
</style>
<div id="msgs"></div>

<div id="actions" class="curriculum-actions text-right">
    <form id="export-form" action="<?php echo ENTRADA_URL . "/api/curriculum-tags.api.php?method=export-csv&set_id=" . $objective_set_id; ?>" method="post">
        <div class="btn-group" id="view-type">
            <a title="table-view" class="btn" href="<?php echo ENTRADA_URL . "/admin/curriculum/tags/objectives?set_id=" . $objective_set_id; ?>&view-type=table-view" data-type="table-view"><i class="fa fa-table"></i></a>
            <a class="btn active" href="<?php echo ENTRADA_URL . "/admin/curriculum/tags/objectives?set_id=" . $objective_set_id; ?>&view-type=list-view" data-type="list-view"><i class="fa fa-list-ul"></i></a>
        </div>
        <?php if ($ENTRADA_ACL->amIAllowed("objective", "create", false)) { ?>
            <a class="btn btn-success btn-add" id="btn-add" href="<?php echo ENTRADA_URL . "/admin/curriculum/tags/objectives?section=add&id=" . $parent_id; ?>">
                <i class="fa fa-plus-circle" aria-hidden="true"></i>
                <?php echo($translate->_("Add Tag")); ?>
            </a>
        <?php }
        if ($ENTRADA_ACL->amIAllowed("objective", "update", false)) { ?>
            <a href="<?php echo ENTRADA_URL . "/admin/curriculum/tags?section=edit&id=" . $objective_set_id; ?>" class="btn btn-primary btn-edit" id="btn-edit">
                <i class="fa fa-edit" aria-hidden="true"></i>
                <?php echo($translate->_("Edit Tag Set")); ?>
            </a>
        <?php }
        if (!$empty_tag_sets) { ?>
            <button class="btn btn-primary btn-export" id="exportbtn" name="exportbtn">
                <i	class="fa fa-download" aria-hidden="true"></i>
                <?php echo($translate->_("Export")); ?>
            </button>
        <?php } ?>
        <?php if ($ENTRADA_ACL->amIAllowed("objective", "create", false)) { ?>
        <button class="btn btn-primary btn-import" id="importbtn" name="importbtn" type="button" data-toggle="modal" data-target="#import-tags-modal">
            <i	class="fa fa-upload" aria-hidden="true"></i>
            <?php echo($translate->_("Import from CSV")); ?>
        </button>
        <?php } ?>
    </form>
</div>

<div class="tab-content">
    <div class="tab-pane" id="table-view">
        <div id="filter-accordion-alerts" data-msg="Objectives"></div>
        <div class="hide level-back-btn">
        </div>
        <table class="hide curriculum-tags-table" id="TableManageObj" data-toggle="table"
               data-show-multi-sort="true" data-striped="true"
               data-show-columns="true" data-unique-id="objective_id"
               data-sortable="true" data-sort-name="objective_order"
               data-striped="true">
            <thead>
            <tr>
                <th data-field="selectall" id="selectall" data-checkbox="true"></th>

                <th data-field="objective_id" data-sortable="true" data-formatter="linkFormatterObjective" data-visible="false">
                    <?php echo($translate->_("ID"));?>
                </th>
                <?php
                if (isset($default_fields["code"])) {
                    ?>
                    <th data-field="objective_code" data-sortable="true" data-formatter="linkFormatterObjective" <?php echo (isset($default_fields["code"]) && $default_fields["code"]["required"] == true ? "" : "data-visible=\"false\""); ?>>
                        <?php echo($translate->_("Code"));?>
                    </th>
                    <?php
                }
                ?>
                <?php
                if ($objective_set->getLanguages() != null) {
                    $languages = json_decode($objective_set->getLanguages(), true);
                    if (sizeof($languages) > 1) {
                        $language_names = json_decode(Entrada_Settings::fetchValueByShortname("language_supported"), true);
                        foreach ($languages as $key => $language) {
                            if (isset($default_fields["title"])) {
                                ?>
                                <th data-field="<?php echo $language; ?>_name" data-sortable="true" data-formatter="linkFormatterObjective"
                                    data-click-to-select="false" <?php echo ($key === 0 ? "" : "data-visible=\"false\""); ?>>
                                    <?php echo $language_names[$language]["name"] . " " . $translate->_("Title");?>
                                </th>
                                <?php
                            }

                            if (isset($default_fields["description"])) {
                                ?>
                                <th data-field="<?php echo $language; ?>_description" data-sortable="true" data-formatter="linkFormatterObjective"
                                    data-click-to-select="false" data-visible="false">
                                    <?php echo $language_names[$language]["name"] . " " . $translate->_("Description");?>
                                </th>
                                <?php
                            }
                            ?>
                            <?php
                        }
                    } else {
                        if (isset($default_fields["title"])) {
                            ?>
                            <th data-field="objective_name" data-sortable="true" data-formatter="linkFormatterObjective" <?php echo (isset($default_fields["title"]) && $default_fields["title"]["required"] == true ? "" : "data-visible=\"false\""); ?>>
                                <?php echo($translate->_("Title"));?>
                            </th>
                            <?php
                        }

                        if (isset($default_fields["description"])) {
                            ?>
                            <th data-field="objective_description" data-sortable="true" data-formatter="linkFormatterObjective"
                                data-click-to-select="false" <?php echo (isset($default_fields["description"]) && $default_fields["description"]["required"] == true ? "" : "data-visible=\"false\""); ?>>
                                <?php echo($translate->_("Description"));?>
                            </th>
                            <?php
                        }
                    }
                }

                    $attributes = Models_Objective_TagAttribute::fetchAllByObjectiveSetID($objective_set_id);
                    if ($attributes) {
                        foreach ($attributes as $attribute) {
                            $objective = Models_ObjectiveSet::fetchRowByID($attribute->getTargetObjectiveSetId());
                            ?>
                            <th class="attribute-column" data-attribute-id="<?php echo $objective->getID(); ?>" data-field="attribute_<?php echo $objective->getID(); ?>" data-click-to-select="false" data-formatter="formatTagAttributes" data-visible="false">
                                <?php echo $objective->getShortname(); ?>
                            </th>
                            <?php
                        }
                    }
                ?>

                <th data-field="objective_status_description" data-sortable="true" data-click-to-select="false"  data-visible="false">
                    <?php echo($translate->_("Status"));?>
                </th>

                <th data-field="objective_translation_status_description" data-sortable="true" data-click-to-select="false"  data-visible="false">
                    <?php echo($translate->_("Translation Status"));?>
                </th>

                <th data-field="options" data-width="10%" data-sortable="false" data-click-to-select="false"  data-visible="true" data-switchable="false" data-formatter="buttonsFormatter">

                </th>
            </tr>
            </thead>
        </table>
        <button class="load_more_results btn btn-default btn-block space-above" disabled data-parent-id="0">Show more results</button>
        <div class="space-above">
            <?php if ($ENTRADA_ACL->amIAllowed("objective", "delete", false)) {?>
                <button class="btn btn-danger btn-delete" id="btn-delete" data-toggle="modal" data-target="#delete-courses-modal" disabled>
                    <i	class="fa fa-trash" aria-hidden="true"></i>
                    <?php echo($translate->_("Delete Items")); ?>
                </button>
            <?php }  ?>
        </div>
        <div class="table-msg space-above"></div>
        <input type="hidden" id="current_level" value="1">
        <input type="hidden" id="maximum_levels" value="1">
        <input type="hidden" id="table-parent-id" value="0">
        <input type="hidden" id="table-parent-title" value="<?php echo $page_title; ?>">
        <input type="hidden" id="current_parent_id" value="0">
        <input type="hidden" id="linked_tags" value="false">
    </div>
    <div class="tab-pane" id="list-view">
        <div id="curriculum-tags-list" class="space-above"></div>
        <button class="load_more_results btn btn-default btn-block space-above" disabled data-parent-id="0">Show more results</button>
    </div>
</div>
<div class="loading-curriculum-tags hide">
    <p class="text-center muted space-above"><i class="fa fa-spin fa-spinner"></i> Loading results, please wait</p>
</div>

<div id="delete-tag-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="delete-tag-modal-title" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="delete-tag-modal-title"><?php echo $translate->_("Delete Curriculum Tag"); ?></h3>
    </div>
    <div class="modal-body">
        <p></p>
        <input id="curriculum_tag_id" value="0" type="hidden">
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
        <button class="btn btn-danger" id="delete-tag-modal-btn">Delete</button>
    </div>
</div>

<div id="manage-modal" class="modal fullscreen-modal no-padding hide">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3></h3>
    </div>

    <div class="modal-body">
        <div class="container"></div>
    </div>
    <div class="modal-footer">
        <div id="side_buttons" class="pull-left"></div>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button id="tag-btn" type="submit" class="btn btn-primary">Save</button>
    </div>
</div>

<div id="import-tags-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="import-tags-modal-title" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="import-tags-modal-title">Import Curriculum Tags</h3>
    </div>
    <div class="modal-body">
        <form id="import-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/api/curriculum-tags.api.php?method=import-csv"?>" enctype="multipart/form-data" method="POST">
            <div class="alert alert-info">
                <p>Click <a href="#" id="example-file-btn"><strong>here</strong></a> to download an example csv file</p>
            </div>
            <div class="control-group">
                <label for="csv" class="control-label">Choose a file to import:</label>
                <div class="controls">
                    <input type="file" class="control" id="csv" name="csv">
                </div>
            </div>
            <?php if ((!$empty_tag_sets && $objective_set->getMaximumLevels() > 1) || $objective_set->getMaximumLevels() > 1) : ?>
                <div class="control-group">
                    <label for="choose-parent-tag-btn"
                           class="control-label"><?php echo $translate->_("Parent Tag"); ?></label>
                    <div class="controls">
                        <button id="choose-parent-tag-btn" class="btn btn-search-filter"
                                style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Tags"); ?>
                            <i class="icon-chevron-down btn-icon pull-right"></i></button>
                    </div>
                </div>
            <?php endif; ?>
            <div id="import-errors"></div>
            <input type="hidden"  id="set_id" name="set_id" value="<?php echo $objective_set_id; ?>">
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
        <button class="btn btn-success" id="import-tags-modal-btn">Import</button>
    </div>
</div>

<script type="text/javascript">

    var $j = jQuery.noConflict();

    $j(document).ready(function(event) {
        view_type = "<?php echo $view_type; ?>";
        if (view_type == "list-view") {
            var tagslist = new CurriculumTagsList({breadcrumbs: true});
            tagslist.get();
        } else {
            UIEventSpace.init(parent_module);
        }
        parent_module = {
            name:"objectives",
            base_url:window.location.href
        };
        $j('#view-type a').removeClass('active');
        $j('#view-type a[data-type="' + view_type + '"]').addClass('active');
        $j('#' + view_type).addClass('active');

        jQuery("#choose-parent-tag-btn").advancedSearch({
            api_url: "<?php echo ENTRADA_URL; ?>/api/curriculum-tags.api.php",
            build_selected_filters: false,
            resource_url: ENTRADA_URL,
            select_all_enabled: false,
            filters: {
                "tag": {
                    label: '<?php echo $translate->_("Tag"); ?>',
                    data_source: "get-tags",
                    api_params: {
                        first_level: <?php echo $parent_id; ?>,
                        max_level: <?php echo $objective_set->getMaximumLevels(); ?>,
                    },
                    secondary_data_source: "get-tags",
                    selector_control_name: "parent_tag",
                    mode: "radio"
                }
            },
            target_name: "tags",
            no_results_text: "<?php echo $translate->_("No Tags found matching the search criteria"); ?>",
            selected_list_container: jQuery("#import-form"),
            parent_form: jQuery("#import-form"),
            width: 360,
            height: 280,
            modal: true
        });
        jQuery("body").on("change", "#choose-parent-tag-btn", function () {
            (jQuery("input[name=\"parent_tag\"]").length > 1 ? jQuery("input[name=\"parent_tag\"]")[0].remove() : false);
        });
    });

</script>
