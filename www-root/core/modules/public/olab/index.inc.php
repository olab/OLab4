<?php
/**
 * OpenLabyrinth 4 [ http://www.openlabyrinth.ca ]
 */
use Entrada\Modules\Olab\Classes\HostSystemApi;

HostSystemApi::addToHead( "<script>var WEBSITE_ROOT = \"" . HostSystemApi::getRootUrl() . "\";</script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"". HostSystemApi::getRelativePath() ."/javascript/jquery/jquery.dataTables-1.10.11.min.js\" defer></script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"". HostSystemApi::getRelativePath() ."/javascript/vue/vue.js\" defer></script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.utilities.js\"></script>" );
HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.list.main.js\"></script>" );
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
    HostSystemApi::UpdateBreadCrumb( HostSystemApi::getRootUrl()  . "/olab", "Player " );

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

                <div v-show="loadingList">
                    <center>
                        <img src="/apidev/images/loading.gif" /><p/>
                    </center>
                </div>
                <table id='olabMapData' class='table table-striped table-bordered' style="table-layout: fixed;word-wrap: break-word;">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="mapDetails" class="span3">

                <div v-if="loadingDetail">
                    <div v-if="detailDataPending">
                        <center>
                            <img src="/apidev/images/loading.gif" />
                        </center>
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
