<?php
header("Content-type: text/html; charset=utf-8");
require_once("Classes/mspr/MSPRs.class.php");
require_once("Entrada/mspr/functions.inc.php");
define("MAX_RESEARCH", 6);
define("MAX_OBSERVERSHIPS", 8);

function generateMSPRHTML(MSPR $mspr,$timestamp = null) {
	if (!$timestamp) {
		$timestamp = time();
	}
	$user = $mspr->getUser();
	$name = $user->getFirstname() . " " . $user->getLastname();
	$grad_year = $user->getGradYear();
	$entry_year = $user->getEntryYear();
	$doc_date = date("F j, Y",$timestamp);
	ob_start();
	?>
	<!DOCTYPE html>
	<html>
	
		<head>
			<title>Medical School Performance Report of <?php echo $name; ?></title>
		
			<meta name="author" content="Associate Dean, Undergraduate Medical Education, Queen's University">
			<meta name="copyright" content="<?php echo COPYRIGHT_STRING; ?>">
			<meta name="docnumber" content="Generated: <?php echo date(DEFAULT_DATE_FORMAT, $timestamp) ?>">
			<meta name="generator" content="Entrada MSPR Generator">
			<meta name="keywords" content="Class of <?php echo $year; ?>, Undergraduate, Education, Dean's Letter, MSPR, Medical School Performance Report">
			<meta name="subject" content="Medical School Performance Report">
			<meta charset="utf-8">
		</head>
		
		<body>
			<h1></h1>
			<table width="100%" border=0 cellpadding=0 cellspacing=0>
			<tr>
			<td align="left"><h1>Medical Student Performance Record</h1></td>
			<td align="right" width=400><img src="<?php echo str_replace("https://", "http://",ENTRADA_URL); ?>/images/Letterhead.png" height=300 width=400></td>
			</tr>
			</table>
			<div align="right"><b><u><?php echo $doc_date; ?></u></b></div>
			<center><h2><u><?php echo $name; ?></u></h2></center>
			<div><?php echo $name;?> entered the first year in <?php echo $entry_year; ?> and is expected to graduate with the degree of Doctor of Medicine in May of <?php echo $grad_year; ?>. The following is intended to supplement the official Queen's University Transcript.</div>
			<br><br>
			<?php 
			$component = $mspr["Clerkship Core Completed"];
			if ($component && $component->count() > 0) { 
				?>
				<h3><u>Clerkship Rotations Completed Satisfactorily to Date</u></h3>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top" width="50%"><?php echo nl2br($entity->getDetails()); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$component = $mspr["Clerkship Core Pending"];
			if ($component && $component->count() > 0) { 
				?>
				<h3><u>Clerkship Rotations Pending</u></h3>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top" width="50%"><?php echo nl2br($entity->getDetails()); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$component = $mspr["Clerkship Electives Completed"];
			if ($component && $component->count() > 0) { 
				?>
				<h3><u>Clerkship Electives Completed Satisfactorily to Date</u></h3>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td>
							<table width="100%" border=0 cellpadding=0 cellspacing=0>
								<tr>
									<td valign="top" width="50%"><?php echo $entity->getTitle(); ?></td>
									<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
								</tr>
								<tr>
									<td valign="top" colspan=2><?php echo $entity->getLocation()."<br>Supervisor: ". $entity->getSupervisor(); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$component = $mspr["Clinical Performance Evaluation Comments"];
			if ($component && $component->count() > 0) { 
				?>
				<h3><u>Clinical Performance Evaluation Comments</u></h3>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						 <td valign="top"><p><?php echo trim(nl2br($entity->getComment())); ?> <i><?php echo $entity->getSource(); ?></i></p></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$observerships = $mspr["Observerships"];
			$studentships = $mspr["Studentships"];
			$student_run_electives = $mspr["Student-Run Electives"];
			$international_activities = $mspr["International Activities"];

			if (($observerships && $observerships->count() > 0) || ($studentships && $studentships->count() > 0) || ($student_run_electives && $student_run_electives->count() > 0 ) || ($international_activities && $international_activities->count() >0)) { 
				?>
				<h3><u>Extra-Curricular Accomplishments</u></h3>
				<i>Activities appear below only when a proof of attendance has been received. This category includes: Observerships, University-approved International Activities,(unless attributable to the Critical Enquiry Project) and extra-curricular learning activities.</i>
				<?php 
			}

			$component = $observerships;
			if ($component && $component->count() > 0) { 
				$observership_no = 0;
				?>
				<h4>Learning Activities - Observerships</h4>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					if (++$observership_no > MAX_OBSERVERSHIPS) break;
					
					$preceptor = trim($entity->getPreceptorFirstname() . " " . $entity->getPreceptorLastname());
					if ((preg_match("/\b[Dd][Rr]\./", $preceptor) == 0) && ($entity->getPreceptorFirstname() != "Various")) {
						$preceptor = "Dr. ".$preceptor;
					}
					?>
					<tr>
						<td valign="top" width="50%"><?php echo nl2br($entity->getDetails()); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}
			
			$component = $studentships;
			if ($component && $component->count() > 0) { 
				?>
				<h4>Studentships</h4>
				<i>A limited number of summer scholarships may be available to students in the first and second medical years through the office of the Associate Dean, Undergraduate Medical Education. Awards are adjudicated by the Awards Committee (Medicine) on the basis of academic achievement and preferred area of interest. Successful students are required to arrange a research project with a faculty member and submit a proposal of the work to be undertaken for approval by the awards committee.</i><br><br>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top" width="50%"><?php echo $entity->getTitle(); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getYear(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}			

			$component = $student_run_electives;
			if ($component && $component->count() > 0) { 
				?>
				<h4>Student-Run Electives</h4>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top" width="50%"><?php echo nl2br($entity->getDetails()); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$component = $international_activities;
			if ($component && $component->count() > 0) { 
				?>
				<h4>International Activities</h4>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top" width="50%"><?php echo nl2br($entity->getDetails()); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$component = $mspr["Research"];
			if ($component) {
				$component->filter("is_approved");
			}
				
			if ($component && $component->count() > 0) { 
				$research_no = 0;
				?>
				<h3><u>Publications</u></h3>
				<i>Students are encouraged to pursue extracurricular research endeavours to enrich their academic experience. Research undertaken during the medical program appears below.</i><br><br>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					if (++$research_no > MAX_RESEARCH) break;
					?>
					<tr>
						<td valign="top"><?php echo nl2br($entity->getText()); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$internal_awards = $mspr["Internal Awards"];
			$external_awards = $mspr["External Awards"];
			if ($external_awards) {
				$external_awards->filter("is_approved");
			}

			$component = new Collection();
			if ($internal_awards) {
				foreach ($internal_awards as $award) {
					$component->push($award);
				}
			}
			if ($external_awards) {
				foreach ($external_awards as $award) {
					$component->push($award);
				}
			}
			$component->sort("year", "asc");

			if ($component->count() > 0) { 
				?>
				<h3><u>Academic Awards</u></h3>
				<i>A brief summary of the terms of reference accompanies each award. Only items of academic significance and either acknowledged or awarded by Queen's University are presented.</i><br><br>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					$award = $entity->getAward(); 
					?>
					<tr>
						<td valign="top" width="50%"><?php echo $award->getTitle(); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getAwardYear(); ?></td>
					</tr>
					<tr>
						<td valign="top" colspan=2><blockquote><?php echo nl2br($award->getTerms()); ?></blockquote></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}

			$component = $mspr["Contributions to Medical School"];
			if ($component) {
				$component->filter("is_approved");
			}
			if ($component && $component->count() > 0) { 
				?>
				<h3><u>Contributions to Medical School/Student Life</u></h3>
				<i>Participation in the School of Medicine student government, committees (such as admissions), and organization of extra-curricular learning activities and Seminars is listed below.</i><br><br>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top" width="50%"><?php echo $entity->getOrgEvent()."<br>".$entity->getRole(); ?></td>
						<td valign="top" width="50%" align="right"><?php echo $entity->getPeriod(); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			}
			?>
			<!--  PAGE BREAK -->
			<h3><u>Leaves of Absence</u></h3>
			<i>This section is intended for an explanation of special circumstances such as illness or concurrent degrees which may have extended the duration of the program</i><br><br>
			<?php 
			$component = $mspr["Leaves of Absence"];
			if ($component && $component->count() > 0) { 
				?>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top"><?php echo nl2br($entity->getDetails()); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			} else {
				?>
				None on Record.
				<?php 
			}
			?>
			<h3><u>Formal Remediation Received</u></h3>
			<i>This section notes instances of Formal Remediation.</i><br><br>
			<?php 
			$component = $mspr["Formal Remediation Received"];
			if ($component && $component->count() > 0) { 
				?>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top"><?php echo nl2br($entity->getDetails()); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			} else {
				?>
				None on Record.
				<?php 
			}
			?>
				
			<h3><u>Disciplinary Actions</u></h3>
			<i>This section is intended to catalogue items noted by the Student Progress and Promotion Committee of an exceptional nature such as breaches of professionalism, failure of a course/block, etc.</i><br><br>
			<?php 
			$component = $mspr["Disciplinary Actions"];
			if ($component && $component->count() > 0) { 
				?>
				<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<?php
				foreach ($component as $entity) {
					?>
					<tr>
						<td valign="top"><?php echo nl2br($entity->getDetails()); ?></td>
					</tr>
					<?php
				}
				?>
				</table>
				<br>
				<?php 
			} else {
				?>
				None on Record.
				<?php 
			}
			?>
		</body>
	</html>
	<?php
	return ob_get_clean();
}