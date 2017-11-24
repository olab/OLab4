<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 *
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
*/

require_once("Classes/users/User.class.php");
require_once("Classes/users/ClinicalFacultyMembers.class.php");

require_once("Classes/utility/Approvable.interface.php");
require_once("Classes/utility/AttentionRequirable.interface.php");
require_once("Classes/utility/Editable.interface.php");
require_once("Classes/utility/Template.class.php");

require_once("Classes/awards/InternalAwardReceipts.class.php");
require_once("Classes/mspr/ExternalAwardReceipts.class.php");
require_once("Classes/mspr/Studentships.class.php");
require_once("Classes/mspr/ClinicalPerformanceEvaluations.class.php");
require_once("Classes/mspr/Contributions.class.php");
require_once("Classes/mspr/DisciplinaryActions.class.php");
require_once("Classes/mspr/LeavesOfAbsence.class.php");
require_once("Classes/mspr/FormalRemediations.class.php");
require_once("Classes/mspr/ClerkshipRotations.class.php");
require_once("Classes/mspr/StudentRunElectives.class.php");
require_once("Classes/mspr/Observerships.class.php");
require_once("Classes/mspr/InternationalActivities.class.php");
require_once("Classes/mspr/CriticalEnquiry.class.php");
require_once("Classes/mspr/CommunityBasedProject.class.php");
require_once("Classes/mspr/ResearchCitations.class.php");
require_once("Classes/mspr/MSPRs.class.php");


function item_wrap_content($type, $entity, $content, $hide_controls = false, $comment="", $id_string="") {
    global $ENTRADA_TEMPLATE;

	$status = getStatus($entity);
	$status_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/item_status.xml";
	$status_template = new Template($status_file);

	$controls = ($hide_controls)? "" : getControls($entity, $type);

	$status_bind = array (
				"content"	=> $content,
				"reason"	=> clean_input($comment,array("notags","specialchars","nl2br")),
				"id"		=> ($id_string ? "id='".$id_string."'" : ""),
				"controls"	=> $controls
	);



	return $status_template->getResult($status_bind, array("lang" => DEFAULT_LANGUAGE, "status"=>$status));
}

function list_wrap_content($content, $class="", $id="") {
    global $ENTRADA_TEMPLATE;

	$list_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/mspr_list.xml";
	$list_template = new Template($list_file);

	$list_bind = array (
				"class" => $class,
				"id" => ($id? "id='".$id."'":""),
				"content"	=> $content
	);

	return $list_template->getResult($list_bind, array("lang" => DEFAULT_LANGUAGE));
}

function getControls($entity, $type) {
    global $ENTRADA_TEMPLATE;

	$controls_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/controls.xml";
	$control_template = new Template($controls_file);

	$user = $entity->getUser();
	$user_id = $user->getID();
	$control_bind = array(
				"user_id"	=> $user_id,
				"entity_id"	=> $entity->getID(),
				"image_dir"	=> ENTRADA_URL . "/images",
				"form_url"	=> ENTRADA_URL . "/admin/users/manage/students?section=mspr&id=" . $user_id
	);

	$controls = array();
	switch($type) { //the differences below are due to the fact that students can only edit approvable items, while staff can only edit non-approvable items
		case "admin":
			if ($entity instanceof Approvable) {
				$status = getStatus($entity);
				switch($status){
					case 'approved':
						$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "unapprove"));
						break;
					case 'unapproved':
						$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "reject"));
						$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "approve"));
						break;
					case 'rejected':
					case 'rejected_reason':
						$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "approve"));
						break;
				}
			} else {
				$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "remove"));
			}
			if ($entity instanceof Editable) {
				$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "edit"));
			}


			break;
		case "public": //fall through
		default:
			if ($entity instanceof Approvable) {
				if ($entity instanceof Editable) {
					$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "edit"));
				}
				$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "remove"));
			}
			if ($entity instanceof Observership) {
				if ($entity->getStart() >= time()) {
					$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "remove"));
					$controls[] = $control_template->getResult($control_bind, array("lang" => DEFAULT_LANGUAGE, "type" => "edit"));
				}
			}
			break;
	}
	$control_content = implode("\n", $controls);

	if ($control_content) {
		$control_set_file =	$ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/control_set.xml";
		$control_set_template = new Template($control_set_file);
		$control_set_bind = array(
				"controls"	=> $control_content
		);
		return $control_set_template->getResult($control_set_bind, array("lang" => DEFAULT_LANGUAGE));
	}
}

function getStatus($entity) {
	if ($entity instanceof Approvable) {
		//student entered data
		$status=($entity->isRejected() ? ($entity->getComment()?"rejected_reason":"rejected") : ($entity->isApproved()? "approved" : "unapproved"));
	} else if ($entity instanceof Observership) {
		$status = (($entity->getStatus() == "confirmed") ? "confirmed" : (($entity->getStatus() == "rejected") ? "rejected" : "unapproved"));
	} else {
		//staff entered data/extracted
		$status="default";
	}
	return $status;
}

function display_studentships(Studentships $studentships, $type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/studentship.xml";
	$content_template = new Template($content_file);

	if ($studentships && count($studentships) > 0) {
		foreach($studentships as $studentship) {

			$content_bind = array (
				"title"	=> clean_input($studentship->getTitle(), array("notags", "specialchars")),
				"year"	=> clean_input($studentship->getYear(), array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
			$contents .= item_wrap_content($type, $studentship,$content, $hide_controls);
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_clineval(ClinicalPerformanceEvaluations $clinevals,$type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/clinical_performance_evaluation_comment.xml";
	$content_template = new Template($content_file);

	if ($clinevals && count($clinevals) > 0) {
		foreach($clinevals as $clineval) {
			$user = $clineval->getUser();

			$content_bind = array (
				"comment"	=> clean_input($clineval->getComment(), array("notags", "specialchars", "nl2br")),
				"source"	=> clean_input($clineval->getSource(), array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
			$contents .= item_wrap_content($type, $clineval, $content, $hide_controls);
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}


function display_internal_awards(InternalAwardReceipts $receipts,$type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/internal_award.xml";
	$content_template = new Template($content_file);

	if ($receipts && count($receipts) > 0) {
		foreach($receipts as $receipt) {
			$award = $receipt->getAward();
			$user = $receipt->getUser();

			$content_bind = array (
				"award_id" => clean_input($award->getID(), array("int")),
				"title"	=> clean_input($award->getTitle(), array("notags", "specialchars")),
				"year"	=> clean_input($receipt->getAwardYear(), array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
			$contents .= item_wrap_content($type, $receipt, $content, $hide_controls);
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_external_awards(ExternalAwardReceipts $receipts,$type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/external_award.xml";
	$content_template = new Template($content_file);

	if ($receipts && count($receipts) > 0) {
		foreach($receipts as $receipt) {
			$award = $receipt->getAward();
			$user = $receipt->getUser();

			$content_bind = array (
				"title"	=> clean_input($award->getTitle(), array("notags", "specialchars")),
				"terms"	=> clean_input($award->getTerms(), array("notags", "specialchars")),
				"body"	=> clean_input($award->getAwardingBody(), array("notags", "specialchars")),
				"year"	=> clean_input($receipt->getAwardYear(), array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
			$contents .= item_wrap_content($type,$receipt, $content, $hide_controls, $receipt->getComment());
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_contributions(Contributions $contributions,$type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/contribution.xml";
	$content_template = new Template($content_file);

	$contents = "";

	if ($contributions && count($contributions) > 0) {
		foreach($contributions as $contribution) {

			$content_bind = array (
				"start_year"		=> clean_input($contribution->getStartYear(), array("int")),
				"end_year"	=> clean_input($contribution->getEndYear(), array("int")),
				"start_month"		=> clean_input($contribution->getStartMonth(), array("int")),
				"end_month"	=> clean_input($contribution->getEndMonth(), array("int")),
				"role"		=> clean_input($contribution->getRole(), array("notags", "specialchars")),
				"org_event"	=> clean_input($contribution->getOrgEvent(), array("notags", "specialchars")),
				"period"	=> clean_input($contribution->getPeriod() , array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));

			$contents .= item_wrap_content($type,$contribution, $content, $hide_controls, $contribution->getComment());
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_clerkship_details(ClerkshipRotations $rotations) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/clerkship_details.xml";
	$content_template = new Template($content_file);

	$contents = "";

	if ($rotations && count($rotations) > 0) {
		foreach($rotations as $rotation) {

			$content_bind = array (
				"details" => clean_input($rotation->getDetails(), array("notags", "specialchars", "nl2br")),
				"period" 	=> clean_input($rotation->getPeriod() , array("notags", "specialchars"))
			);

			$contents .= $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_clerkship_elective_details(ClerkshipElectivesCompleted $rotations) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/clerkship_elective.xml";
	$content_template = new Template($content_file);

	$contents = "";

	if ($rotations && count($rotations) > 0) {
		foreach($rotations as $rotation) {

			$content_bind = array (
				"details" => clean_input($rotation->getTitle(), array("notags", "specialchars", "nl2br")),
				"period" 	=> clean_input($rotation->getPeriod() , array("notags", "specialchars")),
				"location"	=> clean_input($rotation->getLocation() , array("notags", "specialchars")),
				"supervisor" => clean_input($rotation->getSupervisor() , array("notags", "specialchars"))
			);

			$contents .= $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_clerkship_core_completed(ClerkshipRotations $rotations) {
	return display_clerkship_details($rotations);
}

function display_clerkship_core_pending(ClerkshipRotations $rotations) {
	return display_clerkship_details($rotations);
}

function display_clerkship_elective_completed(ClerkshipElectivesCompleted $rotations) {
	return display_clerkship_elective_details($rotations);
}

function display_student_run_electives(StudentRunElectives $sres,$type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/student_run_elective.xml";
	$content_template = new Template($content_file);

	$contents = "";

	if ($sres && count($sres) > 0) {
		foreach($sres as $sre) {

			$content_bind = array (
				"group_name" 	=> clean_input($sre->getGroupName() , array("notags", "specialchars")),
				"university" 	=> clean_input($sre->getUniversity() , array("notags", "specialchars")),
				"location" 		=> clean_input($sre->getLocation() , array("notags", "specialchars")),
				"start_month" 	=> clean_input($sre->getStartMonth() , array("notags", "specialchars")),
				"start_year" 	=> clean_input($sre->getStartYear() , array("notags", "specialchars")),
				"end_month" 	=> clean_input($sre->getEndMonth() , array("notags", "specialchars")),
				"end_year"	 	=> clean_input($sre->getEndYear() , array("notags", "specialchars")),
				"details" 		=> clean_input($sre->getDetails(), array("notags", "specialchars", "nl2br")),
				"period" 		=> clean_input($sre->getPeriod() , array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));

			$contents .= item_wrap_content($type, $sre, $content, $hide_controls);
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_supervised_project(SupervisedProject $project = null, $type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/supervised_project.xml";
	$content_template = new Template($content_file);

	if ($project) {
		$content_bind = array (
			"title"			=> clean_input($project->getTitle(), array("notags", "specialchars")),
			"organisation"	=> clean_input($project->getOrganization(), array("notags", "specialchars")),
			"location" 		=> clean_input($project->getLocation(), array("notags", "specialchars")),
			"supervisor"	=> clean_input($project->getSupervisor(), array("notags", "specialchars"))
		);

		$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));

		$contents = item_wrap_content($type, $project, $content, $hide_controls, $project->getComment());
	} else {
		$contents = "<li>Not yet entered.</li>";
	}

	return list_wrap_content($contents);
}

function display_critical_enquiry(CriticalEnquiry $critical_enquiry = null, $type, $hide_controls = false) {
	return display_supervised_project($critical_enquiry, $type, $hide_controls);
}

function display_community_based_project(CommunityBasedProject $community_based_project = null, $type, $hide_controls = false) {
	return display_supervised_project($community_based_project, $type, $hide_controls);
}

function display_research_citations(ResearchCitations $research_citations, $type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	if ($hide_controls) {
		$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/public/mspr/research_citation.xml";
	} else {
		$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/research_citation.xml";
		$class="priority-list";
	}
	$content_template =  new Template($content_file);

	$contents = "";

	if ($research_citations && $research_citations->count() > 0) {
		foreach($research_citations as $research_citation) {

			$content_bind = array (
				"image" => ENTRADA_URL. "/images/arrow_up_down.png",
				"details" => clean_input($research_citation->getText(), array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));
			$id_string = "research_citation_".$research_citation->getID();
			$contents .= item_wrap_content($type,$research_citation, $content, $hide_controls, $research_citation->getComment(), $id_string);
		}
	} else {
		$contents = "<li>None</li>";
	}
	$id = "citations_list";
	return list_wrap_content($contents, $class, $id);
}


function display_period_details(Collection $collection, $type, $template_name, $hide_controls = false) {

}

function display_observerships(Observerships $observerships,$type, $hide_controls = false) {
    global $ENTRADA_TEMPLATE;

	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/observership.xml";
	$content_template = new Template($content_file);
	$contents = "";

	if ($observerships && $observerships->count() > 0) {
		foreach($observerships as $entity) {
			$preceptor = $entity->getPreceptor();

			if ($preceptor) {
				$preceptor_proxy_id = $preceptor->getID();
				$preceptor_firstname = "";
				$preceptor_lastname = "";
				$preceptor_prefix = $entity->getPreceptorPrefix();
				$preceptor_email = $entity->getPreceptorEmail();
				$preceptor_status = $entity->getStatus();
			} else {
				$preceptor_proxy_id = 0;
				$preceptor_firstname = $entity->getPreceptorFirstname();
				$preceptor_lastname = $entity->getPreceptorLastname();
				$preceptor_prefix = $entity->getPreceptorPrefix();
				$preceptor_email = $entity->getPreceptorEmail();
				$preceptor_status = $entity->getStatus();
			}

			$preceptor_name = (!empty($preceptor_prefix) ? $preceptor_prefix." " : "").trim( $entity->getPreceptorFirstname() . " " . $entity->getPreceptorLastname());

			$start = $entity->getStartDate();
			$end = $entity->getEndDate();

			$start = $start['y']."-".$start['m']."-".$start['d'];
			$end = $end['y']."-".$end['m']."-".$end['d'];

			$content_bind = array (
				"title" 	=> clean_input($entity->getTitle(), array("notags", "specialchars")),
				"site" 	=> clean_input($entity->getSite(), array("notags", "specialchars")),
				"location" 	=> clean_input($entity->getLocation(), array("notags", "specialchars")),
				"preceptor" 	=> clean_input($preceptor_name, array("notags", "specialchars")),
				"period" 	=> clean_input($entity->getPeriod() , array("notags", "specialchars")),
				"preceptor_proxy_id" => $preceptor_proxy_id,
				"preceptor_firstname" => $preceptor_firstname,
				"preceptor_lastname" => $preceptor_lastname,
				"preceptor_prefix" => $preceptor_prefix,
				"preceptor_email" => $preceptor_email,
				"status" => $preceptor_status,
				"start" => $start,
				"end" => $end
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));

			$contents .= item_wrap_content($type, $entity, $content, $hide_controls);
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function display_international_activities(InternationalActivities $int_acts,$type, $hide_controls = false) {
	global $ENTRADA_TEMPLATE;
	$content_file = $ENTRADA_TEMPLATE->absolute()."/modules/common/mspr/international_activity.xml";
	$content_template = new Template($content_file);

	$contents = "";
	if ($int_acts && $int_acts->count() > 0) {
		foreach($int_acts as $entity) {

			$start = $entity->getStartDate();
			$end = $entity->getEndDate();

			$start = $start['y']."-".$start['m']."-".$start['d'];
			$end = $end['y']."-".$end['m']."-".$end['d'];

			$content_bind = array (
				"title" 	=> clean_input($entity->getTitle() , array("notags", "specialchars")),
				"site" 		=> clean_input($entity->getSite() , array("notags", "specialchars")),
				"location" 	=> clean_input($entity->getLocation() , array("notags", "specialchars")),
				"start" 	=> $start,
				"end"	 	=> $end,
				"details" 	=> clean_input($entity->getDetails(), array("notags", "specialchars", "nl2br")),
				"period" 	=> clean_input($entity->getPeriod() , array("notags", "specialchars"))
			);

			$content = $content_template->getResult($content_bind, array("lang" => DEFAULT_LANGUAGE));

			$contents .= item_wrap_content($type,$entity, $content, $hide_controls);
		}
	} else {
		$contents = "<li>None</li>";
	}

	return list_wrap_content($contents);
}

function is_approved(Approvable $entity) {
	return $entity->isApproved();
}

/**
 * Function will load TinyMCE (WYSIWYG / Rich Text Editor) into the page <head></head>
 * causing all textareas on the page to be replaced with rte's.
 *
 * @param array $buttons
 * @return true
 */
function load_mspr_editor() {
	global $HEAD;
	$tinymce  = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tiny_mce/tiny_mce.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	ob_start();
	?>
	<script type="text/javascript">
	tinyMCE.init({
		mode : 'textareas',
		theme : 'advanced',
		element_format : 'html',
		verify_html:false,
		plugins : 'save, paste, inlinepopups, tabfocus, table, fullscreen',
		editor_deselector : 'expandable',
		save_enablewhendirty : true,
		convert_fonts_to_spans: false,
		inline_styles:false,
		cleanup:false,
		theme_advanced_layout_manager : 'SimpleLayout',
		theme_advanced_toolbar_location : 'top',
		theme_advanced_toolbar_align : 'left',
		theme_advanced_statusbar_location : 'bottom',
		theme_advanced_resizing : true,
		theme_advanced_resize_horizontal : false,
		theme_advanced_resizing_use_cookie : true,
		paste_auto_cleanup_on_paste : false,
		paste_convert_middot_lists : true,
		paste_convert_headers_to_strong : true,
		paste_remove_spans : true,
		paste_remove_styles : true,
		force_p_newlines : false,
		force_br_newlines : false,
		forced_root_block : 'html',
		relative_urls : false,
		remove_script_host : false,
		paste_strip_class_attributes : 'all',
		theme_advanced_buttons1 : 'fontselect, fontsizeselect, formatselect, bold, italic, underline, justifyleft, justifycenter, justifyright',
		theme_advanced_buttons2 : 'pastetext, undo, redo, |, tablecontrols, bullist, numlist, |, fullscreen, code',
		theme_advanced_buttons3 : '',
		tab_focus : ':prev,:next',
		entities : '160,nbsp,38,amp,162,cent,8364,euro,163,pound,165,yen,169,copy,174,reg,8482,trade,8240,permil,181,micro,183,middot,8226,bull,8230,hellip,8242,prime,8243,Prime,167,sect,182,para,223,szlig,8249,lsaquo,8250,rsaquo,171,laquo,187,raquo,8216,lsquo,8217,rsquo,8220,ldquo,8221,rdquo,8218,sbquo,8222,bdquo,60,lt,62,gt,8804,le,8805,ge,8211,ndash,8212,mdash,175,macr,8254,oline,164,curren,166,brvbar,168,uml,161,iexcl,191,iquest,710,circ,732,tilde,176,deg,8722,minus,177,plusmn,247,divide,8260,frasl,215,times,185,sup1,178,sup2,179,sup3,188,frac14,189,frac12,190,frac34,402,fnof,8747,int,8721,sum,8734,infin,8730,radic,8764,sim,8773,cong,8776,asymp,8800,ne,8801,equiv,8712,isin,8713,notin,8715,ni,8719,prod,8743,and,8744,or,172,not,8745,cap,8746,cup,8706,part,8704,forall,8707,exist,8709,empty,8711,nabla,8727,lowast,8733,prop,8736,ang,180,acute,184,cedil,170,ordf,186,ordm,8224,dagger,8225,Dagger,192,Agrave,194,Acirc,195,Atilde,196,Auml,197,Aring,198,AElig,199,Ccedil,200,Egrave,202,Ecirc,203,Euml,204,Igrave,206,Icirc,207,Iuml,208,ETH,209,Ntilde,210,Ograve,212,Ocirc,213,Otilde,214,Ouml,216,Oslash,338,OElig,217,Ugrave,219,Ucirc,220,Uuml,376,Yuml,222,THORN,224,agrave,226,acirc,227,atilde,228,auml,229,aring,230,aelig,231,ccedil,232,egrave,234,ecirc,235,euml,236,igrave,238,icirc,239,iuml,240,eth,241,ntilde,242,ograve,244,ocirc,245,otilde,246,ouml,248,oslash,339,oelig,249,ugrave,251,ucirc,252,uuml,254,thorn,255,yuml,914,Beta,915,Gamma,916,Delta,917,Epsilon,918,Zeta,919,Eta,920,Theta,921,Iota,922,Kappa,923,Lambda,924,Mu,925,Nu,926,Xi,927,Omicron,928,Pi,929,Rho,931,Sigma,932,Tau,933,Upsilon,934,Phi,935,Chi,936,Psi,937,Omega,945,alpha,946,beta,947,gamma,948,delta,949,epsilon,950,zeta,951,eta,952,theta,953,iota,954,kappa,955,lambda,956,mu,957,nu,958,xi,959,omicron,960,pi,961,rho,962,sigmaf,963,sigma,964,tau,965,upsilon,966,phi,967,chi,968,psi,969,omega,8501,alefsym,982,piv,8476,real,977,thetasym,978,upsih,8472,weierp,8465,image,8592,larr,8593,uarr,8594,rarr,8595,darr,8596,harr,8629,crarr,8656,lArr,8657,uArr,8658,rArr,8659,dArr,8660,hArr,8756,there4,8834,sub,8835,sup,8836,nsub,8838,sube,8839,supe,8853,oplus,8855,otimes,8869,perp,8901,sdot,8968,lceil,8969,rceil,8970,lfloor,8971,rfloor,9001,lang,9002,rang,9674,loz,9824,spades,9827,clubs,9829,hearts,9830,diams,8194,ensp,8195,emsp,8201,thinsp,8204,zwnj,8205,zwj,8206,lrm,8207,rlm,173,shy,233,eacute,237,iacute,243,oacute,250,uacute,193,Aacute,225,aacute,201,Eacute,205,Iacute,211,Oacute,218,Uacute,221,Yacute,253,yacute',
		valid_elements : ""
			+"html,"
			+"head,"
			+"title,"
			+"meta[author|keywords|copyright|generator|docnumber|subject],"
			+"body,"
			+"table[border|width|cellpadding|cellspacing],"
			+"tr,"
			+"td[align|valign|width],"
			+"b,"
			+"div,"
			+"br,"
			+"font[color|face|size],"
			+"img[src|id|width|height|align|hspace|vspace],"
			+"i,"
			+"ul,"
			+"ol[type],"
			+"li,"
			+"p[align],"
			+"h1,"
			+"h2,"
			+"h3,"
			+"h4,"
			+"h5,"
			+"h6,"
			+"u"
	});

	function toggleEditor(id) {
		if(!tinyMCE.getInstanceById(id)) {
			tinyMCE.execCommand('mceAddControl', false, id);
		} else {
			tinyMCE.execCommand('mceRemoveControl', false, id);
		}
	}
	</script>
	<?php

	$tinymce .= ob_get_clean();
	/**
	 * You must add this first in the $HEAD array because TinyMCE will
	 * not load if scriptaculous is loaded before it (PITA). Do you know
	 * how long it took me to figure this out? ARG.
	 * Ref: http://wiki.script.aculo.us/scriptaculous/show/TinyMCE
	 */
	if ((is_array($HEAD)) && (count($HEAD))) {
		array_unshift($HEAD, $tinymce);
	} else {
		$HEAD[] = $tinymce;
	}
	return true;
}

function get_mspr_entity($type, $entity_id) {
	switch($type) {
		case 'studentships':
			$entity = Studentship::get($entity_id);
			break;
		case 'clineval':
			$entity = ClinicalPerformanceEvaluation::get($entity_id);
			break;
		case 'internal_awards':
			$entity = InternalAwardReceipt::get($entity_id);
			break;
		case 'external_awards':
			$entity = ExternalAwardReceipt::get($entity_id);
			break;
		case 'contributions':
			$entity = Contribution::get($entity_id);
			break;
		case 'student_run_electives':
			$entity = StudentRunElective::get($entity_id);
			break;
		case 'observerships':
			$entity = Observership::get($entity_id);
			break;
		case 'int_acts':
			$entity = InternationalActivity::get($entity_id);
			break;
		case 'critical_enquiry':
			$entity = CriticalEnquiry::get($entity_id);
			break;
		case 'community_based_project':
			$entity = CommunityBasedProject::get($entity_id);
			break;
		case 'research_citations':
			$entity = ResearchCitation::get($entity_id);
			break;
	}
	return $entity;
}

function get_mspr_inputs($type) {
	switch($type) {
		case 'studentships':
			$params = array(
				'title'	=> FILTER_SANITIZE_STRING,
				'year'	=> FILTER_VALIDATE_INT,
			);
			break;
		case 'clineval':
			$params = array(
				'source'	=> FILTER_SANITIZE_STRING,
				'text'		=> FILTER_SANITIZE_STRING
			);
			break;
		case 'internal_awards':
			$params = array(
				'award_id'	=> FILTER_VALIDATE_INT,
				'year'		=> FILTER_VALIDATE_INT
			);
			break;
		case 'external_awards':
			$params = array(
				'title'	=> FILTER_SANITIZE_STRING,
				'body'	=> FILTER_SANITIZE_STRING,
				'terms'	=> FILTER_SANITIZE_STRING,
				'year'	=> FILTER_VALIDATE_INT
			);
			break;
		case 'contributions':
			$params = array(
				'role'			=> FILTER_SANITIZE_STRING,
				'org_event'		=> FILTER_SANITIZE_STRING,
				'start_year'	=> FILTER_VALIDATE_INT,
				'end_year'		=> FILTER_VALIDATE_INT,
				'start_month'	=> FILTER_VALIDATE_INT,
				'end_month'		=> FILTER_VALIDATE_INT
			);
			break;
		case 'student_run_electives':
			$params = array(
				'group_name'	=> FILTER_SANITIZE_STRING,
				'university'	=> FILTER_SANITIZE_STRING,
				'location'		=> FILTER_SANITIZE_STRING,
				'start_year'	=> FILTER_VALIDATE_INT,
				'end_year'		=> FILTER_VALIDATE_INT,
				'start_month'	=> FILTER_VALIDATE_INT,
				'end_month'		=> FILTER_VALIDATE_INT
			);
			break;
		case 'observerships':
			$params = array(
				'title'					=> FILTER_SANITIZE_STRING,
				'site'					=> FILTER_SANITIZE_STRING,
				'location'				=> FILTER_SANITIZE_STRING,
				'start'					=> FILTER_SANITIZE_STRING,
				'end'					=> FILTER_SANITIZE_STRING,
				'preceptor_proxy_id'	=> FILTER_VALIDATE_INT,
				'preceptor_firstname'	=> FILTER_SANITIZE_STRING,
				'preceptor_lastname'	=> FILTER_SANITIZE_STRING,
				'preceptor_prefix'		=> FILTER_SANITIZE_STRING,
				'preceptor_email'		=> FILTER_SANITIZE_STRING
			);
			break;
		case 'int_acts':
			$params = array(
				'title'		=> FILTER_SANITIZE_STRING,
				'site'		=> FILTER_SANITIZE_STRING,
				'location'	=> FILTER_SANITIZE_STRING,
				'start'		=> FILTER_SANITIZE_STRING,
				'end'		=> FILTER_SANITIZE_STRING
			);
			break;
		case 'critical_enquiry':
		case 'community_based_project':
			$params = array(
				'title'			=> FILTER_SANITIZE_STRING,
				'organization'	=> FILTER_SANITIZE_STRING,
				'location'		=> FILTER_SANITIZE_STRING,
				'supervisor'	=> FILTER_SANITIZE_STRING
			);
			break;
		case 'research_citations':
			$params = array(
				'details'				=> FILTER_SANITIZE_STRING,
				'research_citations'	=> array(
											'filter' => FILTER_VALIDATE_INT,
											'flags' => FILTER_REQUIRE_ARRAY
											)
			);
			break;
	}
	$inputs = filter_input_array(INPUT_POST,$params);
	return $inputs;
}

/**
 * adds errors, if found. May modify inputs in the process
 * @param string $type
 * @param array $inputs May modify inputs in the process
 * @param mixed $translator
 */
function process_mspr_inputs($type, array &$inputs, $translator) {
	switch($type) {
		case 'studentships':
			if (!($inputs['title'] && $inputs['year'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'clineval':
			if (!($inputs['text'] && $inputs['source'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'internal_awards':
			if (!($inputs['award_id'] && $inputs['year'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'external_awards':
			if (!($inputs['title'] && $inputs['terms'] && $inputs['body'] && $inputs['year'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'contributions':
			if (!($inputs['role'] && $inputs['org_event'] && $inputs['start_year'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'student_run_electives':
			if (!($inputs['group_name'] && $inputs['university'] && $inputs['location'] && $inputs['start_year'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'observerships':
			if (!checkDateFormat($inputs['start'])) {
				add_error($translator->translate("mspr_observership_invalid_dates"));
			} else {
				$parts = date_parse($inputs['start']);
				$start_ts = mktime(0,0, 0, $parts['month'],$parts['day'], $parts['year']);
				if ($inputs['end'] && checkDateFormat($inputs['end'])) {
					$parts = date_parse($inputs['end']);
					$end_ts = mktime(0,0, 0, $parts['month'],$parts['day'], $parts['year']);
				} else {
					$end_ts = null;
				}
				$inputs['start'] = $start_ts;
				$inputs['end'] = $end_ts;
			}

			if (!$inputs['preceptor_proxy_id']) {
				$inputs['preceptor_proxy_id'] = null;
			}

			if (!$inputs['preceptor_proxy_id'] && !($inputs['preceptor_firstname'] || $inputs['preceptor_lastname'])) {
				add_error($translator->translate("mspr_observership_preceptor_required"));
			}

			if ($inputs['preceptor_proxy_id'] == -1) {
				//special case for "Various"
				$inputs['preceptor_proxy_id'] = 0; //not faculty
				$inputs['preceptor_firstname'] = "Various";
				$inputs['preceptor_lastname'] = "";
			}
			if (!has_error() && !($inputs['title'] && $inputs['site'] && $inputs['location'] && $inputs['start'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'int_acts':
			if (!checkDateFormat($inputs['start'])) {
				add_error($translator->translate("mspr_observership_invalid_dates"));
			} else {
				if (!$inputs['end'] || !checkDateFormat($inputs['end'])) {
					$inputs['end'] = $inputs['start'];
				}
			}

			if (!has_error() && !($inputs['title'] && $inputs['site'] && $inputs['location'] && $inputs['start'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
		break;
		case 'critical_enquiry':
		case 'community_based_project':
			if (!($inputs['title'] && $inputs['organization'] && $inputs['location'] && $inputs['supervisor'])) {
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
		case 'research_citations':
			if (!$inputs['details'] && !is_array($inputs['research_citations'])){
				add_error($translator->translate("mspr_insufficient_info"));
			}
			break;
	}

}