<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MANAGE_USER_REPORTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

require_once("Entrada/metadata/reports.inc.php");


$user = User::fetchRowByID($PROXY_ID);
$SCRIPT[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/meta_data.js\"></script>";
$ONLOAD[] = "api_url = \"".ENTRADA_URL."/admin/users/manage/metadata?section=api-metadata&id=".$PROXY_ID."\";page_init();";

$metadata_prefs = preferences_load('metadata');

if (is_array($metadata_prefs) && array_key_exists('reports', $metadata_prefs) && array_key_exists('features', $metadata_prefs['reports'])) {
	$features = $metadata_prefs['reports']['features'];
} else {
	$features = getExpandedFeatures();
}

/*
 * outline
 * -------
 * page title: User Report: Name
 * Link -- configure user report options
 * Fieldset /w label indicating preview
 * | preview |
 * reminder that some options may be hidden. check config (link) page
 * Export to (Select box) PDF* or HTML
 */
?>
<h1>User Report: <?php echo $user->getName(); ?></h1>
(<a>Configure Report Options</a>)

<h2>Preview</h2>
<?php echo getExpandedProfile($user, $features); ?>


<a></a>
<?php  
}