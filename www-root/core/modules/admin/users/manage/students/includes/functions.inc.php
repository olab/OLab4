<?php
require_once("Entrada/mspr/functions.inc.php");

class MSPRAdminController {

	private $_translator;

	private $_user;

	private $type;

	function __construct($translator, User $forUser) {
		$this->_translator = $translator;
		$this->_user = $forUser;
		$this->type="admin";
	}

	public function process() {
		global $ENTRADA_USER;

		$user = $this->_user;
		$translator = $this->_translator;
		$type = $this->type;

		static $valid = array(
								"studentships" => array("add", "remove", "edit"),
								"clineval" => array("add","remove", "edit"),
								"internal_awards" => array("add","remove", "edit"),
								"student_run_electives" => array("add","remove", "edit"),
								"observerships" => array("add","remove", "edit"),
								"int_acts" => array("add","remove", "edit"),
								"external_awards" => array("approve","unapprove","reject", "add", "edit"),
								"contributions" => array("approve","unapprove","reject", "add", "edit"),
								"critical_enquiry" => array("approve","unapprove","reject", "add", "edit"),
								"community_based_project" => array("approve","unapprove","reject", "add", "edit"),
								"research_citations" => array("approve","unapprove","reject", "add", "edit", "resequence")
								);

		$section = filter_input(INPUT_GET, 'mspr-section', FILTER_CALLBACK, array('options'=>'strtolower'));

		if ($section) {
			$params = array(
				'entity_id'	=> FILTER_VALIDATE_INT,
				'action'	=> array('filter' => FILTER_CALLBACK, 'options' => 'strtolower'),
				'comment'	=> FILTER_SANITIZE_STRING,
				'user_id'	=> FILTER_VALIDATE_INT
			);

			$inputs = filter_input_array(INPUT_POST,$params);
			extract($inputs);

			if (!$action) {
				add_error($translator->translate("mspr_no_action"));
			}
			if (!array_key_exists($section, $valid)) {
				add_error($translator->translate("mspr_invalid_section"));
			} else {
				if (!in_array($action, $valid[$section])){
					add_error($translator->translate("mspr_invalid_action"));
				}
			}

			if (($action == "reject") && (MSPR_REJECTION_REASON_REQUIRED)) {
				if (!$comment) {
					add_error($translator->translate("mspr_no_reject_reason"));
				}
			}

			if (!has_error() && (in_array($action, array("add","edit","resequence")))) {
				$inputs = get_mspr_inputs($section);
				process_mspr_inputs($section, $inputs, $translator); //modifies inputs/adds errors
			}


			if (!has_error()) {
				$inputs['user_id'] = $user_id;
				if ($action == "add") {
					if (AUTO_APPROVE_ADMIN_MSPR_SUBMISSIONS) {
						$inputs['status'] = 1;
					}
					switch($section) {
						case "clineval":
							ClinicalPerformanceEvaluation::create($inputs);
							break;
						case "observerships":
							Observership::create($inputs);
							break;
						case 'studentships':
							Studentship::create($inputs);
							break;
						case 'internal_awards':
							InternalAwardReceipt::create($inputs);
							break;
						case 'external_awards':
							ExternalAwardReceipt::create($inputs);
							break;
						case 'contributions':
							Contribution::create($inputs);
							break;
						case 'student_run_electives':
							StudentRunElective::create($inputs);
							break;
						case 'int_acts':
							InternationalActivity::create($inputs);
							break;
						case 'critical_enquiry':
							if (CriticalEnquiry::get($user_id)) {
								add_error($translator->translate("mspr_too_many_critical_enquiry"));
							} else {
								CriticalEnquiry::create($inputs);
							}
							break;
						case 'community_based_project':
							if (CommunityBasedProject::get($user_id)) {
								add_error($translator->translate("mspr_too_many_community_based_project"));
							} else {
								CommunityBasedProject::create($inputs);
							}
							break;
						case 'research_citations':
							ResearchCitation::create($inputs);
							break;
					}
				} elseif ( $action == "resequence") {
					switch($section) {
						case 'research_citations':
                            $research_citations = new ResearchCitations();
                            $research_citations->setSequence($user_id, $inputs['research_citations']);
							break;
					}
				} else { //everything else requires an entity
					if ($entity_id) {
						$entity = get_mspr_entity($section, $entity_id);
						if ($entity) {
							switch($action) {
								case "approve":
									$entity->approve();
									break;
								case "unapprove":
									$entity->unapprove();
									break;
								case "remove":
									$entity->delete();
									break;
								case "edit":
									if ($entity instanceof Approvable){
										if (AUTO_APPROVE_ADMIN_MSPR_EDITS) {
											$inputs['comment'] = "";
											$inputs['status'] = 1;
										} else {
											$inputs['comment'] = $entity->getComment();
											$inputs['status'] = $entity->getStatus();
										}
									}
									$entity->update($inputs);//inputs processed above
									break;
								case "reject":
									if (MSPR_REJECTION_SEND_EMAIL) {
										$sub_info = get_submission_information($entity);
										$reason_type = ((!$comment) ?  "noreason" : "reason");
										$active_user = User::fetchRowByID($ENTRADA_USER->getID());
										if ($active_user && $type) {

											submission_rejection_notification(	$reason_type,
																			array(
																				"firstname" => $user->getFirstname(),
																				"lastname" => $user->getLastname(),
																				"email" => $user->getEmail()),
																			array(
																				"to_fullname" => $user->getFirstname(). " " . $user->getLastname(),
																				"from_firstname" => $active_user->getFirstname(),
																				"from_lastname" => $active_user->getLastname(),
																				"reason" => clean_input($comment,array("notags","specialchars")),
																				"submission_details" => $sub_info,
																				"application_name" => APPLICATION_NAME . " MSPR System"
																				));
										} else {
											add_error($translator->translate("mspr_email_failed"));
										}
									}
									$entity->reject($comment);
									break;
							}
						} else {
							add_error($translator->translate("mspr_invalid_entity"));
						}
					} else {
						add_error($translator->translate("mspr_no_entity"));
					}
				}
			}

			switch($section) {
				case 'studentships':
					$studentships = Studentships::get($user);
					display_status_messages();
					echo display_studentships($studentships, $type);
				break;

				case 'clineval':
					$clinical_evaluation_comments = ClinicalPerformanceEvaluations::get($user);
					display_status_messages();
					echo display_clineval($clinical_evaluation_comments, $type);
				break;

				case 'internal_awards':
					$internal_awards = InternalAwardReceipts::get($user);
					display_status_messages();
					echo display_internal_awards($internal_awards, $type);
				break;

				case 'external_awards':
					$external_awards = ExternalAwardReceipts::get($user);
					display_status_messages();
					echo display_external_awards($external_awards, $type);
				break;

				case 'contributions':
					$contributions = Contributions::get($user);
					display_status_messages();
					echo display_contributions($contributions, $type);
				break;

				case 'student_run_electives':
					$student_run_electives = StudentRunElectives::get($user);
					display_status_messages();
					echo display_student_run_electives($student_run_electives, $type);
				break;

				case 'observerships':
					$observerships = Observerships::get($user);
					display_status_messages();
					echo display_observerships($observerships, $type);
				break;

				case 'int_acts':
					$int_acts = InternationalActivities::get($user);
					display_status_messages();
					echo display_international_activities($int_acts, $type);
				break;

				case 'critical_enquiry':
					$critical_enquiry = CriticalEnquiry::get($user);
					display_status_messages();
					echo display_critical_enquiry($critical_enquiry, $type);
				break;

				case 'community_based_project':
					$community_based_project = CommunityBasedProject::get($user);
					display_status_messages();
					echo display_community_based_project($community_based_project, $type);
				break;

				case 'research_citations':
					$research_citations = ResearchCitations::get($user);
					display_status_messages();
					echo display_research_citations($research_citations, $type);
				break;
			}
		}
	}
}

/**
 * used for getting information about submissions in a simple text format (for email)
 * @param mixed $entity
 */
function get_submission_information($entity) {
	$class_name = get_class($entity);
	switch ($class_name) {
		case 'Contribution':
			$output = "Contribution to Medical School/Student Life\n\n";
			$output .= "Role: " . $entity->getRole() ."\nOrganisation/Event: ".$entity->getOrgEvent() . "\nPeriod: ".$entity->getPeriod()."\n";
			break;
		case 'ResearchCitation':
			$output = "Research\n\n";
			$output .= $entity->getText() ."\n";
			break;
		case 'CriticalEnquiry':
			$output = "Critical Enquiry\n\n";
			$output .= "Title: " . $entity->getTitle() ."\nOrganisation: ".$entity->getOrganization() . "\nLocation: ".$entity->getLocation()."\nSupervisor: " . $entity->getSupervisor() . "\n";
			break;
		case 'CommunityHealthAndEpidemiology':
			$output = "Community-Based Project\n\n";
			$output .= "Title: " . $entity->getTitle() ."\nOrganisation: ".$entity->getOrganization() . "\nLocation: ".$entity->getLocation()."\nSupervisor: " . $entity->getSupervisor() . "\n";
			break;
		case 'ExternalAward':
			$output = "External Award\n\n";
			$award = $entity->getAward();
			$output .= "Title: " . $award->getTitle() ."\nTerms: ".$award->getTerms() . "\nAwarding Body: ".$award->getAwardingBody()."\nYear Awarded: " . $entity->getAwardYear() . "\n";
			break;
		default:
			$output = "Unknown";

	}
	return $output;
}

function process_mspr_details($translator,$section) {
	$action = clean_input((isset($_POST['action']) ? $_POST['action'] : ""), array("lower"));
	if (!$action) {
		return;
	}
	switch($action) {
		case 'add':
			$user_id = clean_input((isset($_POST['user_id']) ? $_POST['user_id'] : 0), array("int"));
			$details = clean_input((isset($_POST['details']) ? $_POST['details'] : "" ), array("notags"));
			if (!$user_id) {
				add_error($translator->translate("mspr_invalid_user_info"));
			}
			if (!$details) {
				add_error($translator->translate("mspr_no_details"));
			}
			if (!has_error()){
				switch($section) {
					case 'leaves_of_absence':
						LeaveOfAbsence::create($user_id, $details);
						break;
					case 'disciplinary_actions':
						DisciplinaryAction::create($user_id,$details);
						break;
					case 'formal_remediation':
						FormalRemediation::create($user_id,$details);
						break;
				}
			}
			break;
		case 'remove':
			$entity_id = clean_input((isset($_POST['entity_id']) ? $_POST['entity_id'] : 0), array("int"));
			if (!$entity_id) {
				add_error($translator->translate("mspr_no_entity"));
			}
			if (!has_error()) {
				switch($section) {
					case 'leaves_of_absence':
						$entity = LeaveOfAbsence::get($entity_id);
						break;
					case 'disciplinary_actions':
						$entity = DisciplinaryAction::get($entity_id);
						break;
					case 'formal_remediation':
						$entity = FormalRemediation::get($entity_id);
						break;
				}
				if (!$entity) {
					add_error($translator->translate("mspr_invalid_entity"));
				}
				if (!has_error()) {
					$entity->delete();
				}
			}
			break;
	}

}

/**
 * Sends email based on the specified type using templates from $ENTRADA_TEMPLATE->absolute()/email directory
 * @param string $type One of "reason", "noreason"
 * @param array $to associative array consisting of firstname, lastname, and email
 * @param array $keywords Associative array of keywords mapped to the replacement contents
 */
function submission_rejection_notification($type, $to = array(), $keywords = array()) {
	global $AGENT_CONTACTS, $ENTRADA_TEMPLATE;
	if (!is_array($to) || !isset($to["email"]) || !valid_address($to["email"]) || !isset($to["firstname"]) || !isset($to["lastname"])) {
		application_log("error", "Attempting to send a submission_rejection_notification() however the recipient information was not complete.");

		return false;
	}

	if (!in_array($type, array("reason", "noreason"))) {
		application_log("error", "Encountered an unrecognized notification type [".$type."] when attempting to send a submission_rejection_notification().");

		return false;
	}


	$xml_file = $ENTRADA_TEMPLATE->absolute()."/email/mspr-rejection-".$type.".xml";

	try {
		require_once("Classes/utility/Template.class.php");
		require_once("Classes/utility/TemplateMailer.class.php");
		$template = new Template($xml_file);
		$mail = new TemplateMailer(new Zend_Mail());
		$mail->addHeader("X-Section", "MSPR Module", true);

		$from = array("email"=>$AGENT_CONTACTS["agent-notifications"]["email"], "firstname"=> "MSPR System","lastname"=>"");
		if ($mail->send($template,$to,$from,DEFAULT_LANGUAGE,$keywords)) {
			return true;
		} else {
			add_notice("We were unable to e-mail a task notification <strong>".$to["email"]."</strong>.<br /><br />A system administrator was notified of this issue, but you may wish to contact this individual manually and let them know their task verification status.");
			application_log("error", "Unable to send task verification notification to [".$to["email"]."] / type [".$type."]. Zend_Mail said: ".$mail->ErrorInfo);
		}

	} catch (Exception $e) {
		application_log("error", "Unable to load the XML file [".$xml_file."] or the XML file did not contain the language requested [".DEFAULT_LANGUAGE."], when attempting to send a regional education notification.");
	}

	return false;
}