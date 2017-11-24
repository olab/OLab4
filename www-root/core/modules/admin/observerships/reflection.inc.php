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
 * Used in observerships.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_OBSERVERSHIPS_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("observerships", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	echo "<h1>My Observership Reflection</h1>";
	
	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/ObservershipReflection.class.php");
	
	$reflection_id = (int) $_GET["id"];
	
	$observership_reflection = ObservershipReflection::get($reflection_id);
	
	if ($observership_reflection) {
		$observership = $observership_reflection->getObservership();
		$student = User::fetchRowByID($observership->getStudentID());

		$BREADCRUMB = array();
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users", "title" => "Manage Users");
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage?id=".$student->getID(), "title" => $student->getFullname(false));
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$student->getID(), "title" => "Observerships");
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/observerships?section=reflection&id=".$reflection_id, "title" => "Observership Reflection");
	?>
	<style type="text/css">
		.confirmed tbody td {padding:10px 5px;}
	</style>
	<h2><?php echo $observership->getTitle(); ?> Reflection</h2>
	
	<p><strong>Physicians Role:</strong></p>
	<?php  echo "<p>".$observership_reflection->getPhysiciansRole()."</p>"; ?>

	<p><strong>How was the physicians role enacted?</strong></p>
	<?php  echo "<p>".$observership_reflection->getPhysicianReflection()."</p>"; ?>

	<p><strong>Consider other aspects of this role that you did not see, but would want to be a part of your own practice:</strong></p>
	<?php  echo "<p>".$observership_reflection->getRolePractice()."</p>"; ?>

	<p><strong>If there were challenges during the observership within this role, write how you might have solved them.</strong></p>
	<?php  echo "<p>".$observership_reflection->getObservershipChallenge()."</p>"; ?>

	<p><strong>You may feel that this discipline (medical specialty) lends itself to this role.  If so, please analyze how this might occur.</strong></p>
	<?php  echo "<p>".$observership_reflection->getDisciplineReflection()."</p>"; ?>

	<p><strong>Can you predict if there will be challenges for you in your practice, based on your self-assessment?  What can you do to meet these challenges, starting now?</strong></p>
	<?php  echo "<p>".$observership_reflection->getChallengePredictions()."</p>"; ?>

	<p><strong>Are there questions you have about this role or areas you still want to explore?</strong></p>
	<?php  echo "<p>".$observership_reflection->getQuestions()."</p>"; ?>

	<p><strong>Was this experience helpful in career exploration?</strong></p>
	<p><?php echo ($observership_reflection->getCareer() == "1" ? "Yes, this experience was helpful in career exploration." : "No, this experience was <strong>not</strong> helpful in career exploration."); ?></p>
	<div class="row-fluid">
		<div class="pull-right"><input type="button" class="btn btn-primary" value="Back" onclick="window.location = '<?php echo ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$student->getID(); ?>'" /></div>
	</div>
	<?php
	}
}