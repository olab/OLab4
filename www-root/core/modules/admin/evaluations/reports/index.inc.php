<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * The default file that is loaded when /admin/evaluations is accessed.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("IN_EVALUATIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluation", "read", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	?>
	<style type="text/css">
		ol.system-reports li {
			width:			70%;
			color:			#666666;
			font-size:		12px;
			padding:		0px 15px 15px 0px;
			margin-left:	5px;
		}
		
		ol.system-reports li a {
			font-size:		13px;
			font-weight:	bold;
		}
	</style>
	<h1>Evaluation Reports</h1>
	
	<h2 style="color: #669900">Student Evaluations</h2>
	<ol class="system-reports">
	<?php
	if ($ENTRADA_ACL->amIAllowed("evaluation", "update", false)) {
	?>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=student-course-evaluations">Course Evaluations</a><br />
			Reports showing the students' evaluation of their pre-clerkship courses.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=student-teacher-evaluations">Teacher Evaluations</a><br />
			Reports showing the students' evaluation of their pre-clerkship teachers.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=learner-evaluations">Learner Evaluations</a><br />
			Reports showing the results of evaluations on the learners with a progress-over-time aspect for individual questions, or questions associated with Objectives.
		</li>
	<?php
	}
	?>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=student-clerkship-evaluations">Clerkship Core Rotation Evaluations</a><br />
            Reports showing the students' evaluation of their clerkship rotations.
        </li>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=student-clerkship-preceptor-evaluations">Clerkship Preceptor Evaluations</a><br />
            Reports showing the students' evaluation of their clerkship preceptors.
        </li>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=evaluations-aggregated-by-objective">Learner (And Patient Encounter) Evaluations Aggregate Report</a><br />
            Reports showing evaluations completed for learners aggregated by the objectives tagged to the questions.
        </li>
	</ol>
	<?php
}