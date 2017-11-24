<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (isset($_GET["qrow"]) && $_GET["qrow"]) {
	$question_identifier = (int) $_GET["qrow"];
}

if (isset($_GET["ids"]) && $_GET["ids"] && ($temp_objective_ids = explode(",", $_GET["ids"])) && (@count($temp_objective_ids))) {
	$objective_ids = $temp_objective_ids;
	$objective_ids_string = "";
	foreach ($objective_ids as $objective_id) {
		$objective_ids_string .= ($objective_ids_string ? ", " : "").$db->qstr(((int)$objective_id));
	}
	
}

$query = "	SELECT a.* FROM `global_lu_objectives` a
			JOIN `objective_audience` b
			ON a.`objective_id` = b.`objective_id`
			AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
			WHERE b.`audience_value` = 'all'
			AND a.`objective_parent` = '0'
			AND a.`objective_active` = '1'";
$objectives = $db->GetAll($query);
if ($objectives) {
	$objective_name = $translate->_("events_filter_controls");
	$hierarchical_name = $objective_name["co"]["global_lu_objectives_name"];
    $nonhierarchical_name = $objective_name["cp"]["global_lu_objectives_name"];
	?>
	<a name="evaluation-question-objectives-section"></a>
	<h2 title="Evaluation Question Objectives Section">Evaluation Question Objectives</h2>
	<div id="evaluation-objectives-section">
		<div style="width: 40%;" class="objectives half left">
			<h3>Curriculum Tag Sets</h3>
			<ul class="tl-objective-list" id="objective_list_0">
			<?php
			foreach($objectives as $objective){ 
				?>
				<li class="objective-container objective-set"
					id="objective_<?php echo $objective["objective_id"]; ?>"
					data-list="<?php echo (((!isset($hierarchical_name) || !$hierarchical_name) && (!isset($nonhierarchical_name) || !$nonhierarchical_name || $nonhierarchical_name != $objective["objective_name"])) || $objective["objective_name"] == $hierarchical_name ? 'hierarchical' : 'flat'); ?>"
					data-id="<?php echo $objective["objective_id"];?>">
					<?php $title = ($objective["objective_code"]? $objective["objective_code"].': '.$objective["objective_name"] : $objective["objective_name"]); ?>
					<div 	class="objective-title"
							id="objective_title_<?php echo $objective["objective_id"]; ?>"
							data-title="<?php echo $title;?>"
							data-id="<?php echo $objective["objective_id"]; ?>"
							data-code="<?php echo $objective["objective_code"]; ?>"
							data-name="<?php echo $objective["objective_name"]; ?>"
							data-description="<?php echo $objective["objective_description"]; ?>">
						<h4><?php echo $title; ?></h4>
					</div>
					<div class="objective-controls" id="objective_controls_<?php echo $objective["objective_id"];?>">
					</div>
					<div 	class="objective-children"
							id="children_<?php echo $objective["objective_id"]; ?>">
							<ul class="objective-list" id="objective_list_<?php echo $objective["objective_id"]; ?>">
							</ul>
					</div>
				</li>
				<?php 		
			} 
			?>
			</ul>
		</div>
		<?php   
		$query = "SELECT a.*, COUNT(b.`objective_id`) AS `mapped` FROM `global_lu_objectives` AS a
					LEFT JOIN `global_lu_objectives` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_active` = '1'
					".(isset($objective_ids_string) && $objective_ids_string ? "AND b.`objective_id` IN (".$objective_ids_string.")" : "AND b.`objective_id` IS NULL")."
					GROUP BY a.`objective_id`
					ORDER BY a.`objective_id` ASC";
		$mapped_objectives = $db->GetAll($query);
		$hierarchical_objectives = array();
		$flat_objectives = array();
		$explicit_evaluation_question_objectives = false;//array();
		$mapped_evaluation_question_objectives = array();
		if ($mapped_objectives) {
			foreach ($mapped_objectives as $objective) {
				if ($objective["mapped"]) {
					$explicit_evaluation_question_objectives[] = $objective;
				} else {
					$flat_objectives[] = $objective;
				}

				if ($objective["mapped"]) {
					$mapped_evaluation_question_objectives[] = $objective;
				}
			}
		}
		?>
		<style type="text/css">
			.mapped-objective{
				padding-left: 30px!important;
			}
		</style>
		<div class="mapped_objectives right droppable" id="mapped_objectives" data-resource-type="evaluation_question" data-resource-id="<?php echo $question_identifier;?>">
			<h3>Mapped Objectives</h3>
			<div id="default_objective_notice">
				<div class="clearfix">
					<ul class="page-action" style="float: right">
						<li class="last">
							<a href="javascript:void(0)" class="mapping-toggle strong-green" data-toggle="show" id="toggle_sets">Map Additional Objectives</a>
						</li>
					</ul>
				</div>												
				<p class="well well-small content-small">
					<strong>Helpful Tip:</strong> Click <strong>Map Additional Objectives</strong> to view the list of available objective sets. Select an objective from the list on the left and it will be mapped to the assessment.
				</p>
			</div>
			<p class="well well-small content-small" id="alternate_objective_notice" style="display: none;">
				<strong>Helpful Tip:</strong> Select an objective set from the list on the left and it will expand to show objectives from that set. Then, click an objective from that list to further expand, or if it has no children, it will be mapped to the evaluation question. Alternatively, click the checkbox to the right of an objective at any level to map it.
			</p>
		<?php
			if($flat_objectives){
			?>
			<div id="clinical-list-wrapper">
				<a name="clinical-objective-list"></a>
				<h2 id="flat-toggle"  title="Clinical Objective List" class="collapsed list-heading">Other Objectives</h2>
				<div id="clinical-objective-list">
					<ul class="objective-list mapped-list" id="mapped_flat_objectives" data-importance="flat">
					<?php
						if ($flat_objectives) {
							foreach($flat_objectives as $objective){
									$title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
								?>
						<li class = "mapped-objective"
							id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
							data-id = "<?php echo $objective["objective_id"]; ?>"
							data-title="<?php echo $title;?>"
							data-description="<?php echo htmlentities($objective["objective_description"]);?>">
							<strong><?php echo $title; ?></strong>
							<div class="objective-description">
								<?php
								$set = fetch_objective_set_for_objective_id($objective["objective_id"]);
								if ($set) {
									echo "Curriculum Tag Set: <strong>".$set["objective_name"]."</strong><br/>";
								}
								?>
								<?php echo $objective["objective_description"];?>
							</div>

							<div class="evaluation-question-objective-controls">
								<input type="checkbox" class="checked-mapped" id="check_mapped_<?php echo $objective['objective_id'];?>" value="<?php echo $objective['objective_id'];?>" <?php echo $objective["mapped"]?' checked="checked"':''; ?>/>
							</div>
						</li>

					<?php
							}
						} ?>
					</ul>
				</div>
			</div>
			<?php
			}
			?>

			<div id="evaluation-question-list-wrapper" <?php echo ($explicit_evaluation_question_objectives)?'':' style="display:none;"';?>>
				<a name="evaluation-question-objective-list"></a>
				<h2 id="evaluation-question-toggle"  title="Evaluation Question Objective List" class="list-heading">Evaluation Question Specific Objectives</h2>
				<div id="evaluation-question-objective-list">
					<ul class="objective-list mapped-list" id="mapped_evaluation_question_objectives" data-importance="evaluation-question">
					<?php
						if ($explicit_evaluation_question_objectives) {
							foreach($explicit_evaluation_question_objectives as $objective){
									$title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
								?>
								<li class = "mapped-objective"
									id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
									data-id = "<?php echo $objective["objective_id"]; ?>"
									data-title="<?php echo $title;?>"
									data-description="<?php echo htmlentities($objective["objective_description"]);?>"
									data-mapped="<?php echo $objective["mapped_to_course"]?1:0;?>">
									<div class="evaluation-question-objective-controls">
										<i	class="icon-remove-sign pull-right objective-remove list-cancel-image"
											id="objective_remove_<?php echo $objective["objective_id"];?>"
											data-id="<?php echo $objective["objective_id"];?>"></i>
									</div>
									<strong><?php echo $title; ?></strong>
									<div class="objective-description">
										<?php
										$set = fetch_objective_set_for_objective_id($objective["objective_id"]);
										if ($set) {
											echo "Curriculum Tag Set: <strong>".$set["objective_name"]."</strong><br/>";
										}
										?>
										<?php echo $objective["objective_description"];?>
									</div>
								</li>
								<?php
							}
						} ?>
					</ul>
				</div>
			</div>
			<select id="checked_objectives_select" name="checked_objectives[]" multiple="multiple" style="display:none;">
			<?php
				if ($mapped_evaluation_question_objectives) {
					foreach($mapped_evaluation_question_objectives as $objective){
						if(in_array($objective["objective_type"], array("curricular_objective","course"))) {
						?>
						<?php $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]); ?>
						<option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
					<?php
						}
					}
				}
			?>
			</select>
			<select id="clinical_objectives_select" name="clinical_presentations[]" multiple="multiple" style="display:none;">
			<?php
				if ($mapped_evaluation_question_objectives) {
					foreach($mapped_evaluation_question_objectives as $objective){
						if(in_array($objective["objective_type"], array("clinical_presentation","event"))) {
						?>
						<?php $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]); ?>
						<option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
					<?php
						}
					}
				}
			?>
			</select>

		</div>
	</div>
	<input type="hidden" id="qrow" value="<?php echo ((int)$question_identifier); ?>" />
	<?php 	
}

?>
