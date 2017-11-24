<?php

require_once("Entrada/mspr/functions.inc.php");

class MSPRPublicController {
	
	private $_translator;
	
	private $_user;
	
	private $type;
	
	function __construct($translator, User $forUser) {
		$this->_translator = $translator;
		$this->_user = $forUser;
		$this->type="public";
	}
	
	public function process() {
		$user = $this->_user;
		$translator = $this->_translator;
		$type = $this->type;

		static $valid = array(
								"external_awards" => array("add","remove", "edit"),
								"contributions" => array("add","remove", "edit"),
								"critical_enquiry" => array("add","remove", "edit"),
								"community_based_project" => array("add","remove", "edit"),
								"research_citations" => array("add","remove","edit", "resequence"),
								"observerships" => array("add","remove","edit", "resequence")
								);
								
		$section = filter_input(INPUT_GET, 'mspr-section', FILTER_CALLBACK, array('options'=>'strtolower'));
		
		if ($section) {
			
			$params = array(
				'entity_id'	=> FILTER_VALIDATE_INT,
				'action'	=> array('filter' => FILTER_CALLBACK, 'options' => 'strtolower'),
				'user_id'	=> FILTER_VALIDATE_INT
			);
			
			$inputs = filter_input_array(INPUT_POST,$params);
			extract($inputs);

			if (!isset($action)) {
				add_error($translator->translate("mspr_no_action"));
			}
			if (!array_key_exists($section, $valid)) {
				add_error($translator->translate("mspr_invalid_section"));	
			} else {
				if (!in_array($action, $valid[$section])){
					add_error($translator->translate("mspr_invalid_action"));
				}
			}
			
			if (!has_error() && (in_array($action, array("add","edit","resequence")))) {
				$inputs = get_mspr_inputs($section);
				process_mspr_inputs($section, $inputs, $translator); //modifies inputs/adds errors
			}
			
			if (!has_error()) {
				$inputs['user_id'] = $user_id;
				if ($action == "add") {
					switch($section) {
						case 'external_awards':
							ExternalAwardReceipt::create($inputs);
							break;
						case 'contributions':
							Contribution::create($inputs);
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
						case 'observerships':
							$observership = Observership::create($inputs);
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
					if (isset($entity_id)) {
						$entity = get_mspr_entity($section, $entity_id);
						if ($entity) {
							switch($action) {
								case "remove":
									$entity->delete();
									break;
								case "edit":
									if ($entity instanceof Approvable) {
										$inputs['comment'] = "";
										$inputs['status'] = 0; //set to unapproved.
									}
									$entity->update($inputs);//inputs processed above
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
			
				case 'observerships':
					$observerships = Observerships::get($user);
					display_status_messages();
					echo display_observerships($observerships, "public");
				break;
			}
		}
	}
	
	private function add_research($user_id) {
		$translator = $this->_translator;

		$params = array(
			'details'		=> FILTER_SANITIZE_STRING
		);
		
		$inputs = filter_input_array(INPUT_POST,$params);
		extract($inputs);
		
		if (!has_error()) {
			if (isset($user_id) && isset($details)) {
				ResearchCitation::create($user_id,$details);
			} else {
				add_error($translator->translate("mspr_insufficient_info"));
			}
		}
	}
	
	private function edit_research(ResearchCitation $entity) {
		$translator = $this->_translator;
		
		$params = array(
			'details'		=> FILTER_SANITIZE_STRING
		);
		
		$inputs = filter_input_array(INPUT_POST,$params);
		extract($inputs);

        if (isset($entity) && isset($details)) {
			$entity->update($details);
		}  else {
			add_error($translator->translate("mspr_insufficient_info"));
		}
	}
	
	private function resequence_research($user_id) {
		$translator = $this->_translator;
		
		$params = array(
			'research_citations'	=> array(
											'filter' => FILTER_VALIDATE_INT,
											'flags' => FILTER_REQUIRE_ARRAY
											)
		);
		
		$inputs = filter_input_array(INPUT_POST,$params);
		extract($inputs);
		
		$user = User::fetchRowByID($user_id);
		
		if (isset($user) && isset($research_citations) && is_array($research_citations)) {
			ResearchCitations::setSequence($user,$research_citations);
		} else {
			add_error($translator->translate("mspr_insufficient_info"));
		}
	}
}

/**
 * Returns a table containing received internal academic awards
 * @param InternalAwardReceipts $received_awards
 * @return string 
 */
function display_internal_awards_profile(InternalAwardReceipts $receipts = null) {
	return display_internal_awards($receipts,"public");
}

/**
 * Returns a table containing received studentships and details
 * @param Studentships $studentships
 * @return string
 */
function display_studentships_profile(Studentships $studentships = null) {
	return display_studentships($studentships,"public");
}

/**
 * Outputs a table with awards for a given student. Includes profile functions
 * @param ExternalAwardReceipts $receipts
 * @return string
 */
function display_external_awards_profile(ExternalAwardReceipts $receipts, $view_mode = false) {
	return display_external_awards($receipts, "public", $view_mode);
}

/**
 * Returns a table containing submitted contributions with approval status indicated.
 * @param Contributions $contributions
 * @return string
 */
function display_contributions_profile(Contributions $contributions,$view_mode=false) {
	return display_contributions($contributions,"public",$view_mode);
}

/**
 * Returns a table containing clinical performance evaluation comments.
 * @param ClinicalPerformanceEvaluations $clinevals
 * @return string
 */
function display_clineval_profile(ClinicalPerformanceEvaluations $clinevals = null) {
	return display_clineval($clinevals,"public");
}

/**
 * Returns a table containing student-run-electives.
 * @param StudentRunElectives $sres
 * @return string
 */
function display_student_run_electives_profile(StudentRunElectives $sres=null) {
	return display_student_run_electives($sres,"public");
}

/**
 * Returns an HTML table containing Observerships.
 * @param Observerships $obss
 * @return string
 */
function display_observerships_profile(Observerships $obss=null) {
	return display_observerships($obss,"public");
}

/**
 * Returns an HTML table containing Observerships.
 * @param Observerships $obss
 * @return string
 */
function display_international_activities_profile(InternationalActivities $int_acts) {
	return display_international_activities($int_acts,"public");
}

/**
 * Returns a single-row-table (for consistency of formatting and markup) containing the critical entry project details.
 * @param CriticalEnquiry $critical_enquiry
 * @return string
 */
function display_supervised_project_profile(SupervisedProject $project = null) {
	return display_supervised_project($project,"public");
}

function display_research_citations_profile(ResearchCitations $research_citations, $view_mode=false) {
	return display_research_citations($research_citations,"public",$view_mode);
}