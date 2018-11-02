<?php
/**
 * OpenLabyrinth 4 [ http://www.openlabyrinth.ca ]
 */
use Entrada\Modules\Olab\Classes\HostSystemApi;

HostSystemApi::addToHead( "<script>var WEBSITE_ROOT = \"" . HostSystemApi::getRootUrl() . "\";</script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"". HostSystemApi::getRelativePath() ."/javascript/olab/jquery.datatables/datatables.min.js\" defer></script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"". HostSystemApi::getRelativePath() ."/javascript/vue/vue.js\" defer></script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.utilities.js\"></script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.security.main.js\"></script>" );
HostSystemApi::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/css/olab/olab.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );
HostSystemApi::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/css/olab/scrollingtable.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . HostSystemApi::getRootUrl());
    exit;

} else {

    define("IN_OLAB", true);

    /*
     * Updates the <title></title> of the page.
     */
    $PAGE_META["title"] = $translate->_("OpenLabyrinth 4");


    /*
     * Adds a breadcrumb to the breadcrumb trail.
     */
    HostSystemApi::UpdateBreadCrumb( HostSystemApi::getRootUrl()  . "/olab", "Manage Security " );

    /*
     * Renders the sandbox sidebar View Helper.
     */
    $sidebar = new Views_Olab_Sidebar();
    $sidebar->render();
?>

<h1>
    <?php echo $translate->_("OpenLabyrinth 4"); ?>
</h1>

<?php
}
?>

<script>
    var view = null;
</script>

<div id="content">

    <div id="olabContent" class="container">

        <div class="row" v-show="pageMode === 'users'">
            <div class="span6">
                <h3>Users</h3>
            </div>

            <div class="span6">
                <h3>Roles</h3>
            </div>

        </div>

        <div class="row" v-show="pageMode === 'users'">
            <div class="span6">

                <table id='olabUserTable' class='table table-striped table-bordered compact' style="table-layout: fixed;word-wrap: break-word;" >
                    <thead></thead>
                    <tbody></tbody>
                </table>

            </div>

            <div class="span6">

                <table id='olabRoleTable' class='table table-striped table-bordered compact' style="table-layout: fixed;word-wrap: break-word;">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>

        <div class="row">
            <div class="span3">Object Type:</div>
            <div class="span3">
                <select>
                    <option value="">-- Select --</option>
                    <option value="maps">Maps</option>
                    <option value="mapnodes">Map Nodes</option>
                </select>
            </div>
            <div class="span1">
                <button id="btnLoad" type="button" class="btn btn-secondary">Load</button>
            </div>
        </div>

        <div class="row">

            <div class="span12">

                <!--
                <div v-show="loadingList">
                    <center>
                        <img src="<?php echo HostSystemApi::getRelativePath(); ?>/images/loading.gif" />
                        <p></p>
                    </center>
                </div>
                -->

                <table id='olabObjectTable' class='table table-striped table-bordered' style="table-layout: fixed;word-wrap: break-word;">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="span12">
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-secondary">
                    <input type="checkbox" name="options" id="option1" autocomplete="off" checked> Play
                    </label>
                    <label class="btn btn-secondary">
                    <input type="checkbox" name="options" id="option2" autocomplete="off"> Edit
                    </label>
                    <label class="btn btn-secondary">
                    <input type="checkbox" name="options" id="option3" autocomplete="off"> Annotate
                    </label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="span12">
                <center>
                    <button id="btnClear" type="button" class="btn btn-primary">Clear</button>
                    <button id="btnApply" type="button" class="btn btn-primary">Apply</button>
                </center>
            </div>
        </div>
    </div>
</div>