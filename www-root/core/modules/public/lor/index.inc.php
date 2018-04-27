<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The default file that is loaded when /lor is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_PUBLIC_LOR")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
}

if ($ENTRADA_ACL->amIAllowed("lor", "update", false)) {
    $sidebar_html = "<ul class=\"menu\">\n";
    $sidebar_html .= "    <li class=\"on\"><a href=\"" . ENTRADA_URL . "/" . $MODULE . "\">Student View</a></li>\n";
    $sidebar_html .= "    <li class=\"off\"><a href=\"" . ENTRADA_URL . "/admin/" . $MODULE . "\">Administrator View</a></li>\n";
    $sidebar_html .= "</ul>\n";
    new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
}

$HEAD[] = "<script>var IN_ADMIN = false;</script>";
$HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/" . $MODULE . ".js?release=" . APPLICATION_VERSION . "\"></script>";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\"  href=\"" . ENTRADA_RELATIVE . "/css/" . $MODULE . ".css?release=" . APPLICATION_VERSION . "\">\n";

$ONLOAD[] = "get_learning_objects(false);";

$learning_object = new Models_LearningObject();
$active_learning_objects = $learning_object->fetchActiveResources();
?>
<form id="learning-object-search-form" method="post" action="<?php echo ENTRADA_URL . "/" . $MODULE; ?>">
    <div class="row-fluid space-below">
        <div class="btn-group">
            <a href="#" id="grid-view" data-view="grid" class="view-toggle btn" title="Grid View"><i
                        class="fa fa-th-large fa-lg" aria-hidden="true"></i></a>
            <a href="#" id="list-view" data-view="list" class="view-toggle btn" title="List View"><i
                        class="fa fa-list fa-lg" aria-hidden="true"></i></a>
        </div>
        <input type="text" id="learning-object-search" class="pull-right input-large search-icon"
               name="learning-object-search" placeholder="Search <?php echo $translate->_("Learning Objects"); ?>..."
               autofocus/>
        <select class="pull-right input-large space-right" id="object-type-search" name="object_type">
            <option value="">All Types</option>
            <option value="link">Link/URL</option>
            <option value="tincan">TinCan Learning Module</option>
            <option value="scorm">Scorm Learning Module</option>
        </select>
    </div>
    <div id="learning-object-msgs">
        <div id="learning-objects-loading" class="">
            <p><?php echo "Loading " . $translate->_("Learning Objects") . "..."; ?></p>
            <img src="<?php echo ENTRADA_URL . "/images/loading.gif" ?>"/>
        </div>
    </div>
</form>
<script>
    var learning_object_view = "<?php echo(isset($PREFERENCES["learning_object_view"]) ? $PREFERENCES["learning_object_view"] : "grid"); ?>";
    jQuery(".view-toggle").on("click", function (e) {
        learning_object_view = jQuery(this).attr("data-view");
        jQuery(".view-toggle[data-view='" + learning_object_view + "']").addClass("active");
        jQuery.ajax({
            url: "<?php echo ENTRADA_URL . "/" . $MODULE ?>?section=api-lor",
            type: "GET",
            data: "method=update-view-preferences&lor_view=" + learning_object_view,
            dataType: 'json'
        });
    });

    jQuery(document).ready(function ($) {
        $("#learning-objects-loading").addClass("hide");
        var lor_view = "<?php echo(isset($PREFERENCES["lor_view"]) ? $PREFERENCES["lor_view"] : "grid"); ?>";
        $(".view-toggle[data-view='" + lor_view + "']").addClass("active").trigger("click");
    });
</script>

<div id="learning-object-list-container">
    <div id="learning-objects-list"></div>
</div>
<?php
$total_learning_objects = (int)$learning_object->countAllResources();
if ($total_learning_objects <= 50) { ?>
    <a id="load-learning-objects"
       class="btn btn-block load-learning-objects-disabled"><?php echo sprintf($translate->_("Showing %s of %s Learing Objects"), ($active_learning_objects ? count($active_learning_objects) : "0"), ($active_learning_objects ? count($active_learning_objects) : "0")); ?></a>
    <?php
    if ($total_learning_objects == 0) { ?>
        <script type="text/javascript">
            jQuery("#load-learning-objects").addClass("hide");
        </script>
        <?php
    }
} else { ?>
    <a id="load-learning-objects"
       class="btn btn-block"><?php echo sprintf($translate->_("Showing %s of %s Learning Objects"), count($active_learning_objects), $total_learning_objects); ?></a>
    <?php
}
?>
