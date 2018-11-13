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

<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">

<script>
    var view = null;
</script>

<div id="content">

    <div id="olabContent" class="container-fluid">

        <div id="wizardStepRow" class="row" v-cloak="">

            <div class="span2" v-bind:class="{ wizardActiveStep: isStep1Active, wizardInactiveStep: !isStep1Active }" 
                 v-on:click="wizardPageSelect(1);" >
                    <span class="wizardOptionText">
                        <span class="badge badge-info">Step 1</span>&nbsp;Select Objects&nbsp;
                        <i class="fas fa-arrow-right"></i>
                    </span>
            </div>
            <div class="span2" v-bind:class="{ wizardActiveStep: isStep2Active, wizardInactiveStep: !isStep2Active }" 
                 v-show="bHaveObjectsSelected"
                 v-on:click="wizardPageSelect(2);" >
                    <span class="wizardOptionText">
                        <span class="badge badge-info">Step 2</span>&nbsp;Select Users&nbsp;
                        <i class="fas fa-arrow-right"></i>
                    </span>
            </div>
            <!--<div class="span2" v-bind:class="{ wizardActiveStep: isStep3Active, wizardInactiveStep: !isStep3Active }" 
                 v-on:click="wizardPageSelect(3);" >
                    <span class="wizardOptionText"><span class="badge badge-info">Step 3</span>&nbsp;Select Roles&nbsp;<i class="fas fa-arrow-right"></i></span>
            </div>-->
            <div class="span2" v-bind:class="{ wizardActiveStep: isStep3Active, wizardInactiveStep: !isStep3Active }" 
                 v-show="bHaveObjectsSelected && bHaveUsersRolesSelected"
                 v-on:click="wizardPageSelect(3);" >
                    <span class="wizardOptionText">
                        <span class="badge badge-info">Step 3</span>&nbsp;Verify/Apply
                    </span>
            </div>

        </div>

        <div id="objectSelectRow" class="row" v-show="isStep1Active" v-cloak="">
                <br />

            <div class="span1">Object Type:</div>
            <div class="span3">
                <select id="objectTypeSelector" v-model="objectTypeSelection">
                    <option value="">-- Select --</option>
                    <option value="maps">Maps</option>
                    <option value="mapnodes">Map Nodes</option>
                </select>
            </div>
            <div class="span1">
                <button id="btnLoad" type="button" v-bind:disabled="objectTypeSelection === ''"
                        onclick="view.onClickLoadObjects(this);" 
                        class="btn btn-secondary">Load</button>
            </div>

        </div>

        <div id="objectTableRow" class="row" v-show="isStep1Active" v-cloak="">
                <br />

            <div class="span12">

                <span v-show="bLoadingObjects === true">Loading...</span>
                <table v-pre="" id='olabObjectTable' class='table table-bordered compact' 
                       v-show="bLoadingObjects === false"
                       style="table-layout: fixed;word-wrap: break-word;  width: 100%" >
                    <thead></thead>
                    <tbody></tbody>
                </table>

            </div>

        </div>

        <div id="userRoleSelectRow" class="row" v-show="isStep2Active" v-cloak="">
                <br />

            <div class="span1">Object Type:</div>
            <div class="span3">
                <select id="userRoleSelector" v-model="userRoleSelection">
                    <option value="">-- Select --</option>
                    <option value="users">Users</option>
                    <option value="roles">Roles</option>
                </select>
            </div>
            <div class="span1">
                <button id="btnLoad" type="button" v-bind:disabled="userRoleSelection === ''"                        onclick="view.onClickLoadUserRoles(this);" 
                        class="btn btn-secondary">Load</button>
            </div>

        </div>

        <div id="userRoleSelectRow" class="row" v-show="isStep2Active">
                <br />

            <div class="span8">

                <span v-show="bLoadingUsersRoles === true">Loading...</span>
                <table v-pre="" id='olabUserRoleTable' class='table table-bordered compact' 
                       v-show="bLoadingUsersRoles === false"                       
                       style="table-layout: fixed;word-wrap: break-word; width: 100%" >
                    <thead></thead>
                    <tbody></tbody>
                </table>

            </div>

        </div>

        <div class="row" v-show="isStep3Active">

            <div class="span12">
                <br />

                <p>{{objectTypeSelection}}:</p>
                <ul id="selectedObjectList">
                  <li v-for="item in selectedObjects">
                    {{ item[1] }}
                  </li>
                </ul>

                <p>{{userRoleSelection}}</p>
                <ul id="selectedUserRoleList">
                  <li v-for="item in selectedUserRoles">
                    {{ item[1] }}
                  </li>
                </ul>

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

        <div class="row" v-show="wizardStepCount === 3">
            <div class="span12">
                <center>
                    <button id="btnClear" type="button" class="btn btn-primary">Clear</button>
                    <button id="btnApply" type="button" class="btn btn-secondary">Apply</button>
                </center>
            </div>
        </div>

        <!--
        <div id="pageNavButtonsRow" class="row">

            <div class="span4">
                &nbsp;
            </div>

            <div class="span4">
                <button v-cloak="" id="btnPrev" type="button" class="btn btn-primary" v-show="!isStep1Active">
                    <i class="fas fa-arrow-left"></i>&nbsp;Previous
                </button>
                &nbsp;
                <button v-cloak="" id="btnNext" type="button" class="btn btn-primary" v-show="!isStep4Active">
                    Next&nbsp;<i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <div class="span4">
                &nbsp;
            </div>

        </div>
        -->

    </div>
</div>