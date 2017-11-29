<?php
/**
 * OpenLabyrinth 4 [ http://www.openlabyrinth.ca ]
 */
use App\Modules\Olab\Classes\HostSystemApi;

$HEAD[] = "<script>var WEBSITE_ROOT = \"" . HostSystemApi::GetRootUrl() . "\";</script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"". HostSystemApi::GetRelativePath() ."/javascript/jquery/jquery.dataTables-1.10.11.min.js\" defer></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"". HostSystemApi::GetRelativePath() ."/javascript/vue/vue.js\" defer></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"" . HostSystemApi::GetRelativePath() . "/javascript/olab/olab.utilities.js\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"" . HostSystemApi::GetRelativePath() . "/javascript/olab/olab.list.main.js\"></script>";
$HEAD[] = "<link href=\"". HostSystemApi::GetRootUrl() ."/css/olab/olab.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<link href=\"". HostSystemApi::GetRootUrl() ."/css/olab/scrollingtable.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . HostSystemApi::GetRootUrl());
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
    HostSystemApi::UpdateBreadCrumb( HostSystemApi::GetRootUrl()  . "/olab", "Player " );

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

    <div id="olabContent" class="container" v-cloak>
        <div class="row">

            <div class="span9">
                <table id='olabMapData' class='table table-striped table-bordered' style="table-layout: fixed;word-wrap: break-word;">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>


            <div id="mapDetails" class="span3">

                <div v-if="loadingDetail">
                    <div v-if="detailDataPending">
                        <h4>Loading...</h4>
                    </div>
                    <div v-else>
                        <h4>Map Details</h4>
                        <div>
                            <small>
                                <p>
                                    <b>Id:&nbsp;</b>{{data.id}}
                                    <br />
                                    <b>Title:&nbsp;</b>{{data.title}}
                                    <br />
                                    <b>Authors:&nbsp;</b>{{data.author}}
                                    <br />
                                    <b>Nodes:&nbsp;</b>{{data.nodeCount}}
                                    <br />
                                    <b>Links:&nbsp;</b>{{data.linkCount}}
                                    <br />
                                    <b>Questions:&nbsp;</b>{{data.questionCount}}
                                    <br />
                                </p>
                                <p>
                                    <b>
                                        Checkpoint:
                                        <br />
                                    </b>{{data.resumeInfo}}
                                </p>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>