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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

/**
 * Evaluation class with basic information and access to evaluation related info
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@quensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 */
class Classes_Evaluation {
	private $id;

	/**
	 * Returns an Evaluation object created using the array inputs supplied
	 * @param array $arr
	 * @return Evaluation
	 */
	public static function fromArray(array $arr, Classes_Evaluation $evaluation) {
		$evaluation->id = $arr['id'];
		return $evaluation;
	}

	public static function getEditQuestionControls($question_data) {
		global $db, $HEAD, $PROCESSED, $ENTRADA_USER, $translate;
		if (isset($question_data["questiontype_id"]) && $question_data["questiontype_id"]) {
			$query = "SELECT * FROM `evaluations_lu_questiontypes`
						WHERE `questiontype_id` = ".$db->qstr($question_data["questiontype_id"]);
			$questiontype = $db->GetRow($query);
		} else {
			$questiontype = array("questiontype_shortname" => "matrix_single");
		}
		switch ($questiontype["questiontype_shortname"]) {
			case "rubric" :
				?>
				<tr>
					<td style="vertical-align: top">
						<label for="rubric_title" class="form-nrequired">Grouped Item Title</label>
					</td>
					<td>
						<input type="text" id="rubric_title" name="rubric_title" style="width: 330px;" value="<?php echo ((isset($question_data["rubric_title"])) ? clean_input($question_data["rubric_title"], "encode") : ""); ?>">
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="rubric_description" class="form-nrequired">Grouped Item Description</label>
					</td>
					<td>
						<textarea id="rubric_description" class="expandable" name="rubric_description" style="width: 98%; height:0"><?php echo ((isset($question_data["rubric_description"])) ? clean_input($question_data["rubric_description"], "encode") : ""); ?></textarea>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="columns_count" class="form-required">Number of Columns</label>
					</td>
					<td>
						<select name="columns_count" id="columns_count" onchange="updateColumns(this.options[this.selectedIndex].value, $('categories_count').value)">
							<option value="2"<?php echo (isset($question_data["columns_count"]) && $question_data["columns_count"] == 2 ? " selected=\"selected\"" : ""); ?>>2</option>
							<option value="3"<?php echo ((isset($question_data["columns_count"]) && $question_data["columns_count"] == 3) || !isset($question_data["columns_count"]) || !$question_data["columns_count"] ? " selected=\"selected\"" : ""); ?>>3</option>
							<option value="4"<?php echo (isset($question_data["columns_count"]) && $question_data["columns_count"] == 4 ? " selected=\"selected\"" : ""); ?>>4</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="allow_comments" class="form-required">Allow Comments</label>
					</td>
					<td>
						<input type="checkbox" id="allow_comments" name="allow_comments"<?php echo (isset($question_data["allow_comments"]) && $question_data["allow_comments"] ? " checked=\"checked\"" : ""); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="padding-top: 5px; vertical-align: top">
						<label for="response_text_0" class="form-required">Column Labels</label>
					</td>
					<td>
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="padding-top: 5px; vertical-align: top">
						<input type="hidden" value="<?php echo (isset($question_data["categories_count"]) && (int) $question_data["categories_count"] ? $question_data["categories_count"] : 1); ?>" name="categories_count" id="categories_count" />
					</td>
					<td style="padding-top: 5px">
						<table class="form-question" cellspacing="0" cellpadding="2" border="0" summary="Form Question Responses">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 57%" />
							<col style="width: 20%" />
							<col style="width: 20%" />
						</colgroup>
						<thead>
							<tr>
								<td colspan="2">&nbsp;</td>
								<td class="center" style="font-weight: bold; font-size: 11px">Descriptor</td>
								<td class="center" style="font-weight: bold; font-size: 11px">Minimum Pass</td>
							</tr>
						</thead>
						<tbody id="columns_list">
							<?php
								echo Classes_Evaluation::getRubricColumnList($question_data);
							?>
						</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<h2>Categories</h2>
						<a class="btn btn-small btn-success pull-right" style="cursor: pointer; margin-top: -40px;" onclick="loadCategories($('columns_count').options[$('columns_count').selectedIndex].value, (parseInt($('categories_count').value) + 1), 0)"><i class="icon-plus-sign icon-white"></i> Add Additional Category</a>
					</td>
				</tr>
				<tr>
					<td style="padding-top: 5px" colspan="2">
						<table class="form-question" id="category_list" cellspacing="0" cellpadding="2" border="0" summary="Form Question Responses">
						<?php
							echo Classes_Evaluation::getRubricCategoryList($question_data);
						?>
						</table>
					</td>
				</tr>
				<?php
			break;
			case "free_text" :
				?>
				<tr>
					<td style="vertical-align: top">
						<label for="question_code" class="form-nrequired">Question Code</label>
					</td>
					<td>
						<input type="text" id="question_code" name="question_code" value="<?php echo (isset($question_data["question_code"]) && $question_data["question_code"] ? clean_input($question_data["question_code"], "encode") : ""); ?>" />
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="question_text" class="form-required">Question Text</label>
					</td>
					<td>
						<textarea id="question_text" class="expandable" name="question_text" style="width: 100%; height:0"><?php echo ((isset($question_data["question_text"])) ? clean_input($question_data["question_text"], "encode") : ""); ?></textarea>
					</td>
				</tr>
				<tr>
					<td style="padding-top: 5px; vertical-align: top">
						<label class="form-required">Available Responses</label>
					</td>
					<td>
						<?php
							add_notice("The evaluators will be asked to enter a free text comment as a response to this question.");
							echo display_notice();
						?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div id="objectives_1_list" class="hidden">
							<?php
							$objective_ids_string = "";
							if (isset($question_data["objective_ids"]) && @count($question_data["objective_ids"])) {
								foreach ($question_data["objective_ids"] as $objective_id) {
									$objective_ids_string .= ($objective_ids_string ? ", " : "").((int)$objective_id);
									?>
									<input type="hidden" class="objective_ids_1" id="objective_ids_1_<?php echo $objective_id; ?>" name="objective_ids_1[]" value="<?php echo $objective_id; ?>" />
									<?php
								}
							}
							?>
							<input type="hidden" name="objective_ids_string_1" id="objective_ids_string_1" value="<?php echo ($objective_ids_string ? $objective_ids_string : ""); ?>" />
							<input type="hidden" id="qrow" value="1" />
						</div>
						<?php
						$question_identifier = 1;
						require_once("api/evaluations-objectives-list.api.php");
						?>
					</td>
				</tr>
				<?php
			break;
			case "descriptive_text" :
				?>
				<tr>
					<td style="vertical-align: top">
						<label for="question_code" class="form-nrequired">Question Code</label>
					</td>
					<td>
						<input type="text" id="question_code" name="question_code" value="<?php echo (isset($question_data["question_code"]) && $question_data["question_code"] ? clean_input($question_data["question_code"], "encode") : ""); ?>" />
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="question_text" class="form-required">Question Text</label>
					</td>
					<td>
						<textarea id="question_text" class="expandable" name="question_text" style="width: 100%; height:0"><?php echo ((isset($question_data["question_text"])) ? clean_input($question_data["question_text"], "encode") : ""); ?></textarea>
					</td>
				</tr>
				<?php
			break;
			case "selectbox" :
				?>
				<tr>
					<td style="vertical-align: top">
						<label for="question_text" class="form-required">Question Text</label>
					</td>
					<td>
						<textarea id="question_text" class="expandable" name="question_text" style="width: 98%; height:0"><?php echo ((isset($question_data["question_text"])) ? clean_input($question_data["question_text"], "encode") : ""); ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="allow_comments" class="form-required">Allow Comments</label>
					</td>
					<td>
						<input type="checkbox" id="allow_comments" name="allow_comments"<?php echo (isset($question_data["allow_comments"]) && $question_data["allow_comments"] ? " checked=\"checked\"" : ""); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="padding-top: 5px; vertical-align: top">
						<label for="response_text_0" class="form-required">Available Responses</label>
					</td>
					<td style="padding-top: 5px">
						<table class="form-question" id="response_list" cellspacing="0" cellpadding="2" border="0" summary="Form Question Responses">
							<?php
							echo Classes_Evaluation::getQuestionResponseList($question_data, $questiontype["questiontype_shortname"]);
							?>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div id="objectives_1_list" class="hidden">
							<?php
							$objective_ids_string = "";
							if (isset($question_data["objective_ids"]) && @count($question_data["objective_ids"])) {
								foreach ($question_data["objective_ids"] as $objective_id) {
									$objective_ids_string .= ($objective_ids_string ? ", " : "").((int)$objective_id);
									?>
									<input type="hidden" class="objective_ids_1" id="objective_ids_1_<?php echo $objective_id; ?>" name="objective_ids_1[]" value="<?php echo $objective_id; ?>" />
									<?php
								}
							}
							?>
							<input type="hidden" name="objective_ids_string_1" id="objective_ids_string_1" value="<?php echo ($objective_ids_string ? $objective_ids_string : ""); ?>" />
							<input type="hidden" id="qrow" value="1" />
						</div>
						<?php
						$question_identifier = 1;
						require_once("api/evaluations-objectives-list.api.php");
						?>
					</td>
				</tr>
				<?php
			break;
			case "matrix_single" :
            case "vertical_matrix" :
			default :
				?>
				<tr>
					<td style="vertical-align: top">
						<label for="question_code" class="form-nrequired">Question Code</label>
					</td>
					<td>
						<input type="text" id="question_code" name="question_code" value="<?php echo (isset($question_data["question_code"]) && $question_data["question_code"] ? clean_input($question_data["question_code"], "encode") : ""); ?>" />
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="question_text" class="form-required">Question Text</label>
					</td>
					<td>
						<textarea id="question_text" class="expandable" name="question_text" style="width: 98%; height:0"><?php echo ((isset($question_data["question_text"])) ? clean_input($question_data["question_text"], "encode") : ""); ?></textarea>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="responses_count" class="form-required">Number of Responses</label>
					</td>
					<td>
						<select name="responses_count" id="responses_count" onchange="updateResponses(this.options[this.selectedIndex].value, jQuery('#questiontype_id').val())">
                            <?php
                            if ($questiontype["questiontype_shortname"] == "vertical_matrix") {
                                for ($i = 2; $i <= 10; $i++) {
									echo "<option value=\"" . $i . "\"" . (isset($question_data["responses_count"]) && $question_data["responses_count"] == $i || (!isset($question_data["responses_count"]) && $i == 4) ? " selected=\"selected\"" : "") . ">" . $i . "</option>";
                                }
                            } else {
                                ?>
                                <option value="2"<?php echo (isset($question_data["responses_count"]) && $question_data["responses_count"] == 2 ? " selected=\"selected\"" : ""); ?>>2</option>
                                <option value="3"<?php echo ((isset($question_data["responses_count"]) && $question_data["responses_count"] == 3) ? " selected=\"selected\"" : ""); ?>>3</option>
                                <option value="4"<?php echo ((isset($question_data["responses_count"]) && $question_data["responses_count"] == 4) || !isset($question_data["responses_count"]) || !$question_data["responses_count"] ? " selected=\"selected\"" : ""); ?>>4</option>
                                <option value="5"<?php echo ((isset($question_data["responses_count"]) && $question_data["responses_count"] == 5) ? " selected=\"selected\"" : ""); ?>>5</option>
                                <?php
                            }
                            ?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top">
						<label for="allow_comments" class="form-required">Allow Comments</label>
					</td>
					<td>
						<input type="checkbox" id="allow_comments" name="allow_comments"<?php echo (isset($question_data["allow_comments"]) && $question_data["allow_comments"] ? " checked=\"checked\"" : ""); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						&nbsp;
					</td>
				</tr>
				<tr>
					<td style="padding-top: 5px; vertical-align: top">
						<label for="response_text_0" class="form-required">Available Responses</label>
					</td>
					<td style="padding-top: 5px">
						<table class="form-question" id="response_list" cellspacing="0" cellpadding="2" border="0" summary="Form Question Responses">
							<?php
							echo Classes_Evaluation::getQuestionResponseList($question_data, $questiontype["questiontype_shortname"]);
							?>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div id="objectives_1_list" class="hidden">
							<?php
							$objective_ids_string = "";
							if (isset($question_data["objective_ids"]) && @count($question_data["objective_ids"])) {
								foreach ($question_data["objective_ids"] as $objective_id) {
									$objective_ids_string .= ($objective_ids_string ? ", " : "").((int)$objective_id);
									?>
									<input type="hidden" class="objective_ids_1" id="objective_ids_1_<?php echo $objective_id; ?>" name="objective_ids_1[]" value="<?php echo $objective_id; ?>" />
									<?php
								}
							}
							?>
							<input type="hidden" name="objective_ids_string_1" id="objective_ids_string_1" value="<?php echo ($objective_ids_string ? $objective_ids_string : ""); ?>" />
							<input type="hidden" id="qrow" value="1" />
						</div>
						<?php
						$question_identifier = 1;
						require_once("api/evaluations-objectives-list.api.php");
						?>
					</td>
				</tr>
				<?php
			break;
		}
	}

	public static function getRubricCategoryList($question_data) {
		if ($question_data) {
			?>
			<colgroup>
				<col style="width: 20%" />
				<col style="width: 57%" />
				<col style="width: 20%" />
			</colgroup>
			<?php
			foreach (range(1, (isset($question_data["categories_count"]) && (int) $question_data["categories_count"] ? (int) $question_data["categories_count"] : 1)) as $rownum) {
				?>
				<tbody style="<?php echo ($rownum % 2 == 1 ? "background-color: #EEE;" : "background-color: #FFF;"); ?>">
					<tr>
						<td style="padding-top: 10px;">
							<label for="category_<?php echo $rownum; ?>" class="form-required">Category Title</label>
						</td>
						<td colspan="2" style="padding: 10px 4px 0px 4px;">
							<div id="objectives_<?php echo $rownum; ?>_list" class="hidden">
								<?php
								$objective_ids_string = "";
								if (isset($question_data["evaluation_rubric_categories"][$rownum]["objective_ids"]) && @count($question_data["evaluation_rubric_categories"][$rownum]["objective_ids"])) {
									foreach ($question_data["evaluation_rubric_categories"][$rownum]["objective_ids"] as $objective_id) {
										$objective_ids_string .= ($objective_ids_string ? ", " : "").((int)$objective_id);
										?>
										<input type="hidden" class="objective_ids_<?php echo $rownum; ?>" id="objective_ids_<?php echo $rownum; ?>_<?php echo $objective_id; ?>" name="objective_ids_<?php echo $rownum; ?>[]" value="<?php echo $objective_id; ?>" />
										<?php
									}
								}
								?>
								<input type="hidden" name="objective_ids_string_<?php echo $rownum; ?>" id="objective_ids_string_<?php echo $rownum; ?>" value="<?php echo ($objective_ids_string ? $objective_ids_string : ""); ?>" />
							</div>
							<input class="category" type="text" id="category_<?php echo $rownum; ?>" name="category[<?php echo $rownum; ?>]" style="width: 79%" value="<?php echo ((isset($question_data["evaluation_rubric_categories"][$rownum]["category"])) ? clean_input($question_data["evaluation_rubric_categories"][$rownum]["category"], "encode") : ""); ?>" />
							<?php
							echo "<div class=\"controls\" style=\"float: right;\">\n";
							echo "	<a id=\"question_objectives_".$rownum."\" class=\"pointer question-controls-objectives\" onclick=\"openObjectiveDialog(".$rownum.")\"><i class=\"icon-book\" alt=\"Choose Objectives\" title=\"Choose Objectives\"></i></a>";
							echo "	<a id=\"question_delete_".$rownum."\" class=\"pointer question-controls-delete\" onclick=\"loadCategories($('columns_count').options[$('columns_count').selectedIndex].value, (parseInt($('categories_count').value) - 1), ".$rownum.")\" title=\"".$rownum."\"><i class=\"icon-remove-sign\" alt=\"Delete Question\" title=\"Delete Question\"></i></a>";
							echo "</div>\n";
							?>
						</td>
					</tr>
					<tr>
						<td style="padding-top: 10px;">
							<label for="category_description_<?php echo $rownum; ?>" class="form-nrequired">Category Description</label>
						</td>
						<td colspan="2" style="padding: 10px 4px 0px 4px;">
							<textarea class="expandable" id="category_description_<?php echo $rownum; ?>" name="category_description[<?php echo $rownum; ?>]" style="width: 79%"><?php echo ((isset($question_data["evaluation_rubric_categories"][$rownum]["category_description"])) ? clean_input($question_data["evaluation_rubric_categories"][$rownum]["category_description"], "encode") : ""); ?></textarea>
						</td>
					</tr>
					<tr>
						<td>
							&nbsp;
						</td>
						<td colspan="2">
							<div class="rubric_criteria_list" style="padding-right: 20px;">
                            <?php
							foreach (range(1, (isset($question_data["columns_count"]) && (int) $question_data["columns_count"] ? (int) $question_data["columns_count"] : 3)) as $colnum) {
								?>
								<div style="width: 100%; text-align: right; margin: 10px 0px; float: right;">
									<div style="position: absolute;">
										<label style="text-align: left; vertical-align: top;" class="form-nrequired" for="category_<?php echo $rownum."_criteria_".$colnum; ?>">Column <?php echo $colnum; ?> Criteria</label>
									</div>
									<textarea class="criteria_<?php echo $rownum; ?>" style="width: 65%;" id="criteria_<?php echo $colnum; ?>" name="criteria[<?php echo $rownum."][".$colnum; ?>]"><?php echo (isset($question_data["evaluation_rubric_category_criteria"][$rownum][$colnum]["criteria"]) && $question_data["evaluation_rubric_category_criteria"][$rownum][$colnum]["criteria"] ? html_encode($question_data["evaluation_rubric_category_criteria"][$rownum][$colnum]["criteria"]) : ""); ?></textarea>
								</div>
								<?php
							}
							?>
							</div>
						</td>
					</tr>
				</tbody>
				<?php
			}
		}
	}

	public static function getRubricColumnList($question_data) {
		if ($question_data) {
			foreach (range(1, (isset($question_data["columns_count"]) && (int) $question_data["columns_count"] ? (int) $question_data["columns_count"] : 3)) as $number) {
				$minimum_passing_level = (((!isset($question_data["evaluation_question_responses"][$number]["minimum_passing_level"]) && ($number == 1)) || (isset($question_data["evaluation_question_responses"][$number]["minimum_passing_level"]) && (int) $question_data["evaluation_question_responses"][$number]["minimum_passing_level"])) ? true : false);
				?>
				<tr>
					<td style="padding-top: 13px">
						<label for="response_text_<?php echo $number; ?>" class="form-required"><?php echo $number; ?></label>
					</td>
					<td style="padding-top: 10px">
						<input type="text" class="response_text" id="response_text_<?php echo $number; ?>" name="response_text[<?php echo $number; ?>]" style="width: 99%" value="<?php echo ((isset($question_data["evaluation_question_responses"][$number]["response_text"])) ? clean_input($question_data["evaluation_question_responses"][$number]["response_text"], "encode") : ""); ?>" />
					</td>
                    <td class="minimumPass center" style="padding-top: 10px">
                        <a href="javascript: openDescriptorDialog(<?php echo $number; ?>, jQuery('#response_descriptor_<?php echo $number; ?>').val())"><i class="icon-tag"></i></a>
                        <input type="hidden" id="response_descriptor_<?php echo $number; ?>" name="response_descriptor_id[<?php echo $number; ?>]" value="<?php echo ((isset($question_data["evaluation_question_responses"][$number]["erdescriptor_id"])) ? clean_input($question_data["evaluation_question_responses"][$number]["erdescriptor_id"], "int") : ""); ?>" />
                    </td>
					<td class="minimumPass center" style="padding-top: 10px">
						<input type="radio" name="minimum_passing_level" id="fail_indicator_<?php echo $number; ?>" value="<?php echo $number; ?>"<?php echo (($minimum_passing_level) ? " checked=\"true\"" : ""); ?> />
					</td>
				</tr>
				<?php
			}
		}
	}

	public static function getPreceptorArray($evaluation_id, $event_id, $evaluator_proxy_id) {
		global $db;

		$query	= "SELECT b.`preceptor_proxy_id` FROM `evaluation_progress` AS a
					JOIN `evaluation_progress_clerkship_events` AS b
					ON a.`eprogress_id` = b.`eprogress_id`
					WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
					AND a.`proxy_id` = ".$db->qstr($evaluator_proxy_id)."
					AND b.`event_id` = ".$db->qstr($event_id);
		$temp_preceptors = $db->GetAll($query);
		$preceptor_proxy_ids_string = "";
		if ($temp_preceptors) {
			foreach ($temp_preceptors as $temp_preceptor) {
				$preceptor_proxy_ids_string .= ($preceptor_proxy_ids_string ? ", " : "").$db->qstr($temp_preceptor["preceptor_proxy_id"]);
			}
		}
		$query	= "SELECT b.`department_id`, d.`id` AS `proxy_id`, CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`, e.`department_title`
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					JOIN `".CLERKSHIP_DATABASE."`.`category_departments` AS b
					ON a.`category_id` = b.`category_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_departments` AS c
					ON c.`dep_id` = b.`department_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
					ON d.`id` = c.`user_id`
					LEFT JOIN `".AUTH_DATABASE."`.`departments` AS e
					ON e.`department_id` = c.`dep_id`
					WHERE a.`event_id` = ".$db->qstr($event_id)."
					".($preceptor_proxy_ids_string ? "AND d.`id` NOT IN (".$preceptor_proxy_ids_string.")" : "")."
					ORDER BY d.`lastname` ASC, d.`firstname` ASC";
		$preceptors = $db->GetAll($query);
		if (!$preceptors) {
			$query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname` FROM `".AUTH_DATABASE."`.`user_data` AS a
						JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON a.`id` = b.`user_id`
						WHERE b.`group` = 'faculty'
						".($preceptor_proxy_ids_string ? "AND a.`id` NOT IN (".$preceptor_proxy_ids_string.")" : "")."
						GROUP BY a.`id`";
			$preceptors = $db->GetAll($query);
		}

		return ($preceptors ? $preceptors : false);
	}

	public static function getPreceptorSelect($evaluation_id, $event_id, $evaluator_proxy_id, $preceptor_proxy_id = 0) {
		global $db;

		$output = "";

		$preceptors = Classes_Evaluation::getPreceptorArray($evaluation_id, $event_id, $evaluator_proxy_id);

		if ($preceptors) {
			$output = "<select id=\"preceptor_proxy_id\" name=\"preceptor_proxy_id\" onchange=\"fetchTargetDetails(this.value)\">\n";
			$output .= "<option value=\"0\">-- Select a preceptor --</option>\n";
			foreach ($preceptors as $preceptor) {
				if ($preceptor["proxy_id"]) {
					$output .= "<option value=\"".$preceptor["proxy_id"]."\"".($preceptor_proxy_id && $preceptor_proxy_id == $preceptor["proxy_id"] ? " selected=\"selected\"" : "").">".$preceptor["fullname"]."</option>";
				}
			}
            $output .= "<option value=\"other\"".((isset($preceptor_proxy_id)) && $preceptor_proxy_id === "other" ? " selected=\"selected\"" : "").">Other Teacher</option>\n";
			$output .= "</select>\n";
		}
		return $output;
	}

	public static function getQuestionResponseList($question_data, $questiontype) {
        global $HEAD, $ONLOAD;

		if ($question_data) {
            switch ($questiontype) {
                case "selectbox" :
                    $HEAD[] = "<style type=\"text/css\"> .editor_field { width: 100%; }</style>";
                    $HEAD[] = "
                    <script type=\"text/javascript\">
                        var responses = [];
                        function addResponse () {
                            var number = jQuery('.sortable').length + 1;
                            var new_response = '<tr class=\"sortable\">';
                            new_response += '        <td style=\"padding-top: 13px\">';
                            new_response += '            <label for=\"response_text_' + number + '\" class=\"form-required\">' + number + '</label>';
                            new_response += '        </td>';
                            new_response += '        <td style=\"padding-top: 10px\">';
                            new_response += '            <span class=\"response_text\" id=\"response_text_' + number + '\" name=\"response_text[' + number + ']\" style=\"width: 99%\"></span>';
                            new_response += '        </td>';
                            new_response += '        <td class=\"minimumPass center\" style=\"padding-top: 10px\">';
                            new_response += '            <a href=\"javascript: openDescriptorDialog(' + number + ', 0)\"><i class=\"icon-tag\"></i></a>';
                            new_response += '            <input type=\"hidden\" id=\"response_descriptor_' + number + '\" name=\"response_descriptor_id[' + number + ']\" value=\"\" />';
                            new_response += '        </td>';
                            new_response += '        <td class=\"minimumPass center\" style=\"padding-top: 10px\">';
                            new_response += '            <input type=\"radio\" name=\"minimum_passing_level\" id=\"fail_indicator_' + number + '\" value=\"' + number + '\" />';
                            new_response += '        </td>';
                            new_response += '    </tr>';
                            jQuery('#response_list').append(new_response);

                            responses[number] = new Ajax.InPlaceEditor('response_text_' + number, '".ENTRADA_RELATIVE."/api/evaluation-selectbox-responses.api.php', { okText: 'Save Changes', cancelText: 'Cancel Changes', externalControl: 'edit_mode_' + number, callback: function(form, value) { return 'action=edit&sid=".session_id()."&id=' + number + '&response='+escape(value) } });
                            responses[number].enterEditMode();
                        }
                    </script>";
                    $ONLOAD[] = "jQuery('#response_list').sortable({ items: '.sortable', handle: '.sort-handle' })";
                    ?>
                    <colgroup>
                        <col style="width: 3%" />
                        <col style="width: 57%" />
                        <col style="width: 20%" />
                        <col style="width: 20%" />
                    </colgroup>
                    <thead>
                        <tr>
                            <td>&nbsp;</td>
                            <td colspan="2">
                                <a class="btn btn-success pull-right" href="javascript: addResponse()"><i class="icon-white icon-plus-sign"></i> Add a response</a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                            <td class="center" style="font-weight: bold; font-size: 11px">Descriptor</td>
                            <td class="center" style="font-weight: bold; font-size: 11px">Minimum Pass</td>
                        </tr>
                    </thead>
                    <tbody id="response_list">
                        <?php
                        if (isset($question_data["evaluation_question_responses"]) && @count($question_data["evaluation_question_responses"])) {
                            $script = "<script type=\"text/javascript\">\n";
                            $script .= "jQuery( document ).ready(function() {\n";
                            foreach ($question_data["evaluation_question_responses"] as $number => $response) {
                                $minimum_passing_level = (((!isset($question_data["evaluation_question_responses"][$number]["minimum_passing_level"]) && ($number == 1)) || (isset($question_data["evaluation_question_responses"][$number]["minimum_passing_level"]) && (int) $question_data["evaluation_question_responses"][$number]["minimum_passing_level"])) ? true : false);
                                $script .= "responses[".$number."] = new Ajax.InPlaceEditor('response_text_".$number."', '".ENTRADA_RELATIVE."/api/evaluation-selectbox-responses.api.php', { okText: 'Save Changes', cancelText: 'Cancel Changes', externalControl: 'edit_mode_".$number."', callback: function(form, value) { return 'action=edit&sid=".session_id()."&id=".$number."&response='+escape(value) } });\n";
                                ?>
                                <tr class="sortable">
                                    <td style="padding-top: 13px" class="sort-handle">
                                        <label for="response_text_<?php echo $number; ?>" class="form-required"><?php echo $number; ?></label>
                                    </td>
                                    <td style="padding-top: 10px">
                                        <span class="response_text" id="response_text_<?php echo $number; ?>" style="width: 99%"><?php echo ((isset($question_data["evaluation_question_responses"][$number]["response_text"])) ? clean_input($question_data["evaluation_question_responses"][$number]["response_text"], "encode") : ""); ?><input type="hidden" value="<?php echo ((isset($question_data["evaluation_question_responses"][$number]["response_text"])) ? clean_input($question_data["evaluation_question_responses"][$number]["response_text"], "encode") : ""); ?>" name="response_text[<?php echo $number; ?>]" /></span>
                                    </td>
                                    <td class="minimumPass center" style="padding-top: 10px">
                                        <a href="javascript: openDescriptorDialog(<?php echo $number; ?>, jQuery('#response_descriptor_<?php echo $number; ?>').val())"><i class="icon-tag"></i></a>
                                        <input type="hidden" id="response_descriptor_<?php echo $number; ?>" name="response_descriptor_id[<?php echo $number; ?>]" value="<?php echo ((isset($question_data["evaluation_question_responses"][$number]["erdescriptor_id"])) ? clean_input($question_data["evaluation_question_responses"][$number]["erdescriptor_id"], "int") : ""); ?>" />
                                    </td>
                                    <td class="minimumPass center" style="padding-top: 10px">
                                        <input type="radio" name="minimum_passing_level" id="fail_indicator_<?php echo $number; ?>" value="<?php echo $number; ?>"<?php echo (($minimum_passing_level) ? " checked=\"true\"" : ""); ?> />
                                    </td>
                                </tr>
                                <?php
                            }
                            $script .= "});\n";
                            $script .= "</script>\n";
                            $HEAD[] = $script;
                        }
                        ?>
                    </tbody>
                    <?php
                break;
                case "matrix_single" :
                default :
                    ?>
                    <colgroup>
                        <col style="width: 3%" />
                        <col style="width: 57%" />
                        <col style="width: 20%" />
                        <col style="width: 20%" />
                    </colgroup>
                    <thead>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                            <td class="center" style="font-weight: bold; font-size: 11px">Descriptor</td>
                            <td class="center" style="font-weight: bold; font-size: 11px">Minimum Pass</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach (range(1, (isset($question_data["responses_count"]) && (int) $question_data["responses_count"] ? (int) $question_data["responses_count"] : 4)) as $number) {
                            $minimum_passing_level = (((!isset($question_data["evaluation_question_responses"][$number]["minimum_passing_level"]) && ($number == 1)) || (isset($question_data["evaluation_question_responses"][$number]["minimum_passing_level"]) && (int) $question_data["evaluation_question_responses"][$number]["minimum_passing_level"])) ? true : false);
                            ?>
                            <tr>
                                <td style="padding-top: 13px">
                                    <label for="response_text_<?php echo $number; ?>" class="form-required"><?php echo $number; ?></label>
                                </td>
                                <td style="padding-top: 10px">
                                    <input type="text" class="response_text" id="response_text_<?php echo $number; ?>" name="response_text[<?php echo $number; ?>]" style="width: 99%" value="<?php echo ((isset($question_data["evaluation_question_responses"][$number]["response_text"])) ? clean_input($question_data["evaluation_question_responses"][$number]["response_text"], "encode") : ""); ?>" />
                                </td>
                                <td class="minimumPass center" style="padding-top: 10px">
                                    <a href="javascript: openDescriptorDialog(<?php echo $number; ?>, jQuery('#response_descriptor_<?php echo $number; ?>').val())"><i class="icon-tag"></i></a>
                                    <input type="hidden" id="response_descriptor_<?php echo $number; ?>" name="response_descriptor_id[<?php echo $number; ?>]" value="<?php echo ((isset($question_data["evaluation_question_responses"][$number]["erdescriptor_id"])) ? clean_input($question_data["evaluation_question_responses"][$number]["erdescriptor_id"], "int") : ""); ?>" />
                                </td>
                                <td class="minimumPass center" style="padding-top: 10px">
                                    <input type="radio" name="minimum_passing_level" id="fail_indicator_<?php echo $number; ?>" value="<?php echo $number; ?>"<?php echo (($minimum_passing_level) ? " checked=\"true\"" : ""); ?> />
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <?php
                break;
            }
		}
	}

	public static function getQuestionAnswerControls($questions, $form_id, $allow_question_modifications = true, $attempt = false, $eprogress_id = 0) {
		global $db;

		?>
		<div id="form-content-questions-holder">
			<ol id="form-questions-list">
			<?php
			if ($eprogress_id) {
				$current_progress_record = Classes_Evaluation::loadProgress($eprogress_id);
			} else {
				$current_progress_record = false;
			}
			$rubric_id = 0;
			$show_rubric_headers = false;
			$show_rubric_footers = false;
			$rubric_table_open = false;
			$original_question_id = 0;
			$comments_enabled = false;
			$modified_count = 0;
			$desctext_count = 0;
			foreach ($questions as $key => $question) {
				if (isset($question["questiontype_id"]) && $question["questiontype_id"]) {
					$query = "SELECT * FROM `evaluations_lu_questiontypes`
								WHERE `questiontype_id` = ".$db->qstr($question["questiontype_id"]);
					$questiontype = $db->GetRow($query);
				} else {
					$questiontype = array("questiontype_shortname" => "matrix_single");
				}
				switch ($questiontype["questiontype_shortname"]) {
					case "rubric" :
						$query = "SELECT * FROM `evaluation_rubric_questions` AS a
									JOIN `evaluations_lu_rubrics` AS b
									ON a.`erubric_id` = b.`erubric_id`
									WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"]);
						$rubric = $db->GetRow($query);
						if ($rubric) {
							if ($rubric["erubric_id"] != $rubric_id) {
								if ($rubric_id) {
									$show_rubric_footers = true;
								}
								$rubric_id = $rubric["erubric_id"];
								$show_rubric_headers = true;
								$original_question_id = $question["equestion_id"];
								$comments_enabled = $question["allow_comments"];
							}
							if ($show_rubric_footers) {
								$show_rubric_footers = false;
								$rubric_table_open = false;
								echo "</table></div>";
								if ($comments_enabled) {
									echo "	<div class=\"clear\"></div>\n";
									echo "	<div class=\"comments\">\n";
									echo "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
									echo "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$original_question_id."', '0', $('".$original_question_id."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
									echo "	</div>\n";
								} else {
									echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
								}
								$original_question_id = $question["equestion_id"];
								$comments_enabled = $question["allow_comments"];
								echo "</li>";
							}
							if ($show_rubric_headers) {
								$rubric_table_open = true;
								echo "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">\n";
								echo "<span id=\"question_text_".$question["equestion_id"]."\" style=\"display: none;\">".$rubric["rubric_title"].(stripos($rubric["rubric_title"], "rubric") === false ? " Grouped Item" : "")."</span>";
								echo (isset($rubric["rubric_title"]) && $rubric["rubric_title"] ? "<h2>".$rubric["rubric_title"] : "")."<span style=\"font-weight: normal; margin-left: 10px; padding-right: 30px;\" class=\"content-small\">".$rubric["rubric_description"]."</span>".(isset($rubric["rubric_title"]) && $rubric["rubric_title"] ? "</h2>\n" : "\n");
								if ($allow_question_modifications) {
									echo "<div class=\"rubric-controls\">\n";
									echo "	<a href=\"".ENTRADA_URL."/admin/evaluations/questions?form_id=".$form_id."&amp;section=edit&amp;id=".$question["equestion_id"]."&amp;efquestion_id=".$question["efquestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-edit.gif\" alt=\"Edit Question\" title=\"Edit Question\" /></a>";
									echo "	<a id=\"question_delete_".$question["equestion_id"]."\" class=\"question-controls-delete\" href=\"#delete-question-confirmation-box\" title=\"".$question["equestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-delete.gif\" alt=\"Delete Question\" title=\"Delete Question\" /></a>";
									echo "</div>\n";
								}
								$modified_count++;
								echo "<br /><div class=\"question".($allow_question_modifications ? " cursor-move" : "")."\"><table class=\"rubric\">\n";
								echo "	<tr>\n";
								$columns = 0;
								$query = "	SELECT a.*
											FROM `evaluations_lu_question_responses` AS a
											WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
											ORDER BY a.`response_order` ASC";
								$responses = $db->GetAll($query);
								if ($responses) {
									$response_width = floor(100 / (count($responses) + 1));
									echo "		<th style=\"width: ".$response_width."%; text-align: left; border-bottom: \">\n";
									echo "			Categories";
									echo "		</th>\n";
									foreach ($responses as $response) {
										$columns++;
										echo "<th style=\"width: ".$response_width."%; text-align: left;\">\n";
										echo clean_input($response["response_text"], "specialchars");
										echo "</th>\n";
									}
								}
								echo "	</tr>\n";
								$show_rubric_headers = false;
							}

							$question_number = ($key + 1);

							echo "<tr id=\"question_".$question["equestion_id"]."\">";

							$query = "	SELECT b.*, a.`equestion_id`, a.`minimum_passing_level`
										FROM `evaluations_lu_question_responses` AS a
										LEFT JOIN `evaluations_lu_question_response_criteria` AS b
										ON a.`eqresponse_id` = b.`eqresponse_id`
										WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
										ORDER BY a.`response_order` ASC";
							$criteriae = $db->GetAll($query);
							if ($criteriae) {
								$criteria_width = floor(100 / (count($criteriae) + 1));
								echo "		<td style=\"width: ".$criteria_width."%\">\n";
								echo "			<div class=\"td-stretch\" style=\"position: relative; width: 100%; vertical-align: middle;\">\n";
                                if (!$attempt) {
                                    echo "				<img onclick=\"openDialog('".ENTRADA_URL."/admin/evaluations/forms/questions?section=api-objectives&id=".$form_id."&efquestion_id=".$question["efquestion_id"]."')\" width=\"16\" height=\"16\" class=\"question-controls cursor-pointer pull-right\" src=\"".ENTRADA_URL."/images/icon-resources-on.gif\" alt=\"Edit Question Objectives\" title=\"Edit Question Objectives\" style=\"margin-top: -15px;\" />";
                                }
								echo "				<div style=\"position: relative; top: 50%;\">\n";
                                echo "                  <strong>".$question["question_text"]."</strong>\n";
                                if (isset($question["question_description"]) && $question["question_description"]) {
                                    echo "                  <div class=\"space-above content-small\">".nl2br($question["question_description"])."</div>";
                                }
                                echo "              </div>\n";
								echo "			</div>\n";
								echo "		</td>\n";
								foreach ($criteriae as $criteria) {
									echo "<td style=\"width: ".$criteria_width."%; vertical-align: top;\" >\n";
									echo "	<div style=\"width: 100%; text-align: center; padding-bottom: 10px;\">";
									echo "		<input type=\"radio\" id=\"".$form_id."_".$criteria["equestion_id"]."_".$criteria["eqresponse_id"]."\" name=\"responses[".$question["equestion_id"]."]\"".($attempt ? " onclick=\"((this.checked == true) ? storeResponse('".$question["equestion_id"]."', '".$criteria["eqresponse_id"]."', ".($question["allow_comments"] ? "$('".$original_question_id."_comment').value" : "''").") : false)\"" : "").($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $criteria["eqresponse_id"] ? " checked=\"checked\"" : "")." value=\"".$criteria["eqresponse_id"]."\" />";
									echo "	</div>\n";
									echo clean_input(nl2br($criteria["criteria_text"]), "allowedtags");
									echo "</td>\n";
								}
							}
							echo "</tr>";
						}
					break;
					case "descriptive_text" :
					case "free_text" :
						if ($rubric_table_open) {
							$rubric_table_open = false;
							$rubric_id = 0;
							echo "</table></div>";
							if ($comments_enabled) {
								echo "	<div class=\"clear\"></div>\n";
								echo "	<div class=\"comments\">\n";
								echo "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
								echo "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$original_question_id."', '0', $('".$original_question_id."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
								echo "	</div>\n";
							} else {
								echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
							}
							$original_question_id = 0;
							$comments_enabled = false;
							echo "</li>";
						}
						$question_number = ($key + 1);

						echo "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                        echo "<div class=\"controls align-top\">\n";
                            if ($allow_question_modifications) {
                                echo "	<a href=\"".ENTRADA_URL."/admin/evaluations/questions?form_id=".$form_id."&amp;section=edit&amp;id=".$question["equestion_id"]."&amp;efquestion_id=".$question["efquestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-edit.gif\" alt=\"Edit Question\" title=\"Edit Question\" /></a>";
                                echo "	<a id=\"question_delete_".$question["equestion_id"]."\" class=\"question-controls-delete\" href=\"#delete-question-confirmation-box\" title=\"".$question["equestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-delete.gif\" alt=\"Delete Question\" title=\"Delete Question\" /></a>";
                            }
                        if ($questiontype["questiontype_shortname"] == "free_text" && !$attempt) {
                            echo "	<a href=\"javascript: openDialog('".ENTRADA_URL."/admin/evaluations/forms/questions?section=api-objectives&id=".$form_id."&efquestion_id=".$question["efquestion_id"]."')\" class=\"question-controls-objectives\" title=\"".$question["efquestion_id"]."\"><img width=\"16\" height=\"16\" class=\"question-controls\" src=\"".ENTRADA_URL."/images/icon-resources-on.gif\" alt=\"Edit Question Objectives\" title=\"Edit Question Objectives\" /></a>";
                        }
                        echo "</div>\n";
						echo "	<div id=\"question_text_".$question["equestion_id"]."\" for=\"".$question["equestion_id"]."_comment\" class=\"question".($allow_question_modifications ? " cursor-move" : "")."\">\n";
						echo "		".clean_input($question["question_text"], "specialchars");
						echo "	</div>\n";
						echo "	<div class=\"clear\"></div>";
						if ($questiontype["questiontype_shortname"] == "free_text") {
							echo "	<div class=\"comments\">";
							echo "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$question["equestion_id"]."', '0', $('".$question["equestion_id"]."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
							echo "	</div>";
						}
						echo "</li>\n";
						$modified_count++;
					break;
					case "matrix_single" :
					default :
						if ($rubric_table_open) {
							$rubric_table_open = false;
							$rubric_id = 0;
							echo "</table></div>";
							if ($comments_enabled) {
								echo "	<div class=\"clear\"></div>\n";
								echo "	<div class=\"comments\">\n";
								echo "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
								echo "	<textarea id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$original_question_id."', '0', $('".$original_question_id."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
								echo "	</div>\n";
							} else {
								echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
							}
							$original_question_id = 0;
							$comments_enabled = false;
							echo "</li>";
						}
						$question_number = ($key + 1);

						echo "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                        echo "<div class=\"controls\">\n";
						if ($allow_question_modifications) {
							echo "	<a href=\"".ENTRADA_URL."/admin/evaluations/questions?form_id=".$form_id."&amp;section=edit&amp;id=".$question["equestion_id"]."&amp;efquestion_id=".$question["efquestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-edit.gif\" alt=\"Edit Question\" title=\"Edit Question\" /></a>";
							echo "	<a id=\"question_delete_".$question["equestion_id"]."\" class=\"question-controls-delete\" href=\"#delete-question-confirmation-box\" title=\"".$question["equestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-delete.gif\" alt=\"Delete Question\" title=\"Delete Question\" /></a>";
						}
                        if (!$attempt) {
                            echo "	<a href=\"javascript: openDialog('".ENTRADA_URL."/admin/evaluations/forms/questions?section=api-objectives&id=".$form_id."&efquestion_id=".$question["efquestion_id"]."')\" class=\"question-controls-objectives\" title=\"".$question["efquestion_id"]."\"><img width=\"16\" height=\"16\" class=\"question-controls\" src=\"".ENTRADA_URL."/images/icon-resources-on.gif\" alt=\"Edit Question Objectives\" title=\"Edit Question Objectives\" /></a>";
                        }
                        echo "</div>\n";
						echo "	<div id=\"question_text_".$question["equestion_id"]."\" class=\"question".($allow_question_modifications ? " cursor-move" : "")."\">\n";
						echo "		".clean_input($question["question_text"], "specialchars");
						echo "	</div>\n";
						echo "	<div class=\"responses clearfix\">\n";
						$query = "	SELECT a.*
									FROM `evaluations_lu_question_responses` AS a
									WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
									ORDER BY a.`response_order` ASC";
						$responses = $db->GetAll($query);
						if ($responses) {
							$response_width = floor(100 / count($responses)) - 1;
							//echo "<div class=\"clearfix\">\n";
							foreach ($responses as $response) {
								echo "<div style=\"width: ".$response_width."%\">\n";
								echo "	<label for=\"response_".$response["equestion_id"]."_".$response["eqresponse_id"]."\">".clean_input($response["response_text"], "specialchars")."</label><br />";
								echo "	<input type=\"radio\" style=\"margin-top: 5px\" id=\"response_".$question["equestion_id"]."_".$response["eqresponse_id"]."\" name=\"responses[".$response["equestion_id"]."]\"".($attempt ? " onclick=\"((this.checked == true) ? storeResponse('".$question["equestion_id"]."', '".$response["eqresponse_id"]."', ".($question["allow_comments"] ? "$('".$response["equestion_id"]."_comment').value" : "''").") : false)\"" : "").($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $response["eqresponse_id"] ? " checked=\"checked\"" : "")." value=\"".$response["eqresponse_id"]."\" />";
								echo "</div>\n";
							}
							//echo "</div>\n";
						}
						echo "	</div>\n";
						if ($question["allow_comments"]) {
							echo "	<div class=\"clear\"></div>";
							echo "	<div class=\"comments\">";
							echo "	<label for=\"".$question["equestion_id"]."_comment\" class=\"form-nrequired\">Comments:</label>";
							echo "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$question["equestion_id"]."', '0', $('".$question["equestion_id"]."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
							echo "	</div>";
						} else {
							echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
						}
						echo "</li>\n";
						$modified_count++;
					break;
					case "vertical_matrix" :
						if ($rubric_table_open) {
							$rubric_table_open = false;
							$rubric_id = 0;
							echo "</table></div>";
							if ($comments_enabled) {
								echo "	<div class=\"clear\"></div>\n";
								echo "	<div class=\"comments\">\n";
								echo "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
								echo "	<textarea id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$original_question_id."', '0', $('".$original_question_id."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
								echo "	</div>\n";
							} else {
								echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
							}
							$original_question_id = 0;
							$comments_enabled = false;
							echo "</li>";
						}
						$question_number = ($key + 1);

						echo "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                        echo "<div class=\"controls\">\n";
						if ($allow_question_modifications) {
							echo "	<a href=\"".ENTRADA_URL."/admin/evaluations/questions?form_id=".$form_id."&amp;section=edit&amp;id=".$question["equestion_id"]."&amp;efquestion_id=".$question["efquestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-edit.gif\" alt=\"Edit Question\" title=\"Edit Question\" /></a>";
							echo "	<a id=\"question_delete_".$question["equestion_id"]."\" class=\"question-controls-delete\" href=\"#delete-question-confirmation-box\" title=\"".$question["equestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-delete.gif\" alt=\"Delete Question\" title=\"Delete Question\" /></a>";
						}
                        if (!$attempt) {
                            echo "	<a href=\"javascript: openDialog('".ENTRADA_URL."/admin/evaluations/forms/questions?section=api-objectives&id=".$form_id."&efquestion_id=".$question["efquestion_id"]."')\" class=\"question-controls-objectives\" title=\"".$question["efquestion_id"]."\"><img width=\"16\" height=\"16\" class=\"question-controls\" src=\"".ENTRADA_URL."/images/icon-resources-on.gif\" alt=\"Edit Question Objectives\" title=\"Edit Question Objectives\" /></a>";
                        }
                        echo "</div>\n";
						echo "	<div id=\"question_text_".$question["equestion_id"]."\" class=\"question".($allow_question_modifications ? " cursor-move" : "")."\">\n";
						echo "		".clean_input($question["question_text"], "specialchars");
						echo "	</div>\n";
						echo "	<div class=\"responses clearfix\">\n";
						$query = "	SELECT a.*
									FROM `evaluations_lu_question_responses` AS a
									WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
									ORDER BY a.`response_order` ASC";
						$responses = $db->GetAll($query);
						if ($responses) {
							echo "<div class=\"clearfix\" style=\"text-align: left; width: 100%;\">\n";
							foreach ($responses as $response) {
								echo "<div>\n";
								echo "	<input type=\"radio\" style=\"float: left\" id=\"response_".$question["equestion_id"]."_".$response["eqresponse_id"]."\" name=\"responses[".$response["equestion_id"]."]\"".($attempt ? " onclick=\"((this.checked == true) ? storeResponse('".$question["equestion_id"]."', '".$response["eqresponse_id"]."', ".($question["allow_comments"] ? "$('".$response["equestion_id"]."_comment').value" : "''").") : false)\"" : "").($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $response["eqresponse_id"] ? " checked=\"checked\"" : "")." value=\"".$response["eqresponse_id"]."\" />&nbsp;";
								echo "	<label for=\"response_".$response["equestion_id"]."_".$response["eqresponse_id"]."\">".clean_input($response["response_text"], "specialchars")."</label><br />";
								echo "</div>\n";
							}
							echo "</div>\n";
						}
						echo "	</div>\n";
						if ($question["allow_comments"]) {
							echo "	<div class=\"clear\"></div>";
							echo "	<div class=\"comments\">";
							echo "	<label for=\"".$question["equestion_id"]."_comment\" class=\"form-nrequired\">Comments:</label>";
							echo "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$question["equestion_id"]."', '0', $('".$question["equestion_id"]."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
							echo "	</div>";
						} else {
							echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
						}
						echo "</li>\n";
						$modified_count++;
					break;
					case "selectbox" :
						if ($rubric_table_open) {
							$rubric_table_open = false;
							$rubric_id = 0;
							echo "</table></div>";
							if ($comments_enabled) {
								echo "	<div class=\"clear\"></div>\n";
								echo "	<div class=\"comments\">\n";
								echo "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
								echo "	<textarea id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$original_question_id."', '0', $('".$original_question_id."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
								echo "	</div>\n";
							} else {
								echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
							}
							$original_question_id = 0;
							$comments_enabled = false;
							echo "</li>";
						}
						$question_number = ($key + 1);

						echo "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                        echo "<div class=\"controls\">\n";
						if ($allow_question_modifications) {
							echo "	<a href=\"".ENTRADA_URL."/admin/evaluations/questions?form_id=".$form_id."&amp;section=edit&amp;id=".$question["equestion_id"]."&amp;efquestion_id=".$question["efquestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-edit.gif\" alt=\"Edit Question\" title=\"Edit Question\" /></a>";
							echo "	<a id=\"question_delete_".$question["equestion_id"]."\" class=\"question-controls-delete\" href=\"#delete-question-confirmation-box\" title=\"".$question["equestion_id"]."\"><img class=\"question-controls\" src=\"".ENTRADA_URL."/images/action-delete.gif\" alt=\"Delete Question\" title=\"Delete Question\" /></a>";
						}
                        if (!$attempt) {
                            echo "	<a href=\"javascript: openDialog('".ENTRADA_URL."/admin/evaluations/forms/questions?section=api-objectives&id=".$form_id."&efquestion_id=".$question["efquestion_id"]."')\" class=\"question-controls-objectives\" title=\"".$question["efquestion_id"]."\"><img width=\"16\" height=\"16\" class=\"question-controls\" src=\"".ENTRADA_URL."/images/icon-resources-on.gif\" alt=\"Edit Question Objectives\" title=\"Edit Question Objectives\" /></a>";
                        }
                        echo "</div>\n";
						echo "	<div id=\"question_text_".$question["equestion_id"]."\" class=\"question".($allow_question_modifications ? " cursor-move" : "")."\">\n";
						echo "		".clean_input($question["question_text"], "specialchars");
						echo "	</div>\n";
						echo "	<div class=\"responses clearfix\">\n";
						$query = "	SELECT a.*
									FROM `evaluations_lu_question_responses` AS a
									WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
									ORDER BY a.`response_order` ASC";
						$responses = $db->GetAll($query);
						if ($responses) {
							$response_width = floor(100 / count($responses)) - 1;
							//echo "<div class=\"clearfix\">\n";
                            echo "<select id=\"responses_".$question["equestion_id"]."\" name=\"responses[".$question["equestion_id"]."]\"".($attempt ? " onchange=\"storeResponse('".$question["equestion_id"]."', jQuery('#responses_".$question["equestion_id"]."').val(), ".($question["allow_comments"] ? "$('".$question["equestion_id"]."_comment').value" : "''").")\"" : "").">\n";
                            echo "  <option value=\"0\">-- Select a response --</option>\n";
							foreach ($responses as $response) {
                                echo "  <option value=\"".$response["eqresponse_id"]."\"".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $response["eqresponse_id"] ? " selected=\"selected\"" : "").">".clean_input($response["response_text"], "specialchars")."</option>\n";
							}
                            echo "</select>\n";
							//echo "</div>\n";
						}
						echo "	</div>\n";
						if ($question["allow_comments"]) {
							echo "	<div class=\"clear\"></div>";
							echo "	<div class=\"comments\">";
							echo "	<label for=\"".$question["equestion_id"]."_comment\" class=\"form-nrequired\">Comments:</label>";
							echo "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$question["equestion_id"]."', '0', $('".$question["equestion_id"]."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
							echo "	</div>";
						} else {
							echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
						}
						echo "</li>\n";
						$modified_count++;
					break;
				}
			}
			if ($rubric_table_open) {
				echo "</table></div>";
				if ($comments_enabled) {
					echo "	<div class=\"clear\"></div>\n";
					echo "	<div class=\"comments\">\n";
					echo "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
					echo "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"".($attempt ? " onblur=\"((this.value.length > 1) ? storeResponse('".$original_question_id."', '0', $('".$original_question_id."_comment').value) : false)\"" : "").">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
					echo "	</div>\n";
				} else {
					echo "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
				}
				echo "</li>";
			}

			?>
			</ol>
		</div>
		<?php
		if ($allow_question_modifications) {
			?>
			<div id="delete-question-confirmation-box" class="modal-confirmation">
				<h1>Delete Form <strong>Question</strong> Confirmation</h1>
				Do you really wish to remove this question from your evaluation form?
				<div class="body">
					<div id="delete-question-confirmation-content" class="content"></div>
				</div>
				If you confirm this action, the question will be permanently removed.
				<div class="footer">
					<input type="button" class="btn" value="Close" onclick="Control.Modal.close()" style="float: left; margin: 8px 0px 4px 10px" />
					<input type="button" class="btn btn-danger" value="Confirm" onclick="deleteFormQuestion(deleteQuestion_id)" style="float: right; margin: 8px 10px 4px 0px" />
				</div>
			</div>
			<script type="text/javascript" defer="defer">

				jQuery('.td-stretch').each(function(){
					var heightToSet =  jQuery(this).parent().innerHeight();
					jQuery(this).height(heightToSet);
				});

				var deleteQuestion_id = 0;

				Sortable.create('form-questions-list', { handles : $$('#form-questions-list div.question'), onUpdate : updateFormQuestionOrder });

				$$('a.question-controls-delete').each(function(obj) {
					new Control.Modal(obj.id, {
						overlayOpacity:	0.75,
						closeOnClick:	'overlay',
						className:		'modal-confirmation',
						fade:			true,
						fadeDuration:	0.30,
						beforeOpen: function() {
							deleteQuestion_id = obj.readAttribute('title');
							$('delete-question-confirmation-content').innerHTML = $('question_text_' + obj.readAttribute('title')).innerHTML;
						},
						afterClose: function() {
							deleteQuestion_id = 0;
							$('delete-question-confirmation-content').innerHTML = '';
						}
					});
				});

				function updateFormQuestionOrder() {
					new Ajax.Request('<?php echo ENTRADA_URL; ?>/admin/evaluations/forms/questions?section=api-order&id=<?php echo $form_id; ?>', {
						method: 'post',
						parameters: {result : Sortable.serialize('form-questions-list', { name : 'order' }) },
						onSuccess: function(transport) {
							var count = 0;
							$$('#form-questions-list li').each(function(obj) {
								if (obj.hasClassName('odd')) {
									obj.removeClassName('odd');
								}

								if (!(count % 2)) {
									obj.addClassName('odd');
								}
								count++;
							});
							if (!transport.responseText.match(200)) {
								new Effect.Highlight('form-content-questions-holder', { startcolor : '#FFD9D0' });
							}
						},
						onError: function() {
							new Effect.Highlight('form-content-questions-holder', { startcolor : '#FFD9D0' });
						}
					});
				}

				function deleteFormQuestion(efquestion_id) {
					Control.Modal.close();
					$('question_' + efquestion_id).fade({ duration: 0.3 });

					new Ajax.Request('<?php echo ENTRADA_URL; ?>/admin/evaluations/forms/questions', {
						method: 'post',
						parameters: { id: '<?php echo $form_id; ?>', section: 'api-delete', record: efquestion_id },
						onSuccess: function(transport) {
							if (transport.responseText.match(200)) {
								$('question_' + efquestion_id).remove();

								if ($$('#form-questions-list li').length == 0) {
									$('form-content-questions-holder').hide();
									$('display-no-question-message').show();
								}
							} else {
								if ($$('#question_' + efquestion_id + ' .display-error').length == 0) {
									var errorString = 'Unable to delete this question at this time.<br /><br />The system administrator has been notified of this error, please try again later.';
									var errorMsg = new Element('div', { 'class': 'display-error' }).update(errorString);

									$('question_' + efquestion_id).insert(errorMsg);
								}

								$('question_' + efquestion_id).appear({ duration: 0.3 });

								new Effect.Highlight('question_' + efquestion_id, { startcolor : '#FFD9D0' });
							}
						},
						onError: function() {
							$('question_' + efquestion_id).appear({ duration: 0.3 });

							new Effect.Highlight('question_' + efquestion_id, { startcolor : '#FFD9D0' });
						}
					});
				}
			</script>
			<?php
		}
	}

	public static function getQuestionControlsArray($questions) {
		global $db;
		$question_controls = array();
		?>
		<div id="evaluation-questions-holder">
			<?php
			$temp_question_controls = array();
			$rubric_id = 0;
			$show_rubric_headers = false;
			$show_rubric_footers = false;
			$rubric_table_open = false;
			$original_question_id = 0;
			$comments_enabled = false;
			$modified_count = 0;
			$desctext_count = 0;
			foreach ($questions as $key => &$question) {
				if (isset($question["questiontype_id"]) && $question["questiontype_id"]) {
					$query = "SELECT * FROM `evaluations_lu_questiontypes`
								WHERE `questiontype_id` = ".$db->qstr($question["questiontype_id"]);
					$questiontype = $db->GetRow($query);
				} else {
					$questiontype = array("questiontype_shortname" => "matrix_single");
				}
				switch ($questiontype["questiontype_shortname"]) {
					case "rubric" :
						$query = "SELECT * FROM `evaluation_rubric_questions` AS a
									JOIN `evaluations_lu_rubrics` AS b
									ON a.`erubric_id` = b.`erubric_id`
									WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"]);
						$rubric = $db->GetRow($query);
						if ($rubric) {
							if ($rubric["erubric_id"] != $rubric_id) {
								if ($rubric_id) {
									$show_rubric_footers = true;
								}
								$rubric_id = $rubric["erubric_id"];
								$show_rubric_headers = true;
								$save_question_id = $original_question_id;
								$original_question_id = $question["equestion_id"];
								$comments_enabled = $question["allow_comments"];
							}
							if ($show_rubric_footers) {
								$show_rubric_footers = false;
								$rubric_table_open = false;
								$temp_question_controls[] = "</table></div>";
								if ($comments_enabled) {
									$temp_question_controls[] = "	<div class=\"clear\"></div>\n";
									$temp_question_controls[] = "	<div class=\"comments\">\n";
									$temp_question_controls[] = "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
									$temp_question_controls[] = "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"></textarea>\n";
									$temp_question_controls[] = "	</div>\n";
								} else {
									$temp_question_controls[] = "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
								}
								$temp_question_controls[] = "</div>";
								$question_controls[$save_question_id] = implode("", $temp_question_controls);
								$original_question_id = $question["equestion_id"];
								$comments_enabled = $question["allow_comments"];
							}
							if ($show_rubric_headers) {
								$rubric_table_open = true;
								unset($temp_question_controls);
								$temp_question_controls = array("<div id=\"question_".$question["equestion_id"]."\">\n");
								$temp_question_controls[] = "<span id=\"question_text_".$question["equestion_id"]."\" style=\"display: none;\">".$rubric["rubric_title"].(stripos($rubric["rubric_title"], "rubric") === false ? " Grouped Item" : "")."</span>";
								$temp_question_controls[] = (isset($rubric["rubric_title"]) && $rubric["rubric_title"] ? "<h2>".$rubric["rubric_title"] : "")."<span style=\"font-weight: normal; margin-left: 10px; padding-right: 30px;\" class=\"content-small\">".$rubric["rubric_description"]."</span>".(isset($rubric["rubric_title"]) && $rubric["rubric_title"] ? "</h2>\n" : "\n");
								$modified_count++;
								$temp_question_controls[] = "<div class=\"question\"><table class=\"rubric\">\n";
								$temp_question_controls[] = "	<tr>\n";
								$columns = 0;
								$query = "	SELECT a.*
											FROM `evaluations_lu_question_responses` AS a
											WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
											ORDER BY a.`response_order` ASC";
								$responses = $db->GetAll($query);
								if ($responses) {
									$response_width = floor(100 / (count($responses) + 1));
									$temp_question_controls[] = "		<th style=\"width: ".$response_width."%; text-align: left; border-bottom: \">\n";
									$temp_question_controls[] = "			Categories";
									$temp_question_controls[] = "		</th>\n";
									foreach ($responses as $response) {
										$columns++;
										$temp_question_controls[] = "<th style=\"width: ".$response_width."%; text-align: left;\">\n";
										$temp_question_controls[] = clean_input($response["response_text"], "specialchars");
										$temp_question_controls[] = "</th>\n";
									}
								}
								$temp_question_controls[] = "	</tr>\n";
								$show_rubric_headers = false;
							}

							$question_number = ($key + 1);

							$temp_question_controls[] = "<tr id=\"question_".$question["equestion_id"]."\">";

							$query = "SELECT b.*, a.`equestion_id`, a.`minimum_passing_level`
										FROM `evaluations_lu_question_responses` AS a
										LEFT JOIN `evaluations_lu_question_response_criteria` AS b
										ON a.`eqresponse_id` = b.`eqresponse_id`
										WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
										ORDER BY a.`response_order` ASC";
							$criteriae = $db->GetAll($query);
							if ($criteriae) {
								$criteria_width = floor(100 / (count($criteriae) + 1));
								$temp_question_controls[] = "		<td style=\"width: ".$criteria_width."%\">\n";
								$temp_question_controls[] = "			<div class=\"td-stretch\" style=\"position: relative; width: 100%; vertical-align: middle;\">\n";
                                $temp_question_controls[] = "			    <div style=\"position: relative; top: 50%;\">\n";
                                $temp_question_controls[] = "                   <strong>".$question["question_text"]."</strong>\n";
                                $temp_question_controls[] = "                   <div class=\"space-above content-small\">".nl2br($question["question_description"])."</div>";
                                $temp_question_controls[] = "               </div>\n";
								$temp_question_controls[] = "			</div>\n";
								$temp_question_controls[] = "		</td>\n";
								foreach ($criteriae as $criteria) {
									$temp_question_controls[] = "<td style=\"width: ".$criteria_width."%; vertical-align: top;\" >\n";
									$temp_question_controls[] = "	<div style=\"width: 100%; text-align: center; padding-bottom: 10px;\">";
									$temp_question_controls[] = "		<input type=\"radio\" id=\"".$criteria["equestion_id"]."_".$criteria["eqresponse_id"]."\" name=\"responses[".$question["equestion_id"]."]\" value=\"".$criteria["eqresponse_id"]."\" />";
									$temp_question_controls[] = "	</div>\n";
									$temp_question_controls[] = clean_input(nl2br($criteria["criteria_text"]), "allowedtags");
									$temp_question_controls[] = "</td>\n";
								}
							}
							$temp_question_controls[] = "</tr>";
						}
					break;
					case "descriptive_text" :
					case "free_text" :
						if ($rubric_table_open) {
							$rubric_table_open = false;
							$rubric_id = 0;
							$temp_question_controls[] = "</table></div>";
							if ($comments_enabled) {
								$temp_question_controls[] = "	<div class=\"clear\"></div>\n";
								$temp_question_controls[] = "	<div class=\"comments\">\n";
								$temp_question_controls[] = "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
								$temp_question_controls[] = "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"></textarea>\n";
								$temp_question_controls[] = "	</div>\n";
							} else {
								$temp_question_controls[] = "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
							}
							$temp_question_controls[] = "</div>";
							$question_controls[$original_question_id] = implode("", $temp_question_controls);
							$original_question_id = 0;
							$comments_enabled = false;
						}
						$question_number = ($key + 1);
						unset($temp_question_controls);
						$temp_question_controls = array("<div id=\"question_".$question["equestion_id"]."\">");
						$temp_question_controls[] = "	<div id=\"question_text_".$question["equestion_id"]."\" for=\"".$question["equestion_id"]."_comment\" class=\"question\">\n";
						$temp_question_controls[] = "		".clean_input($question["question_text"], "specialchars");
						$temp_question_controls[] = "	</div>\n";
						$temp_question_controls[] = "	<div class=\"clear\"></div>";
						if ($questiontype["questiontype_shortname"] == "free_text") {
							$temp_question_controls[] = "	<div class=\"comments\">";
							$temp_question_controls[] = "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"></textarea>";
							$temp_question_controls[] = "	</div>";
						}
						$temp_question_controls[] = "</div>\n";
						$question_controls[$question["equestion_id"]] = implode("", $temp_question_controls);
						$modified_count++;
					break;
					case "matrix_single" :
					default :
						if ($rubric_table_open) {
							$rubric_table_open = false;
							$rubric_id = 0;
							$temp_question_controls[] = "</table></div>";
							if ($comments_enabled) {
								$temp_question_controls[] = "	<div class=\"clear\"></div>\n";
								$temp_question_controls[] = "	<div class=\"comments\">\n";
								$temp_question_controls[] = "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
								$temp_question_controls[] = "	<textarea id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"></textarea>\n";
								$temp_question_controls[] = "	</div>\n";
							} else {
								$temp_question_controls[] = "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
							}
							$temp_question_controls[] = "</div>";
							$question_controls[$original_question_id] = implode("", $temp_question_controls);
							$original_question_id = 0;
							$comments_enabled = false;
						}
						$question_number = ($key + 1);
						unset($temp_question_controls);
						$temp_question_controls = array("<div id=\"question_".$question["equestion_id"]."\">");
						$temp_question_controls[] = "	<div id=\"question_text_".$question["equestion_id"]."\" class=\"question\">\n";
						$temp_question_controls[] = "		".clean_input($question["question_text"], "specialchars");
						$temp_question_controls[] = "	</div>\n";
						$temp_question_controls[] = "	<div class=\"responses clearfix\">\n";
						$query = "	SELECT a.*
									FROM `evaluations_lu_question_responses` AS a
									WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
									ORDER BY a.`response_order` ASC";
						$responses = $db->GetAll($query);
						if ($responses) {
							$response_width = floor(100 / count($responses)) - 1;

							foreach ($responses as $response) {
								$temp_question_controls[] = "<div style=\"width: ".$response_width."%\">\n";
								$temp_question_controls[] = "	<label for=\"".$response["equestion_id"]."_".$response["eqresponse_id"]."\">".clean_input($response["response_text"], "specialchars")."</label><br />";
								$temp_question_controls[] = "	<input type=\"radio\" id=\"response_".$question["equestion_id"]."_".$response["eqresponse_id"]."\" name=\"responses[".$response["equestion_id"]."]\" value=\"".$response["eqresponse_id"]."\" />";
								$temp_question_controls[] = "</div>\n";
							}
						}
						$temp_question_controls[] = "	</div>\n";
						if ($question["allow_comments"]) {
							$temp_question_controls[] = "	<div class=\"clear\"></div>";
							$temp_question_controls[] = "	<div class=\"comments\">";
							$temp_question_controls[] = "	<label for=\"".$question["equestion_id"]."_comment\" class=\"form-nrequired\">Comments:</label>";
							$temp_question_controls[] = "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"></textarea>";
							$temp_question_controls[] = "	</div>";
						} else {
							$temp_question_controls[] = "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
						}
						$temp_question_controls[] = "</div>\n";
						$question_controls[$question["equestion_id"]] = implode("", $temp_question_controls);
						$modified_count++;
					break;
				}
			}
			if ($rubric_table_open) {
				$temp_question_controls[] = "</table></div>";
				if ($comments_enabled) {
					$temp_question_controls[] = "	<div class=\"clear\"></div>\n";
					$temp_question_controls[] = "	<div class=\"comments\">\n";
					$temp_question_controls[] = "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
					$temp_question_controls[] = "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\"></textarea>\n";
					$temp_question_controls[] = "	</div>\n";
				} else {
					$temp_question_controls[] = "<input type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
				}
				$temp_question_controls[] = "</div>";
				$question_controls[$original_question_id] = implode("", $temp_question_controls);
			}

			?>
		</div>
		<?php
		return $question_controls;
	}

	public static function getTargetControls ($target_data, $options_for = "", $form_id = 0) {
		global $ENTRADA_USER, $ENTRADA_ACL, $db, $use_ajax;
		if ($form_id) {
			$query = "	SELECT b.*
						FROM `evaluation_forms` AS a
						LEFT JOIN `evaluations_lu_targets` AS b
						ON b.`target_id` = a.`target_id`
						WHERE a.`form_active` = '1'
						AND b.`target_active` = '1'
						AND a.`eform_id` = ".$db->qstr($form_id);
			$target_details = $db->GetRow($query);
			if ($target_details) {
				switch ($target_details["target_shortname"]) {
					case "course" :
						$courses_list = array();

						$query = "	SELECT `course_id`, `organisation_id`, `course_code`, `course_name`
									FROM `courses`
									WHERE `organisation_id`=".$ENTRADA_USER->getActiveOrganisation()."
									AND `course_active` = '1'
									ORDER BY `course_code` ASC, `course_name` ASC";
						$results = $db->GetAll($query);
						if ($results) {
							foreach ($results as $result) {
								if ($ENTRADA_ACL->amIAllowed(new CourseResource($result["course_id"], $result["organisation_id"]), "read")) {
									$courses_list[$result["course_id"]] = ($result["course_code"]." - ".$result["course_name"]);
								}
							}
						}
						?>
						<tr>
							<td></td>
							<td style="vertical-align: top">
								<label for="PickList" class="form-required">Select Courses</label>
								<div class="content-small"><strong>Hint:</strong> Select the course or courses you would like to have evaluated.</div>
							</td>
							<td style="vertical-align: top">
								<select class="multi-picklist" id="PickList" name="course_ids[]" multiple="multiple" size="4" style="width: 100%; margin-bottom: 5px">
								<?php
								if ((is_array($target_data["evaluation_targets"])) && (!empty($target_data["evaluation_targets"]))) {
									foreach ($target_data["evaluation_targets"] as $target) {
										echo "<option value=\"".(int) $target["target_value"]."\">".html_encode($courses_list[$target["target_value"]])."</option>\n";
									}
								}
								?>
								</select>
								<div style="float: left; display: inline">
									<input type="button" id="courses_list_state_btn" class="btn" value="Show List" onclick="toggle_list('courses_list')" />
								</div>
								<div style="float: right; display: inline">
									<input type="button" id="courses_list_remove_btn" class="btn btn-danger" onclick="delIt()" value="Remove" />
									<input type="button" id="courses_list_add_btn" class="btn btn-success" onclick="addIt()" style="display: none" value="Add" />
								</div>
								<div id="courses_list" style="clear: both; padding-top: 3px; display: none">
									<h2>Course List</h2>
									<select class="multi-picklist" id="SelectList" name="other_courses_list" multiple="multiple" size="15" style="width: 100%">
									<?php
									foreach ($courses_list as $course_id => $course_name) {
										if (!in_array($course_id, $target_data["evaluation_targets"])) {
											echo "<option value=\"".(int) $course_id."\">".html_encode($course_name)."</option>\n";
										}
									}
									?>
									</select>
								</div>
							</td>
						</tr>
						<?php
					break;
					case "teacher" :
						$teachers_list = array();

						$query = "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									LEFT JOIN `event_contacts` AS c
									ON c.`proxy_id` = a.`id`
									LEFT JOIN `events` AS d
									ON d.`event_id` = c.`event_id`
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									AND (b.`group` = 'faculty' OR
										(b.`group` = 'resident' AND b.`role` = 'lecturer')
									)
									AND d.`event_finish` >= ".$db->qstr(strtotime("-12 months"))."
									GROUP BY a.`id`
									ORDER BY a.`lastname` ASC, a.`firstname` ASC";
						$results = $db->GetAll($query);
						if ($results) {
							foreach ($results as $result) {
								$teachers_list[$result["proxy_id"]] = $result["fullname"];
							}
						}
						$target_ids = array();
						if (isset($target_data["evaluation_targets"]) && @count($target_data["evaluation_targets"])) {
    						foreach ($target_data["evaluation_targets"] as $temp_target) {
    							if ($temp_target["target_type"] == "proxy_id") {
    								$target_ids[] = $temp_target["target_value"];
    							}
    						}
						}
						?>
						<tr>
							<td></td>
							<td style="vertical-align: top">
								<label for="PickList" class="form-required">Select Teachers</label>
								<div class="content-small"><strong>Hint:</strong> Select the teacher or teachers you would like to have evaluated.</div>
							</td>
							<td style="vertical-align: top">
								<select class="multi-picklist" id="PickList" name="teacher_ids[]" multiple="multiple" size="4" style="width: 100%; margin-bottom: 5px">
								<?php
								if ((is_array($target_data["evaluation_targets"])) && (!empty($target_data["evaluation_targets"]))) {
									foreach ($teachers_list as $proxy_id => $teacher_name) {
										if (in_array($proxy_id, $target_ids)) {
											echo "<option value=\"".(int) $proxy_id."\">".html_encode($teacher_name)."</option>\n";
										}
									}
								}
								?>
								</select>
								<div style="float: left; display: inline">
									<input type="button" id="teachers_list_state_btn" class="btn" value="Show List" onclick="toggle_list('teachers_list')" />
								</div>
								<div style="float: right; display: inline">
									<input type="button" id="teachers_list_remove_btn" class="btn btn-danger" onclick="delIt()" value="Remove" />
									<input type="button" id="teachers_list_add_btn" class="btn btn-success" onclick="addIt()" style="display: none" value="Add" />
								</div>
								<div id="teachers_list" style="clear: both; padding-top: 3px; display: none">
									<h2>Course List</h2>
									<select class="multi-picklist" id="SelectList" name="other_teachers_list" multiple="multiple" size="15" style="width: 100%">
									<?php
									foreach ($teachers_list as $proxy_id => $teacher_name) {
										if (!isset($target_data["evaluation_targets"]) || !is_array($target_data["evaluation_targets"]) || !in_array($proxy_id, $target_data["evaluation_targets"])) {
											echo "<option value=\"".(int) $proxy_id."\">".html_encode($teacher_name)."</option>\n";
										}
									}
									?>
									</select>
								</div>
							</td>
						</tr>
						<?php
					break;
					case "resident" :
						$residents_list = array();

						$query = "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									AND b.`group` = 'student'
									GROUP BY a.`id`
									ORDER BY a.`lastname` ASC, a.`firstname` ASC";
						$results = $db->GetAll($query);
						if ($results) {
							foreach ($results as $result) {
								$residents_list[$result["proxy_id"]] = $result["fullname"];
							}
						}
						$target_ids = array();
						if (isset($target_data["evaluation_targets"]) && @count($target_data["evaluation_targets"])) {
							foreach ($target_data["evaluation_targets"] as $temp_target) {
								if ($temp_target["target_type"] == "proxy_id") {
									$target_ids[] = $temp_target["target_value"];
								}
							}
						}
						?>
						<tr>
							<td></td>
							<td style="vertical-align: top">
								<label for="PickList" class="form-required">Select Learners</label>
								<div class="content-small"><strong>Hint:</strong> Select the learner(s) you would like to have evaluated.</div>
							</td>
							<td style="vertical-align: top">
								<select class="multi-picklist" id="PickList" name="resident_ids[]" multiple="multiple" size="4" style="width: 100%; margin-bottom: 5px">
								<?php
								if ((is_array($target_data["evaluation_targets"])) && (!empty($target_data["evaluation_targets"]))) {
									foreach ($residents_list as $proxy_id => $resident_name) {
										if (in_array($proxy_id, $target_ids)) {
											echo "<option value=\"".(int) $proxy_id."\">".html_encode($resident_name)."</option>\n";
										}
									}
								}
								?>
								</select>
								<div style="float: left; display: inline">
									<input type="button" id="residents_list_state_btn" class="btn" value="Show List" onclick="toggle_list('residents_list')" />
								</div>
								<div style="float: right; display: inline">
									<input type="button" id="residents_list_remove_btn" class="btn btn-danger" onclick="delIt()" value="Remove" />
									<input type="button" id="residents_list_add_btn" class="btn btn-success" onclick="addIt()" style="display: none" value="Add" />
								</div>
								<div id="residents_list" style="clear: both; padding-top: 3px; display: none">
									<h2>Learner List</h2>
									<select class="multi-picklist" id="SelectList" name="other_residents_list" multiple="multiple" size="15" style="width: 100%">
									<?php
									foreach ($residents_list as $proxy_id => $resident_name) {
										if (!isset($target_data["evaluation_targets"]) || !is_array($target_data["evaluation_targets"]) || !in_array($proxy_id, $target_data["evaluation_targets"])) {
											echo "<option value=\"".(int) $proxy_id."\">".html_encode($resident_name)."</option>\n";
										}
									}
									?>
									</select>
								</div>
							</td>
						</tr>
						<?php
					break;
					case "student" :
					case "peer" :
						$query = "SELECT * FROM `course_groups` AS a
									JOIN `courses` AS b
									ON a.`course_id` = b.`course_id`
									ORDER BY b.`course_name`,
										LENGTH(a.`group_name`),
										a.`group_name` ASC";
						$temp_course_groups = $db->GetAll($query);
						$course_groups = array();
						if ($temp_course_groups) {
							foreach ($temp_course_groups as $temp_course_group) {
								$course_groups[$temp_course_group["cgroup_id"]] = $temp_course_group;
							}
						}
						if (!isset($target_data["associated_cgroup_ids"]) && !isset($target_data["associated_cohort_ids"]) && !isset($target_data["associated_proxy_ids"])) {
							if (isset($target_data["evaluation_targets"]) && is_array($target_data["evaluation_targets"])) {
								foreach ($target_data["evaluation_targets"] as $target) {
									if ($target["target_type"] == "cgroup_id") {
										$target_data["associated_cgroup_ids"][] = $target["target_value"];
									} elseif ($target["target_type"] == "cohort") {
										$target_data["associated_cohort_ids"][] = $target["target_value"];
									} elseif ($target["target_type"] == "proxy_id") {
										$target_data["associated_proxy_ids"][] = $target["target_value"];
									}
								}
							}
						}
						unset($temp_course_groups);
						?>
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="faculty_name" class="form-nrequired">Evaluation Targets</label></td>
							<td>
								<table>
									<tbody>
										<tr>
											<td style="vertical-align: top"><input type="radio" name="target_type" id="target_type_custom" value="custom" onclick="selectEvaluationTargetOption('custom')" style="vertical-align: middle" checked="checked" /></td>
											<td colspan="2" style="padding-bottom: 15px">
												<label for="target_type_custom" class="radio-group-title">Custom Evaluation Targets</label>
												<div class="content-small">This evaluation is intended for a custom selection of evaluation targets.</div>

												<div id="evaluation_target_type_custom_options" style="position: relative; margin-top: 10px;">
													<select id="target_type" onchange="showMultiSelect();" style="width: 275px;">
														<option value="">-- Select an target type --</option>
														<option value="cohorts">Cohorts of learners</option>
															<?php

														if ($course_groups) {
															?>
															<option value="course_groups">Course specific small groups</option>
															<?php
														}
															?>
														<option value="students">Individual learners</option>
													</select>

													<span id="options_loading" style="display:none; vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Loading ... </span>
													<span id="options_container"></span>
													<?php
													/**
													 * Compiles the list of groups from groups table (known as Cohorts).
													 */
													$COHORT_LIST = array();
													$results = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
													if ($results) {
														foreach($results as $result) {
															$COHORT_LIST[$result["group_id"]] = $result;
														}
													}

													$GROUP_LIST = $course_groups;

													/**
													 * Compiles the list of students.
													 */
													$STUDENT_LIST = array();
													$query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`organisation_id`
																FROM `".AUTH_DATABASE."`.`user_data` AS a
																LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
																ON a.`id` = b.`user_id`
																WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
																AND b.`account_active` = 'true'
																AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
																AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
																AND b.`group` = 'student'
																AND a.`grad_year` >= '".(date("Y") - ((date("m") < 7) ?  2 : 1))."'
																ORDER BY a.`grad_year` ASC, a.`lastname` ASC, a.`firstname` ASC";
													$results = $db->GetAll($query);
													if ($results) {
														foreach($results as $result) {
															$STUDENT_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
														}
													}
													$target_data["form_id"] = $form_id;

													if (!isset($target_data["associated_cohort_ids"]) && !isset($target_data["associated_cgroup_ids"]) && !isset($target_data["associated_proxy_ids"]) && isset($target_data["evaluation_id"])) {
														$query = "SELECT * FROM `evaluation_targets` WHERE `evaluation_id` = ".$db->qstr($target_data["evaluation_id"]);
														$results = $db->GetAll($query);
														if ($results) {
															$target_data["target_type"] = "custom";

															foreach($results as $result) {
																switch($result["target_type"]) {
																	case "cohort" :
																		$target_data["associated_cohort_ids"][] = (int) $result["target_value"];
																	break;
																	case "cgroup_id" :
																		$target_data["associated_cgroup_ids"][] = (int) $result["target_value"];
																	break;
																	case "proxy_id" :
																		$target_data["associated_proxy_ids"][] = (int) $result["target_value"];
																	break;
																}
															}
														}
													}

													$cohort_ids_string = "";
													$cgroup_ids_string = "";
													$student_ids_string = "";

													if (isset($target_data["associated_course_ids"]) && $target_data["associated_course_ids"]) {
														$course_target_included = true;
													} else {
														$course_target_included = false;
													}

													if (isset($target_data["associated_cohort_ids"]) && is_array($target_data["associated_cohort_ids"])) {
														foreach ($target_data["associated_cohort_ids"] as $group_id) {
															if ($cohort_ids_string) {
																$cohort_ids_string .= ",group_".$group_id;
															} else {
																$cohort_ids_string = "group_".$group_id;
															}
														}
													}

													if (isset($target_data["associated_cgroup_ids"]) && is_array($target_data["associated_cgroup_ids"])) {
														foreach ($target_data["associated_cgroup_ids"] as $group_id) {
															if ($cgroup_ids_string) {
																$cgroup_ids_string .= ",cgroup_".$group_id;
															} else {
																$cgroup_ids_string = "cgroup_".$group_id;
															}
														}
													}

													if (isset($target_data["associated_proxy_ids"]) && is_array($target_data["associated_proxy_ids"])) {
														foreach ($target_data["associated_proxy_ids"] as $proxy_id) {
															if ($student_ids_string) {
																$student_ids_string .= ",student_".$proxy_id;
															} else {
																$student_ids_string = "student_".$proxy_id;
															}
														}
													}
													?>
													<input type="hidden" id="evaluation_target_cohorts" name="evaluation_target_cohorts" value="<?php echo $cohort_ids_string; ?>" />
													<input type="hidden" id="evaluation_target_course_groups" name="evaluation_target_course_groups" value="<?php echo $cgroup_ids_string; ?>" />
													<input type="hidden" id="evaluation_target_students" name="evaluation_target_students" value="<?php echo $student_ids_string; ?>" />
													<input type="hidden" id="evaluation_target_course" name="evaluation_target_course" value="<?php echo $course_target_included ? "1" : "0"; ?>" />

													<ul class="menu multiselect" id="target_list" style="margin-top: 5px">
													<?php
													if (isset($target_data["associated_cohort_ids"]) && count($target_data["associated_cohort_ids"])) {
														foreach ($target_data["associated_cohort_ids"] as $group) {
															if ((array_key_exists($group, $COHORT_LIST)) && is_array($COHORT_LIST[$group])) {
																?>
																<li class="group" id="target_group_<?php echo $COHORT_LIST[$group]["group_id"]; ?>" style="cursor: move;"><?php echo $COHORT_LIST[$group]["group_name"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeTarget('group_<?php echo $COHORT_LIST[$group]["group_id"]; ?>', 'cohorts');" class="list-cancel-image" /></li>
																<?php
															}
														}
													}
													if (isset($target_data["associated_cgroup_ids"]) && count($target_data["associated_cgroup_ids"])) {
														foreach ($target_data["associated_cgroup_ids"] as $group) {
															if ((array_key_exists($group, $GROUP_LIST)) && is_array($GROUP_LIST[$group])) {
																?>
																<li class="group" id="target_cgroup_<?php echo $GROUP_LIST[$group]["cgroup_id"]; ?>" style="cursor: move;"><?php echo $GROUP_LIST[$group]["group_name"]." - ".$GROUP_LIST[$group]["course_code"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeTarget('cgroup_<?php echo $GROUP_LIST[$group]["cgroup_id"]; ?>', 'course_groups');" class="list-cancel-image" /></li>
																<?php
															}
														}
													}

													if (isset($target_data["associated_proxy_ids"]) && count($target_data["associated_proxy_ids"])) {
														foreach ($target_data["associated_proxy_ids"] as $student) {
															if ((array_key_exists($student, $STUDENT_LIST)) && is_array($STUDENT_LIST[$student])) {
																?>
																<li class="user" id="target_student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>" style="cursor: move;"><?php echo $STUDENT_LIST[$student]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeTarget('student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>', 'students');" class="list-cancel-image" /></li>
																<?php
															}
														}
													}
													?>
													</ul>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
						if ($target_details["target_shortname"] == "peer") {
							?>
							<tr>
								<td colspan="2">&nbsp;</td>
								<td>
									<?php echo display_notice("When creating peer assessments, learners will be able to assess any others within the same cohort, course group, or custom list of students, depending on which evaluation targets you include. <br /><br />Additionally, they will not be able to view results of evaluations done on themselves until they have filled out all of the required evaluations available to them, or the evaluation period ends, whichever comes first."); ?>
								</td>
							</tr>
							<?php
						}
					break;
					case "rotation_core" :
						$target_data["form_id"] = $form_id;
						if (!isset($target_data["associated_rotation_ids"])) {
						    if (isset($target_data["evaluation_targets"]) && @count($target_data["evaluation_targets"])) {
    							foreach ($target_data["evaluation_targets"] as $target) {
    								if ($target["target_type"] == "rotation_id") {
    									$target_data["associated_rotation_ids"][] = $target["target_value"];
    								}
    							}
						    }
						}
						?>
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="faculty_name" class="form-nrequired">Evaluation Targets</label></td>
							<td>
								<table>
									<tbody>
										<tr>
											<td style="vertical-align: top"><input type="radio" name="target_type" id="target_type_rotations" value="rotations" onclick="selectEvaluationTargetOption('rotations')" style="vertical-align: middle"  checked="checked" /></td>
											<td colspan="2" style="padding-bottom: 15px">
												<label for="target_type_rotations" class="radio-group-title">Each Service in the selected Core Rotation</label>
												<div class="content-small">This evaluation is intended for all events associated with a custom selection of Core Rotations.</div>
												<?php

												$ROTATION_LIST = array();
												$rotations[0] = array("text" => "All Core Rotations", "value" => "all", "category" => true);

												$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
															WHERE `rotation_id` != ".$db->qstr(MAX_ROTATION);
												$rotation_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
												if ($rotation_results) {
													foreach ($rotation_results as $rotation) {
														$ROTATION_LIST[$rotation["rotation_id"]] = $rotation;
														if (isset($target_data["associated_rotation_ids"]) && is_array($target_data["associated_rotation_ids"]) && in_array($rotation["rotation_id"], $target_data["associated_rotation_ids"])) {
															$checked = "checked=\"checked\"";
														} else {
															$checked = "";
														}

														$rotations[0]["options"][] = array("text" => $rotation["rotation_title"], "class" => "cat_enabled", "value" => "rotation_".$rotation["rotation_id"], "checked" => $checked);
													}

													echo lp_multiple_select_inline("rotations", $rotations, array("title" => "Select Core Rotations:", "hidden" => false, "class" => "select_multiple_area_container", "category_check_all" => true, "submit" => false));
												} else {
													echo display_notice("There are no core rotations available.");
												}
												if (isset($target_data["associated_rotation_ids"]) && is_array($target_data["associated_rotation_ids"])) {
													foreach ($target_data["associated_rotation_ids"] as $rotation_id) {
														if ($rotation_ids_string) {
															$rotation_ids_string .= ",rotation_".$rotation_id;
														} else {
															$rotation_ids_string = "rotation_".$rotation_id;
														}
													}
												}
												?>
												<input type="hidden" id="evaluation_target_rotations" name="evaluation_target_rotations" value="<?php echo $rotation_ids_string; ?>" />
												<ul class="menu multiselect" id="target_list" style="margin-top: 5px;">
													<?php
													if (is_array($target_data["associated_rotation_ids"]) && count($target_data["associated_rotation_ids"])) {
														foreach ($target_data["associated_rotation_ids"] as $rotation) {
															if ((array_key_exists($rotation, $ROTATION_LIST)) && is_array($ROTATION_LIST[$rotation])) {
																?>
																<li class="group" id="target_rotation_<?php echo $ROTATION_LIST[$rotation]["rotation_id"]; ?>" style="cursor: move;"><?php echo $ROTATION_LIST[$rotation]["rotation_title"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeTarget('rotation_<?php echo $ROTATION_LIST[$rotation]["rotation_id"]; ?>', 'rotations');" class="list-cancel-image" style="position: relative; float: right;" /></li>
																<?php
															}
														}
													}
													?>
												</ul>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<?php echo display_notice("When creating core rotation evaluations, the list of <strong>Core Rotations</strong>, <strong>Evaluators</strong>, the <strong>Evaluation Start</strong>, and the <strong>Evaluation Finish</strong> determine which electives will be targeted for evaluation. <br /><br />Each of the services in one of selected <strong>Core Rotations</strong> which ends between the <strong>Evaluation Start</strong> and the <strong>Evaluation Finish</strong> for learners in the <strong>Evaluators</strong> list will require/allow an evaluation to be completed on it."); ?>
							</td>
						</tr>
						<?php
					break;
					case "rotation_elective" :
						?>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<?php echo display_notice("When creating clerkship elective evaluations, the list of <strong>Evaluators</strong>, the <strong>Evaluation Start</strong>, and the <strong>Evaluation Finish</strong> determine which electives will be targeted for evaluation. <br /><br />Each elective which ends between the <strong>Evaluation Start</strong> and the <strong>Evaluation Finish</strong> for learners in the <strong>Evaluators</strong> list will require/allow an evaluation to be completed on it."); ?>
							</td>
						</tr>
						<?php
					break;
					case "preceptor" :
						$target_data["form_id"] = $form_id;
						if (!isset($target_data["associated_rotation_ids"])) {
						    if (isset($target_data["evaluation_targets"]) && @count($target_data["evaluation_targets"])) {
    							foreach ($target_data["evaluation_targets"] as $target) {
    								if ($target["target_type"] == "rotation_id") {
    									$target_data["associated_rotation_ids"][] = $target["target_value"];
    								}
    							}
						    }
						}
						?>
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="faculty_name" class="form-nrequired">Evaluation Targets</label></td>
							<td>
								<table>
									<tbody>
										<tr>
											<td style="vertical-align: top"><input type="hidden" name="target_subtype" value="preceptor" /><input type="radio" name="target_type" id="target_type_rotations" value="rotations" onclick="selectEvaluationTargetOption('rotations')" style="vertical-align: middle"  checked="checked" /></td>
											<td colspan="2" style="padding-bottom: 15px">
												<label for="target_type_rotations" class="radio-group-title">Each Service in the selected Clerkship Rotation</label>
												<div class="content-small">This evaluation is intended for all events associated with a custom selection of Clerkship Rotations.</div>
												<?php

												$ROTATION_LIST = array();
												$rotations[0] = array("text" => "All Clerkship Rotations", "value" => "all", "category" => true);

												$query = "	SELECT *
															FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`";
												$rotation_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
												if ($rotation_results) {
													foreach ($rotation_results as $rotation) {
														$ROTATION_LIST[$rotation["rotation_id"]] = $rotation;
														if (isset($target_data["associated_rotation_ids"]) && is_array($target_data["associated_rotation_ids"]) && in_array($rotation["rotation_id"], $target_data["associated_rotation_ids"])) {
															$checked = "checked=\"checked\"";
														} else {
															$checked = "";
														}

														$rotations[0]["options"][] = array("text" => $rotation["rotation_title"], "value" => "rotation_".$rotation["rotation_id"], "class" => "cat_enabled", "checked" => $checked);
													}

													echo lp_multiple_select_inline("rotations", $rotations, array("title" => "Select Clerkship Rotations:", "hidden" => false, "class" => "select_multiple_area_container", "category_check_all" => true, "submit" => false));
												} else {
													echo display_notice("There are no clerkship rotations available.");
												}
												if (isset($target_data["associated_rotation_ids"]) && is_array($target_data["associated_rotation_ids"])) {
													foreach ($target_data["associated_rotation_ids"] as $rotation_id) {
														if ($rotation_ids_string) {
															$rotation_ids_string .= ",rotation_".$rotation_id;
														} else {
															$rotation_ids_string = "rotation_".$rotation_id;
														}
													}
												}
												?>
												<input type="hidden" id="evaluation_target_rotations" name="evaluation_target_rotations" value="<?php echo $rotation_ids_string; ?>" />
												<ul class="menu multiselect" id="target_list" style="margin-top: 5px;">
													<?php
													if (is_array($target_data["associated_rotation_ids"]) && count($target_data["associated_rotation_ids"])) {
														foreach ($target_data["associated_rotation_ids"] as $rotation) {
															if ((array_key_exists($rotation, $ROTATION_LIST)) && is_array($ROTATION_LIST[$rotation])) {
																?>
																<li class="group" id="target_rotation_<?php echo $ROTATION_LIST[$rotation]["rotation_id"]; ?>" style="cursor: move;"><?php echo $ROTATION_LIST[$rotation]["rotation_title"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeTarget('rotation_<?php echo $ROTATION_LIST[$rotation]["rotation_id"]; ?>', 'rotations');" class="list-cancel-image" style="position: relative; float: right;" /></li>
																<?php
															}
														}
													}
													?>
												</ul>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<?php echo display_notice("When creating clerkship preceptor evaluations, the list of <strong>Rotations</strong>, <strong>Evaluators</strong>, the <strong>Evaluation Start</strong>, and the <strong>Evaluation Finish</strong> determine which electives will be targeted for evaluation. <br /><br />Each preceptor for services in one of the selected <strong>Rotations</strong> which ends between the <strong>Evaluation Start</strong> and the <strong>Evaluation Finish</strong> for learners in the <strong>Evaluators</strong> list will require/allow an evaluation to be completed on it."); ?>
							</td>
						</tr>
						<?php
					break;
					case "self" :
						?>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<?php echo display_notice("When creating self evaluations, the list of evaluators also acts as the target, as learners can only evaluate themselves."); ?>
							</td>
						</tr>
						<?php
					break;
					default :
						?>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<?php echo display_notice("The target that you have selected is not currently available."); ?>
							</td>
						</tr>
						<?php
						application_log("error", "Unaccounted for target_shortname [".$target_details["target_shortname"]."] encountered. An update to api-targets.inc.php is required.");
					break;
				}

				if ($target_details["target_shortname"] != "peer" && $target_details["target_shortname"] != "student" && $target_details["target_shortname"] != "resident") {
					?>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top">
							<label for="PickList" class="form-required">Select Students</label>
							<div class="content-small"><strong>Hint:</strong> Select the student or students you would like to evaluate the teachers above.</div>
						</td>
						<td style="vertical-align: top">
							<table style="width: 100%" cellspacing="0" cellpadding="0">
								<colgroup>
									<col style="width: 4%" />
									<col style="width: 96%" />
								</colgroup>
								<tbody>
									<tr>
										<td style="vertical-align: top"><input<?php echo (isset($target_data["evaluation_evaluators"][0]["evaluator_type"]) && $target_data["evaluation_evaluators"][0]["evaluator_type"] == "cohort" ? " checked=\"checked\"" : ""); ?> type="radio" name="target_group_type" id="target_group_type_cohort" value="cohort" onclick="selectTargetGroupOption(this.value)" style="vertical-align: middle" /></td>
										<td style="padding-bottom: 15px">
											<label for="target_group_type_cohort" class="radio-group-title">Entire class must complete this evaluation</label>
											<div class="content-small">This evaluation must be completed by everyone in the selected class.</div>
										</td>
									</tr>
									<tr class="target_group cohort_target">
										<td></td>
										<td style="vertical-align: middle" class="content-small">
											<label for="cohort" class="form-required">All students in</label>
											<select id="cohort" name="cohort" style="width: 203px; vertical-align: middle">
												<?php
												$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
												if (isset($active_cohorts) && !empty($active_cohorts)) {
													foreach ($active_cohorts as $cohort) {
														echo "<option value=\"".$cohort["group_id"]."\"".((($target_data["evaluation_evaluators"][0]["evaluator_type"] == "cohort") && ($target_data["evaluation_evaluators"][0]["evaluator_value"] == $cohort["group_id"])) ? " selected=\"selected\"" : "").">".html_encode($cohort["group_name"])."</option>\n";
													}
												}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td style="vertical-align: top"><input type="radio" name="target_group_type" id="target_group_type_percentage" value="percentage" onclick="selectTargetGroupOption(this.value)" style="vertical-align: middle" /></td>
										<td style="padding-bottom: 15px">
											<label for="target_group_type_percentage" class="radio-group-title">Percentage of class must complete this evaluation</label>
											<div class="content-small">This evaluation must be completed by certain percentage of students in the selected class.</div>
										</td>
									</tr>
									<tr class="target_group percentage_target">
										<td>&nbsp;</td>
										<td style="vertical-align: middle" class="content-small">
											<input type="text" class="percentage" id="percentage_percent" name="percentage_percent" style="width: 30px; vertical-align: middle" maxlength="3" value="100" /> <label for="percentage_cohort" class="form-required">of the</label>
											<select id="percentage_cohort" name="percentage_cohort" style="width: 203px; vertical-align: middle">
											<?php
											$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
											if (isset($active_cohorts) && !empty($active_cohorts)) {
												foreach ($active_cohorts as $cohort) {
													echo "<option value=\"".$cohort["group_id"]."\">".html_encode($cohort["group_name"])."</option>\n";
												}
											}
											?>
											</select>
										</td>
									</tr>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td style="vertical-align: top"><input<?php echo (isset($target_data["evaluation_evaluators"][0]["evaluator_type"]) && $target_data["evaluation_evaluators"][0]["evaluator_type"] == "cgroup_id" ? " checked=\"checked\"" : ""); ?> type="radio" name="target_group_type" id="target_group_type_cgroup_id" value="cgroup_id" onclick="selectTargetGroupOption(this.value)" style="vertical-align: middle" /></td>
										<td style="padding-bottom: 15px">
											<label for="target_group_type_cgroup_id" class="radio-group-title">Selected course groups must complete this evaluation</label>
											<div class="content-small">This evaluation must be completed by everyone in the selected course groups.</div>
										</td>
									</tr>
									<tr class="target_group cgroup_id_target">
										<td></td>
										<td style="vertical-align: middle" class="content-small">
											<label for="cgroup_ids" class="form-required">All students in</label>
											<select multiple="multiple" id="cgroup_ids" name="cgroup_ids[]" style="width: 450px; height: 200px; vertical-align: top;"><?php
												$query = "SELECT * FROM `course_groups` AS a
															JOIN `courses` AS b
															ON a.`course_id` = b.`course_id`
															WHERE b.`course_active` = 1
															AND a.`active` = 1
															AND b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation()."
															ORDER BY b.`course_name`,
																LENGTH(a.`group_name`),
																a.`group_name` ASC";
												$temp_course_groups = $db->GetAll($query);
												$course_groups = array();
												if ($temp_course_groups) {
													foreach ($temp_course_groups as $temp_course_group) {
														$course_groups[$temp_course_group["cgroup_id"]] = $temp_course_group;
													}
												}
												$evaluator_cgroup_ids = array();
												foreach ($target_data["evaluation_evaluators"] as $evaluator) {
													if ($evaluator["evaluator_type"] == "cgroup_id") {
														$evaluator_cgroup_ids[] = $evaluator["evaluator_value"];
													}
												}
												if (isset($course_groups) && !empty($course_groups)) {
													$last_course_name = false;
													foreach ($course_groups as $course_group) {
														if ($course_group["course_name"] && $last_course_name != $course_group["course_name"]) {
															if ($last_course_name) {
																echo "</optgroup>\n";
															}
															$last_course_name = $course_group["course_name"];
															echo "<optgroup label=\"".$course_group["course_name"].($course_group["course_code"] ? " - ".$course_group["course_code"] : "")."\">\n";
														}
														echo "<option value=\"".$course_group["cgroup_id"]."\"".(in_array($course_group["cgroup_id"], $evaluator_cgroup_ids) ? " selected=\"selected\"" : "").">".html_encode($course_group["group_name"])."</option>\n";
													}
													if ($last_course_name) {
														echo "</optgroup>\n";
													}
												}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr >
										<td style="vertical-align: top"><input<?php echo (isset($target_data["evaluation_evaluators"][0]["evaluator_type"]) && $target_data["evaluation_evaluators"][0]["evaluator_type"] == "proxy_id" ? " checked=\"checked\"" : ""); ?> type="radio" name="target_group_type" id="target_group_type_proxy_id" value="proxy_id" onclick="selectTargetGroupOption(this.value)" style="vertical-align: middle" /></td>
										<td style="padding-bottom: 15px">
											<label for="target_group_type_proxy_id" class="radio-group-title">Selected students must complete this evaluation</label>
											<div class="content-small">This evaluation must be completed only by the selected individuals.</div>
										</td>
									</tr>
									<tr class="target_group proxy_id_target">
										<td>&nbsp;</td>
										<td style="vertical-align: middle" class="content-small">
											<label for="student_name" class="form-required">Student Name</label>

											<input type="text" id="student_name" name="fullname" size="30" autocomplete="off" style="width: 203px; vertical-align: middle" />
											<div class="autocomplete" id="student_name_auto_complete"></div>

											<input type="hidden" id="associated_student" name="associated_student" />
											<input type="button" class="btn btn-small" id="add_associated_student" value="Add" style="vertical-align: middle" />
											<span class="content-small" style="margin-left: 3px; padding-top: 5px"><strong>e.g.</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?></span>
											<ul id="student_list" class="menu" style="margin-top: 15px">
												<?php
												if (($target_data["evaluation_evaluators"][0]["evaluator_type"] == "proxy_id") && is_array($target_data["evaluation_evaluators"]) && !empty($target_data["evaluation_evaluators"])) {
													foreach ($target_data["evaluation_evaluators"] as $evaluator) {
														$proxy_id = (int) $evaluator["evaluator_value"];
														?>
														<li class="community" id="student_<?php echo $proxy_id; ?>" style="cursor: move;"><?php echo get_account_data("fullname", $proxy_id); ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="student_list.removeItem('<?php echo $proxy_id; ?>');" class="list-cancel-image" /></li>
														<?php
													}
												}
												?>
											</ul>
											<input type="hidden" id="student_ref" name="student_ref" value="" />
											<input type="hidden" id="student_id" name="student_id" value="" />
										</td>
									</tr>
								</tbody>
							</table>
							<div id="scripts-on-open" style="display: none;">
								$('submittable_notice').update('&nbsp;');
								selectTargetGroupOption('<?php echo (isset($target_data["evaluation_evaluators"][0]["evaluator_type"]) ? $target_data["evaluation_evaluators"][0]["evaluator_type"] : 'cohort'); ?>');
								student_list = new AutoCompleteList({ type: 'student', url: '<?php echo ENTRADA_RELATIVE; ?>/api/personnel.api.php?type=student', remove_image: '<?php echo ENTRADA_RELATIVE; ?>/images/action-delete.gif' });
								<?php
								if (in_array($target_details["target_shortname"], array("course", "rotation_core", "preceptor"))) {
									?>
									$('directors_select').show();
									$('pcoordinators_select').show();
									$('tutors_select').hide();
									<?php
								} elseif ($target_details["target_shortname"] == "self") {
									?>
									$('tutors_select').show();
									$('directors_select').hide();
									$('pcoordinators_select').hide();
									<?php
								}
								?>
							</div>
						</td>
					</tr>
					<?php
				} elseif ($target_details["target_shortname"] == "peer") {
					?>
					<tr>
						<td colspan="3">
							<div id="scripts-on-open" style="display: none;">
								$('tutors_select').show();
								$('submittable_notice').update('<div class="display-notice"><ul><li>If you set the Min or Max Submittable for a Peer Evaluation to 0, the value will default to the number of targets available to evaluate.</li></ul></div>');
							</div>
						</td>
					</tr>
					<?php
				} elseif ($target_details["target_shortname"] == "student" || $target_details["target_shortname"] == "resident") {
					?>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="evalfaculty_name" class="form-required">Faculty Evaluators</label></td>
						<td>
							<input type="hidden" name="target_group_type" id="target_group_type_faculty" value="faculty" style="vertical-align: middle" />
							<div id="scripts-on-open" style="display: none;">
								faculty_list = new AutoCompleteList(
									{
										type: 'evalfaculty',
										url: '<?php echo ENTRADA_RELATIVE; ?>/api/personnel.api.php?type=evalfaculty',
										remove_image: '<?php echo ENTRADA_RELATIVE; ?>/images/action-delete.gif'
									});
								$('tutors_select').show();
								$('directors_select').hide();
								$('pcoordinators_select').hide();
								$('submittable_notice').update('&nbsp;');
							</div>
							<input type="text" id="evalfaculty_name" name="fullname" size="30" autocomplete="off" style="width: 203px; vertical-align: middle" />
							<div class="autocomplete" id="evalfaculty_name_auto_complete"></div>
							<input type="hidden" id="associated_evalfaculty" name="associated_evalfaculty" />
							<input type="button" class="btn btn-small" id="add_associated_evalfaculty" value="Add" style="vertical-align: middle" />
							<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
							<ul id="evalfaculty_list" class="menu" style="margin-top: 15px">
								<?php
								if (($target_data["evaluation_evaluators"][0]["evaluator_type"] == "proxy_id") && is_array($target_data["evaluation_evaluators"]) && !empty($target_data["evaluation_evaluators"])) {
									foreach ($target_data["evaluation_evaluators"] as $evaluator) {
										$proxy_id = (int) $evaluator["evaluator_value"];
										?>
										<li class="community" id="evalfaculty_<?php echo $proxy_id; ?>" style="cursor: move;"><?php echo get_account_data("fullname", $proxy_id); ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="faculty_list.removeItem('<?php echo $proxy_id; ?>');" class="list-cancel-image" /></li>
										<?php
									}
								}
								?>
							</ul>
							<input type="hidden" id="evalfaculty_ref" name="evalfaculty_ref" value="" />
							<input type="hidden" id="evalfaculty_id" name="evalfaculty_id" value="" />
						</td>
					</tr>
					<?php
				}
			}
		} else {
			$organisation[$ENTRADA_USER->getActiveOrganisation()] = array("text" => fetch_organisation_title($ENTRADA_USER->getActiveOrganisation()), "value" => "organisation_" . $ENTRADA_USER->getActiveOrganisation(), "category" => true);

			switch ($options_for) {
				case "cohorts" : // Classes
					/**
					 * Cohorts.
					 */
					if ((isset($target_data["evaluation_target_cohorts"]))) {
						$associated_targets = explode(',', $target_data["evaluation_target_cohorts"]);
						if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
							foreach($associated_targets as $target_id) {
								if (strpos($target_id, "group") !== false) {
									if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
										$query = "	SELECT * FROM `groups`
													WHERE `group_id` = ".$db->qstr($group_id)."
													AND `group_active` = 1";
										$result	= $db->GetRow($query);
										if ($result) {
											$target_data["associated_cohort_ids"][] = $group_id;
										}
									}
								}
							}
						}
					}

					$groups = $organisation;
					$groups_results = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
					if ($groups_results) {
						foreach ($groups_results as $group) {
							if (isset($target_data["associated_cohort_ids"]) && is_array($target_data["associated_cohort_ids"]) && in_array($group["group_id"], $target_data["associated_cohort_ids"])) {
								$checked = "checked=\"checked\"";
							} else {
								$checked = "";
							}

							$groups[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $group["group_name"], "value" => "group_" . $group["group_id"], "checked" => $checked);
						}

						echo lp_multiple_select_popup("cohorts", $groups, array("title" => "Select Cohorts of Learners:", "submit_text" => "Close", "submit" => true));
					} else {
						echo display_notice("There are no cohorts of learners available.");
					}
				break;
				case "course_groups" :
					/**
					 * Course Groups
					 */
					if (isset($target_data["evaluation_target_course_groups"])) {
						$associated_targets = explode(',', $target_data["evaluation_target_course_groups"]);
						if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
							foreach($associated_targets as $target_id) {
								if (strpos($target_id, "cgroup") !== false) {
									if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
										$query	= "	SELECT *
													FROM `course_groups`
													WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
													AND (`active` = '1')";
										$result	= $db->GetRow($query);
										if ($result) {
											$target_data["associated_cgroup_ids"][] = $cgroup_id;
										}
									}
								}
							}
						}
					}

					$groups = $organisation;

					$query = "SELECT a.*, b.`course_name`, b.`course_code` FROM `course_groups` AS a
								JOIN `courses` AS b
								ON a.`course_id` = b.`course_id`
								WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								ORDER BY b.`course_name`,
									LENGTH(a.`group_name`),
									a.`group_name` ASC";
					$groups_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
					if ($groups_results) {
						$last_course_category = "";
						foreach ($groups_results as $group) {
							if ($last_course_category != $group["course_name"]) {
								$last_course_category = $group["course_name"];
								$groups[$group["course_id"]] = array("text" => $group["course_name"], "value" => "course_" . $group["course_id"], "category" => true);
							}
							if (isset($target_data["associated_cgroup_ids"]) && is_array($target_data["associated_cgroup_ids"]) && in_array($group["cgroup_id"], $target_data["associated_cgroup_ids"])) {
								$checked = "checked=\"checked\"";
							} else {
								$checked = "";
							}

							$groups[$group["course_id"]]["options"][] = array("text" => $group["group_name"].($group["course_code"] ? " - ".$group["course_code"] : ""), "value" => "cgroup_" . $group["cgroup_id"], "checked" => $checked);
						}

						echo lp_multiple_select_popup("course_groups", $groups, array("title" => "Select Course Specific Small Groups:", "submit_text" => "Close", "submit" => true));
					} else {
						//echo display_notice("There are no small groups in the course you have selected.");
					}
				break;
				case "students" : // Students
					/**
					 * Learners
					 */
					if ((isset($target_data["evaluation_target_students"]))) {
						$associated_targets = explode(',', $target_data["evaluation_target_students"]);
						if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
							foreach($associated_targets as $target_id) {
								if (strpos($target_id, "student") !== false) {
									if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
										$query = "	SELECT a.*
													FROM `".AUTH_DATABASE."`.`user_data` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
													ON a.`id` = b.`user_id`
													WHERE a.`id` = ".$db->qstr($proxy_id)."
													AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
													AND b.`account_active` = 'true'
													AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
													AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
										$result	= $db->GetRow($query);
										if ($result) {
											$target_data["associated_proxy_ids"][] = $proxy_id;
										}
									}
								}
							}
						}
					}

					$students = $organisation;

					$query = "	SELECT a.`id` AS `proxy_id`, a.`organisation_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
								AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
								AND b.`group` = 'student'
								AND a.`grad_year` >= ".$db->qstr((fetch_first_year() - 4)).
								(($ENTRADA_USER->getGroup() == "student") ? " AND a.`id` = ".$db->qstr($ENTRADA_USER->getID()) : "")."
								GROUP BY a.`id`
								ORDER BY a.`grad_year` DESC, a.`lastname` ASC, a.`firstname` ASC";
					$student_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
					if ($student_results) {
						foreach ($student_results as $student) {
							if (isset($target_data["associated_proxy_ids"]) && is_array($target_data["associated_proxy_ids"]) && in_array($student["proxy_id"], $target_data["associated_proxy_ids"])) {
								$checked = "checked=\"checked\"";
							} else {
								$checked = "";
							}

							$students[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $student["fullname"], "value" => "student_".$student["proxy_id"], "checked" => $checked);
						}

						echo lp_multiple_select_popup("students", $students, array("title" => "Select Individual Learners:", "submit_text" => "Close", "submit" => true));
					} else {
						echo display_notice("There are no students available.");
					}
				break;
				case "residents" : // Residents
					if ((isset($target_data["evaluation_target_residents"]))) {
						$associated_targets = explode(',', $target_data["evaluation_target_residents"]);
						if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
							foreach($associated_targets as $target_id) {
								if (strpos($target_id, "resident") !== false) {
									if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
										$query = "	SELECT a.*
													FROM `".AUTH_DATABASE."`.`user_data` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
													ON a.`id` = b.`user_id`
													WHERE a.`id` = ".$db->qstr($proxy_id)."
													AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
													AND b.`account_active` = 'true'
													AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
													AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
										$result	= $db->GetRow($query);
										if ($result) {
											$target_data["associated_proxy_ids"][] = $proxy_id;
										}
									}
								}
							}
						}
					}

					$residents = $organisation;

					$query = "	SELECT a.`id` AS `proxy_id`, a.`organisation_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
								AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
								AND b.`group` = 'student'
								GROUP BY a.`id`
								ORDER BY a.`grad_year` DESC, a.`lastname` ASC, a.`firstname` ASC";
					$resident_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
					if ($resident_results) {
						foreach ($resident_results as $resident) {
							if (isset($target_data["associated_proxy_ids"]) && is_array($target_data["associated_proxy_ids"]) && in_array($resident["proxy_id"], $target_data["associated_proxy_ids"])) {
								$checked = "checked=\"checked\"";
							} else {
								$checked = "";
							}

							$residents[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $resident["fullname"], "value" => "resident_".$g["proxy_id"], "checked" => $checked);
						}

						echo lp_multiple_select_popup("residents", $residents, array("title" => "Select Individual Learners:", "submit_text" => "Close", "submit" => true));
					} else {
						echo display_notice("There are no residents available.");
					}
				break;
				default :
					application_log("notice", "Unknown evaluation target filter type [" . $options_for . "] provided to evaluation targets API.");
				break;
			}
		}
	}

	public static function processTargets ($target_data, $PROCESSED = array()) {
		global $db;
		if (!isset($PROCESSED["eform_id"]) || !$PROCESSED["eform_id"]) {
			$PROCESSED["eform_id"] = 0;
		}
		if ((isset($target_data["form_id"]) && ($eform_id = clean_input($target_data["form_id"], "int"))) || (isset($target_data["eform_id"]) && ($eform_id = clean_input($target_data["eform_id"], "int"))) || (isset($PROCESSED["eform_id"]) && ($eform_id = clean_input($PROCESSED["eform_id"], "int")))) {
			$PROCESSED["eform_id"] = $eform_id;
			$query = "	SELECT a.*, b.`target_id`, b.`target_shortname`
						FROM `evaluation_forms` AS a
						LEFT JOIN `evaluations_lu_targets` AS b
						ON b.`target_id` = a.`target_id`
						WHERE a.`eform_id` = ".$db->qstr($eform_id)."
						AND a.`form_active` = '1'";
			$result = $db->GetRow($query);
			if ($result) {
				$evaluation_target_id = $result["target_id"];
				$evaluation_target_type = $result["target_shortname"];
			} else {
				add_error("The <strong>Evaluation Form</strong> that you selected is not currently available for use.");
			}
		}
		/**
		 * Processing for evaluation_targets table.
		 */
		switch ($evaluation_target_type) {
			case "course" :
				if (isset($target_data["course_ids"]) && is_array($target_data["course_ids"]) && !empty($target_data["course_ids"])) {
					foreach ($target_data["course_ids"] as $course_id) {
						$course_id = clean_input($course_id, "int");
						if ($course_id) {
							$query = "SELECT `course_id` FROM `courses` WHERE `course_id` = ".$db->qstr($course_id);
							$result = $db->GetRow($query);
							if ($result) {
									$PROCESSED["evaluation_targets"][] = array("target_value" => $result["course_id"], "target_type" => "course_id");
							}
						}
					}

					if (empty($PROCESSED["evaluation_targets"])) {
						add_error("You must select at least one <strong>course</strong> that you would like to have evaluated.");
					}
				} else {
					add_error("You must select <strong>which courses</strong> you would like to have evaluated.");
				}
			break;
			case "teacher" :
				if (isset($target_data["teacher_ids"]) && is_array($target_data["teacher_ids"]) && !empty($target_data["teacher_ids"])) {
					foreach ($target_data["teacher_ids"] as $proxy_id) {
						$proxy_id = clean_input($proxy_id, "int");
						if ($proxy_id) {
							$query = "	SELECT a.`id` AS `proxy_id`
										FROM `".AUTH_DATABASE."`.`user_data` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
										ON b.`user_id` = a.`id`
										WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
										AND (b.`group` = 'faculty' OR
											(b.`group` = 'resident' AND b.`role` = 'lecturer')
										)
										AND a.`id` = ".$db->qstr($proxy_id);
							$result = $db->GetOne($query);
							if ($result) {
									$PROCESSED["evaluation_targets"][] = array("target_value" => $proxy_id, "target_type" => "proxy_id");
							}
						}
					}

					if (empty($PROCESSED["evaluation_targets"])) {
						add_error("You must select at least one <strong>teacher</strong> that you would like to have evaluated.");
					}
				} else {
					add_error("You must select <strong>which teachers</strong> you would like to have evaluated.");
				}
			break;
			case "resident" :
				if (isset($target_data["resident_ids"]) && is_array($target_data["resident_ids"]) && !empty($target_data["resident_ids"])) {
					foreach ($target_data["resident_ids"] as $proxy_id) {
						$proxy_id = clean_input($proxy_id, "int");
						if ($proxy_id) {
							$query = "	SELECT a.`id` AS `proxy_id`
										FROM `".AUTH_DATABASE."`.`user_data` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
										ON b.`user_id` = a.`id`
										WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
										AND b.`group` = 'student'
										AND a.`id` = ".$db->qstr($proxy_id);
							$result = $db->GetOne($query);
							if ($result) {
									$PROCESSED["evaluation_targets"][] = array("target_value" => $proxy_id, "target_type" => "proxy_id");
							}
						}
					}

					if (empty($PROCESSED["evaluation_targets"])) {
						add_error("You must select at least one <strong>learner</strong> that you would like to have evaluated.");
					}
				} else {
					add_error("You must select <strong>which residents</strong> you would like to have evaluated.");
				}
			break;
			case "peer" :
			case "student" :
				/**
				 * Cohorts.
				 */
				if ((isset($target_data["evaluation_target_cohorts"]))) {
					$associated_targets = explode(',', $target_data["evaluation_target_cohorts"]);
					if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
						foreach($associated_targets as $target_id) {
							if (strpos($target_id, "group") !== false) {
								if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
									$query = "	SELECT *
												FROM `groups`
												WHERE `group_id` = ".$db->qstr($group_id)."
												AND `group_active` = 1";
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_cohort_ids"][] = $group_id;
										$PROCESSED["evaluation_targets"][] = array("target_value" => $group_id, "target_type" => "cohort");
									}
								}
							}
						}
					}
				} elseif (isset($target_data["evaluation_id"]) && $target_data["evaluation_id"]) {
					$query = "	SELECT * FROM `groups` AS a
								JOIN `evaluation_targets` AS b
								ON b.`target_value` = a.`group_id`
								AND b.`target_type` = 'group_id'
								AND b.`evaluation_id` = ".$db->qstr($target_data["evaluation_id"])."
								AND `group_active` = 1";
					$results	= $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$PROCESSED["associated_cohort_ids"][] = $result["group_id"];
							$PROCESSED["evaluation_targets"][] = array("target_value" => $result["group_id"], "target_type" => "cohort");
						}
					}
				}
				/**
				 * Course Groups
				 */
				if (isset($target_data["evaluation_target_course_groups"])) {
					$associated_targets = explode(',', $target_data["evaluation_target_course_groups"]);
					if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
						foreach($associated_targets as $target_id) {
							if (strpos($target_id, "cgroup") !== false) {
								if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
									$query = "	SELECT *
												FROM `course_groups`
												WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
												AND (`active` = '1')";
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_cgroup_ids"][] = $cgroup_id;
										$PROCESSED["evaluation_targets"][] = array("target_value" => $cgroup_id, "target_type" => "cgroup_id");
									}
								}
							}
						}
					}
				} elseif (isset($target_data["evaluation_id"]) && $target_data["evaluation_id"]) {
					$query = "	SELECT * FROM `course_groups` AS a
								JOIN `evaluation_targets` AS b
								ON b.`target_value` = a.`cgroup_id`
								AND b.`target_type` = 'cgroup_id'
								WHERE a.`active` = 1
								AND b.`evaluation_id` = ".$db->qstr($target_data["evaluation_id"]);
					$results	= $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$PROCESSED["associated_cgroup_ids"][] = $result["cgroup_id"];
							$PROCESSED["evaluation_targets"][] = array("target_value" => $result["cgroup_id"], "target_type" => "cgroup_id");
						}
					}
				}
				/**
				 * Learners
				 */
				if ((isset($target_data["evaluation_target_students"]))) {
					$associated_targets = explode(',', $target_data["evaluation_target_students"]);
					if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
						foreach($associated_targets as $target_id) {
							if (strpos($target_id, "student") !== false) {
								if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
									$query = "	SELECT a.*
												FROM `".AUTH_DATABASE."`.`user_data` AS a
												LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
												ON a.`id` = b.`user_id`
												WHERE a.`id` = ".$db->qstr($proxy_id)."
												AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
												AND b.`account_active` = 'true'
												AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
												AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_proxy_ids"][] = $proxy_id;
										$PROCESSED["evaluation_targets"][] = array("target_value" => $proxy_id, "target_type" => "proxy_id");
									}
								}
							}
						}
					}
				} elseif (isset($target_data["evaluation_id"]) && $target_data["evaluation_id"]) {
					$query = "	SELECT a.*
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								JOIN `evaluation_targets` AS c
								ON a.`id` = c.`target_value`
								AND c.`target_type` = 'proxy_id'
								WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
								AND c.`evaluation_id` = ".$target_data["evaluation_id"]."
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
					$results	= $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$PROCESSED["associated_proxy_ids"][] = $result["proxy_id"];
							$PROCESSED["evaluation_targets"][] = array("target_value" => $result["proxy_id"], "target_type" => "proxy_id");
						}
					}
				}
			break;
			case "rotation_core" :
				/**
				 * Core Rotations
				 */
				if ((isset($target_data["evaluation_target_rotations"]))) {
					$associated_targets = explode(',', $target_data["evaluation_target_rotations"]);
					if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
						foreach($associated_targets as $target_id) {
							if (strpos($target_id, "rotation") !== false) {
								if ($rotation_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
									$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
												WHERE `rotation_id` = ".$db->qstr($rotation_id)."
												AND `rotation_id` != ".$db->qstr(MAX_ROTATION);
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_rotation_ids"][] = $rotation_id;
										$PROCESSED["evaluation_targets"][] = array("target_value" => $rotation_id, "target_type" => "rotation_id");
									}
								}
							}
						}
					}
				}
			break;
			case "rotation_elective" :
				$query = "SELECT `rotation_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` WHERE `rotation_title` LIKE '%Elective%'";
				$elective_rotation_id = $db->GetOne($query);
				if (!$elective_rotation_id) {
					$elective_rotation_id = 0;
				}
				$PROCESSED["associated_rotation_ids"][] = $elective_rotation_id;
				$PROCESSED["evaluation_targets"][] = array("target_value" => $elective_rotation_id, "target_type" => "rotation_id");
			break;
			case "preceptor" :
				/**
				 * Rotations
				 */
				if ((isset($target_data["evaluation_target_rotations"]))) {
					$associated_targets = explode(',', $target_data["evaluation_target_rotations"]);
					if ((isset($associated_targets)) && (is_array($associated_targets)) && (count($associated_targets))) {
						foreach($associated_targets as $target_id) {
							if (strpos($target_id, "rotation") !== false) {
								if ($rotation_id = clean_input(preg_replace("/[a-z_]/", "", $target_id), array("trim", "int"))) {
									$query = "	SELECT *
												FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
												WHERE `rotation_id` = ".$db->qstr($rotation_id);
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_rotation_ids"][] = $rotation_id;
										$PROCESSED["evaluation_targets"][] = array("target_value" => $rotation_id, "target_type" => "rotation_id");
									}
								}
							}
						}
					}
				}

			break;
			case "self" :
				$PROCESSED["evaluation_targets"][] = array("target_value" => 0, "target_type" => "self");
			break;
			default :
				add_error("The form type you have selected is currently unavailable. The system administrator has been notified of this issue, please try again later.");

				application_log("error", "Unaccounted for target_shortname [".$evaluation_target_type."] encountered. An update to add.inc.php is required.");
			break;
		}
		return $PROCESSED;
	}

	public static function getTargetsArray ($evaluation_id, $evaluator_id = 0, $evaluator_proxy_id = 0, $simple = true, $available_only = false, $recent = false, $request_id = false, $mandatory_only = false) {
		global $db, $ENTRADA_USER;

		if (!$evaluator_proxy_id && isset($ENTRADA_USER) && $ENTRADA_USER->getProxyId()) {
			$evaluator_proxy_id = $ENTRADA_USER->getProxyId();
		} elseif ($evaluator_id) {
			$query = "SELECT * FROM `evaluation_evaluators` WHERE `eevaluator_id` = ".$db->qstr($evaluator_id);
			$evaluator = $db->GetRow($query);
			if ($evaluator["evaluator_type"] == "proxy_id") {
                            $evaluator_proxy_id = $evaluator["evaluator_value"];
			} elseif ($evaluator["evaluator_type"] == "cgroup_id") {
                            $cgroup_id = $evaluator["evaluator_value"];
            }
		}

		$evaluation_targets = array();

		$query = "SELECT * FROM `evaluations` AS a
					JOIN `evaluation_forms` AS b
					ON a.`eform_id` = b.`eform_id`
					JOIN `evaluations_lu_targets` AS c
					ON b.`target_id` = c.`target_id`
					WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
		$evaluation = $db->GetRow($query);
		if ($evaluation) {
			if (isset($evaluation["allow_repeat_targets"]) && $evaluation["allow_repeat_targets"] == 1) {
				$available_only = false;
			}

            if (!$evaluation["max_submittable"] && $evaluation["target_shortname"] != "peer" && $evaluation["allow_repeat_targets"]) {
                $evaluation["max_submittable"] = 2147483647;
            }

			if ($evaluator_id) {
				$query = "SELECT * FROM `evaluation_evaluators`
							WHERE `eevaluator_id` = ".$db->qstr($evaluator_id)."
							AND `evaluation_id` = ".$db->qstr($evaluation_id);
				$evaluator_record = $db->getRow($query);
			}
			switch ($evaluation["target_shortname"]) {
				case "self" :
					if ($simple) {
						$evaluation_targets[] = $evaluator_proxy_id;
					} else {
						$query = "SELECT *, b.`id` AS `proxy_id` FROM `evaluation_targets` AS a
									JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON b.`id` = ".$db->qstr($evaluator_proxy_id)."
									WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
									AND a.`target_type` = 'self'
									AND a.`target_active` = 1";
						$evaluation_target_record = $db->GetRow($query);
						if ($evaluation_target_record) {
							$evaluation_targets[] = $evaluation_target_record;
						}
					}
				break;
				case "peer" :
					if ($evaluator) {
						switch ($evaluator["evaluator_type"]) {
							case "cgroup_id" :
                                if ($evaluator_proxy_id && $available_only) {
                                    $unavailable_proxy_ids_string = "";
                                    $query = "SELECT `target_record_id` FROM `evaluation_progress`
												WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
												AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
												AND `progress_value` = 'complete'";
                                    $unavailable_proxy_ids = $db->GetAll($query);
                                    if ($unavailable_proxy_ids) {
                                        foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                            $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["target_record_id"]);
                                        }
                                    }
                                }
								$query = "SELECT ".($simple ? "a.`proxy_id`" : "b.*, c.*, a.`proxy_id`")." FROM `course_group_audience` AS a
											JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON a.`proxy_id` = b.`id`
											JOIN `evaluation_targets` AS c
											ON c.`target_value` = a.`cgroup_id`
											AND c.`target_type` = 'cgroup_id'
											AND c.`evaluation_id` = ".$db->qstr($evaluation_id)."
											WHERE a.`cgroup_id` = ".$db->qstr($evaluator["evaluator_value"])."
											".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`proxy_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
											AND a.`active` = 1
											GROUP BY b.`id`";
								$evaluation_target_users = $db->GetAll($query);
								if ($evaluation_target_users) {
									foreach ($evaluation_target_users as $evaluation_target_user) {
										if ($evaluation_target_user["proxy_id"] != $evaluator_proxy_id) {
											if ($simple) {
												$evaluation_targets[] = $evaluation_target_user["proxy_id"];
											} else {
												$evaluation_targets[] = $evaluation_target_user;
											}
										}
									}
								}
							break;
							case "cohort" :
                                if ($evaluator_proxy_id && $available_only) {
                                    $unavailable_proxy_ids_string = "";
                                    $query = "SELECT `target_record_id` FROM `evaluation_progress`
												WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
												AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
												AND `progress_value` = 'complete'";
                                    $unavailable_proxy_ids = $db->GetAll($query);
                                    if ($unavailable_proxy_ids) {
                                        foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                            $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["target_record_id"]);
                                        }
                                    }
                                }
								$query = "SELECT ".($simple ? "a.`proxy_id`" : "*")." FROM `group_members` AS a
											JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON a.`proxy_id` = b.`id`
											JOIN `evaluation_targets` AS c
											ON c.`target_value` = a.`group_id`
											AND c.`target_type` = 'cohort'
											AND c.`evaluation_id` = ".$db->qstr($evaluation_id)."
											WHERE a.`group_id` = ".$db->qstr($evaluator["evaluator_value"])."
											".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`proxy_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
											AND a.`member_active` = 1
											GROUP BY b.`id`";
								$evaluation_target_users = $db->GetAll($query);
								if ($evaluation_target_users) {
									foreach ($evaluation_target_users as $evaluation_target_user) {
										if ($evaluation_target_user["proxy_id"] != $evaluator_proxy_id) {
											if ($simple) {
												$evaluation_targets[] = $evaluation_target_user["proxy_id"];
											} else {
												$evaluation_targets[] = $evaluation_target_user;
											}
										}
									}
								}
							break;
							case "proxy_id" :
                                if ($evaluator_proxy_id && $available_only) {
                                    $unavailable_proxy_ids_string = "";
                                    $query = "SELECT `etarget_id` FROM `evaluation_progress`
												WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
												AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
												AND `progress_value` = 'complete'";
                                    $unavailable_proxy_ids = $db->GetAll($query);
                                    if ($unavailable_proxy_ids) {
                                        foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                            $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["etarget_id"]);
                                        }
                                    }
                                }
								$query = "SELECT *, b.`id` AS `proxy_id` FROM `evaluation_targets` AS a
											JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON a.`target_value` = b.`id`
											WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
											AND a.`target_type` = 'proxy_id'
											".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`etarget_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
											AND a.`target_active` = 1";
								$evaluation_target_records = $db->GetAll($query);
								if ($evaluation_target_records) {
									foreach ($evaluation_target_records as $evaluation_target_record) {
										if ($evaluation_target_record["target_value"] != $evaluator_proxy_id) {
											if ($simple) {
												$evaluation_targets[] = $evaluation_target_record["target_value"];
											} else {
												$query = "SELECT *, a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a
															JOIN `evaluation_targets` AS b
															ON b.`target_value` = a.`id`
															AND b.`target_type` = 'proxy_id'
															AND b.`evaluation_id` = ".$db->qstr($evaluation_id)."
															WHERE a.`id` = ".$db->qstr($evaluation_target_record["target_value"])."
															GROUP BY a.`id`";
												$evaluation_target_user = $db->GetRow($query);
												$evaluation_targets[] = $evaluation_target_user;
											}
										}
									}
								}
							break;
						}
                        if (!$simple) {
                            $sort_lastname = array();
                            $sort_firstname = array();
                            foreach ($evaluation_targets as $temp_target) {
                                $sort_lastname[] = $temp_target["lastname"];
                                $sort_firstname[] = $temp_target["firstname"];
                            }
                            array_multisort($sort_lastname, SORT_ASC, $sort_firstname, SORT_ASC, $evaluation_targets);
                        }
					}
				break;
				case "preceptor" :
				case "rotation_elective" :
				case "rotation_core" :
					$query = "SELECT ".($simple ? "b.`rotation_id`" : "*")." FROM `evaluation_targets` AS a
								JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS b
								ON a.`target_value` = b.`rotation_id`
								AND a.`target_type` = 'rotation_id'
								WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"]);
					$rotations = $db->GetAll($query);
					foreach ($rotations as $rotation) {
						$evaluated_event_ids_string = "";
						$fulfilled_event_ids_string = "";
						if ($evaluator_proxy_id) {
							if ($available_only) {
								$query = "SELECT a.`event_id` FROM `evaluation_progress_clerkship_events` AS a
											JOIN `evaluation_progress` AS b
											ON a.`eprogress_id` = b.`eprogress_id`
											WHERE b.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
											AND b.`proxy_id` = ".$db->qstr($evaluator_proxy_id)."
											GROUP BY a.`event_id`
											HAVING COUNT(a.`event_id`) >= ".$evaluation["max_submittable"];
								$evaluation_event_ids = $db->GetAll($query);
								if ($evaluation_event_ids) {
									foreach ($evaluation_event_ids as $event) {
										$evaluated_event_ids_string .= ($evaluated_event_ids_string ? ", " : "").$db->qstr($event["event_id"]);
									}
								}
							}
							if ($mandatory_only) {
								$query = "SELECT a.`event_id` FROM `evaluation_progress_clerkship_events` AS a
											JOIN `evaluation_progress` AS b
											ON a.`eprogress_id` = b.`eprogress_id`
											WHERE b.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
											AND b.`proxy_id` = ".$db->qstr($evaluator_proxy_id)."
											GROUP BY a.`event_id`
											HAVING COUNT(a.`event_id`) >= ".$evaluation["min_submittable"];
								$event_ids = $db->GetAll($query);
								if ($event_ids) {
									foreach ($event_ids as $event) {
										$fulfilled_event_ids_string .= ($fulfilled_event_ids_string ? ", " : "").$db->qstr($event["event_id"]);
									}
								}
							}
							$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`events` AS a
										JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
										ON a.`event_id` = b.`event_id`
										WHERE a.`rotation_id` = ".$db->qstr($rotation["rotation_id"])."
                                        AND a.`event_finish` >= ".$db->qstr($evaluation["evaluation_start"])."
										AND b.`etype_id` = ".$db->qstr($evaluator_proxy_id)."
										".($evaluated_event_ids_string && $available_only ? "AND a.`event_id` NOT IN (".$evaluated_event_ids_string.")" : "")."
										".($fulfilled_event_ids_string && $mandatory_only ? "AND a.`event_id` NOT IN (".$fulfilled_event_ids_string.")" : "")."
										".($recent ? "AND a.`event_finish` > ".$db->qstr(strtotime("-36 hours"))."" : "")."
										".(defined("CLERKSHIP_EVALUATION_LOCKOUT") && CLERKSHIP_EVALUATION_LOCKOUT ? "AND a.`event_finish` > ".$db->qstr(time() - CLERKSHIP_EVALUATION_LOCKOUT)."" : "")."
										".($mandatory_only && defined("CLERKSHIP_EVALUATION_TIMEOUT") && CLERKSHIP_EVALUATION_TIMEOUT ? "AND a.`event_finish` <= ".$db->qstr(time() - CLERKSHIP_EVALUATION_TIMEOUT) : "")."
										AND a.`event_finish` <= ".$db->qstr(strtotime("+5 days"));
							$events = $db->GetAll($query);
							if ($events) {
								foreach ($events as $event) {
									if ($simple) {
										$evaluation_targets[] = $event["event_id"];
									} else {
										$event = array_merge($event, $rotation);
										$evaluation_targets[] = $event;
									}
								}
							}
						}
					}
				break;
				case "student" :
					$query = "SELECT * FROM `evaluation_targets`
								WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
								AND `target_active` = 1";
					$evaluation_target_records = $db->GetAll($query);
					if ($evaluation_target_records) {
                                                $query = "SELECT `cgroup_id` FROM `course_group_contacts` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                                                $course_groups = $db->GetAll($query);
                                                if ($course_groups) {
                                                    $course_group_ids = array();
                                                    foreach ($course_groups as $course_group) {
                                                        $course_group_ids[] = $course_group["cgroup_id"];
                                                    }
                                                }
						foreach ($evaluation_target_records as $evaluation_target_record) {
							switch ($evaluation_target_record["target_type"]) {
								case "cgroup_id" :
                                    if (!isset($course_group_ids) || !count($course_group_ids) || array_search($evaluation_target_record["target_value"], $course_group_ids) !== false) {
                                        if ($evaluator_proxy_id && $available_only) {
                                            $unavailable_proxy_ids_string = "";
                                            $query = "SELECT `target_record_id` FROM `evaluation_progress`
                                                        WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
                                                        AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                                        AND `progress_value` = 'complete'";
                                            $unavailable_proxy_ids = $db->GetAll($query);
                                            if ($unavailable_proxy_ids) {
                                                foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                                    $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["target_record_id"]);
                                                }
                                            }
                                        }
                                        $query = "SELECT ".($simple ? "a.`proxy_id`" : "*")." FROM `course_group_audience` AS a
                                                                JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                                                ON a.`proxy_id` = b.`id`
                                                                WHERE a.`cgroup_id` = ".$db->qstr($evaluation_target_record["target_value"])."
                                                                ".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`proxy_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
                                                                AND a.`active` = 1
                                                                GROUP BY b.`id`";
                                        $evaluation_target_users = $db->GetAll($query);
                                        if ($evaluation_target_users) {
                                                foreach ($evaluation_target_users as $evaluation_target_user) {
                                                        if ($simple) {
                                                                $evaluation_targets[] = $evaluation_target_user["proxy_id"];
                                                        } else {
                                                                $evaluation_target_user = array_merge($evaluation_target_user, $evaluation_target_record);
                                                                $evaluation_targets[] = $evaluation_target_user;
                                                        }
                                                }
                                        }
                                    }
								break;
								case "cohort" :
                                    if ($evaluator_proxy_id && $available_only) {
                                        $unavailable_proxy_ids_string = "";
                                        $query = "SELECT `target_record_id` FROM `evaluation_progress`
                                                    WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
                                                    AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                                    AND `progress_value` = 'complete'";
                                        $unavailable_proxy_ids = $db->GetAll($query);
                                        if ($unavailable_proxy_ids) {
                                            foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                                $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["target_record_id"]);
                                            }
                                        }
                                    }
									$query = "SELECT ".($simple ? "a.`proxy_id`" : "*")." FROM `group_members` AS a
												JOIN `".AUTH_DATABASE."`.`user_data` AS b
												ON a.`proxy_id` = b.`id`
												WHERE a.`group_id` = ".$db->qstr($evaluation_target_record["target_value"])."
												".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`proxy_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
												AND a.`member_active` = 1
												GROUP BY b.`id`";
									$evaluation_target_users = $db->GetAll($query);
									if ($evaluation_target_users) {
										foreach ($evaluation_target_users as $evaluation_target_user) {
											if ($simple) {
												$evaluation_targets[] = $evaluation_target_user["proxy_id"];
											} else {
												$evaluation_target_user = array_merge($evaluation_target_user, $evaluation_target_record);
												$evaluation_targets[] = $evaluation_target_user;
											}
										}
									}
								break;
								case "proxy_id" :
									if ($simple) {
										$evaluation_targets[] = $evaluation_target_record["target_value"];
									} else {
                                        if ($evaluator_proxy_id && $available_only) {
                                            $unavailable_proxy_ids_string = "";
                                            $query = "SELECT `target_record_id` FROM `evaluation_progress`
														WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
														AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
														AND `progress_value` = 'complete'";
                                            $unavailable_proxy_ids = $db->GetAll($query);
                                            if ($unavailable_proxy_ids) {
                                                foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                                    $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["target_record_id"]);
                                                }
                                            }
                                        }
										$query = "SELECT *, `id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data`
													WHERE `id` = ".$db->qstr($evaluation_target_record["target_value"])."
													".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND `id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
													GROUP BY `id`";
										$evaluation_target_user = $db->GetRow($query);
                                        if ($evaluation_target_user) {
                                            $evaluation_target_user = array_merge($evaluation_target_user, $evaluation_target_record);
                                            $evaluation_targets[] = $evaluation_target_user;
                                        }
									}
								break;
							}
						}
					}
                    if (!$simple) {
                        $sort_lastname = array();
                        $sort_firstname = array();
                        foreach ($evaluation_targets as $temp_target) {
                            $sort_lastname[] = $temp_target["lastname"];
                            $sort_firstname[] = $temp_target["firstname"];
                        }
                        array_multisort($sort_lastname, SORT_ASC, $sort_firstname, SORT_ASC, $evaluation_targets);
                    }
				break;
				case "teacher" :
                    if (isset($cgroup_id) && $cgroup_id) {
                        if ($evaluator_proxy_id && $available_only) {
                            $unavailable_proxy_ids_string = "";
                            $query = "SELECT `etarget_id` FROM `evaluation_progress`
                                        WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
                                        AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                        AND `progress_value` = 'complete'";
                            $unavailable_proxy_ids = $db->GetAll($query);
                            if ($unavailable_proxy_ids) {
                                foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                    $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["etarget_id"]);
                                }
                            }
                        }
                        $query = "SELECT ".($simple ? "a.`target_value` as `proxy_id`" : "*, a.`target_value` as `proxy_id`")." FROM `evaluation_targets` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`target_value` = b.`id`
                                    AND a.`target_type` = 'proxy_id'
                                    JOIN `course_group_contacts` AS c
                                    ON c.`proxy_id` = b.`id`
                                    WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                    ".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`etarget_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
                                    AND c.`cgroup_id` = ".$db->qstr($cgroup_id)."
                                    AND a.`target_active` = 1";
                        $evaluation_target_users = $db->GetAll($query);
                        if ($evaluation_target_users) {
                            $target_found = true;
                            foreach ($evaluation_target_users as $evaluation_target_user) {
                                    if ($simple) {
                                            $evaluation_targets[] = $evaluation_target_user["proxy_id"];
                                    } else {
                                            $evaluation_targets[] = $evaluation_target_user;
                                    }
                            }
                            if (!$simple) {
                                $sort_lastname = array();
                                $sort_firstname = array();
                                foreach ($evaluation_targets as $temp_target) {
                                        $sort_lastname[] = $temp_target["lastname"];
                                        $sort_firstname[] = $temp_target["firstname"];
                                }
                                array_multisort($sort_lastname, SORT_ASC, $sort_firstname, SORT_ASC, $evaluation_targets);
                            }
                        } else {
                            $target_found = false;
                        }
                    }
                    if (!isset($target_found) || !$target_found) {
                        if ($evaluator_proxy_id && $available_only) {
                            $unavailable_proxy_ids_string = "";
                            $query = "SELECT `etarget_id` FROM `evaluation_progress`
                                        WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
                                        AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                        AND `progress_value` = 'complete'";
                            $unavailable_proxy_ids = $db->GetAll($query);
                            if ($unavailable_proxy_ids) {
                                foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                    $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["etarget_id"]);
                                }
                            }
                        }
                        $query = "SELECT ".($simple ? "a.`target_value` as `proxy_id`" : "*, a.`target_value` as `proxy_id`")." FROM `evaluation_targets` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`target_value` = b.`id`
                                    AND a.`target_type` = 'proxy_id'
                                    WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                    ".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`etarget_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
                                    AND a.`target_active` = 1";
                        $evaluation_target_users = $db->GetAll($query);
                        if ($evaluation_target_users) {
                            foreach ($evaluation_target_users as $evaluation_target_user) {
                                if ($simple) {
                                    $evaluation_targets[] = $evaluation_target_user["proxy_id"];
                                } else {
                                    $evaluation_targets[] = $evaluation_target_user;
                                }
                            }
                        }
                        if (!$simple) {
                            $sort_lastname = array();
                            $sort_firstname = array();
                            foreach ($evaluation_targets as $temp_target) {
                                $sort_lastname[] = $temp_target["lastname"];
                                $sort_firstname[] = $temp_target["firstname"];
                            }
                            array_multisort($sort_lastname, SORT_ASC, $sort_firstname, SORT_ASC, $evaluation_targets);
                        }
                    }
				break;
				case "resident" :
                    if (isset($cgroup_id) && $cgroup_id) {
                        if ($evaluator_proxy_id && $available_only) {
                            $unavailable_proxy_ids_string = "";
                            $query = "SELECT `etarget_id` FROM `evaluation_progress`
                                        WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
                                        AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                        AND `progress_value` = 'complete'";
                            $unavailable_proxy_ids = $db->GetAll($query);
                            if ($unavailable_proxy_ids) {
                                foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                    $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["etarget_id"]);
                                }
                            }
                        }
                        $query = "SELECT ".($simple ? "a.`target_value` as `proxy_id`" : "*, a.`target_value` as `proxy_id`")." FROM `evaluation_targets` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`target_value` = b.`id`
                                    AND a.`target_type` = 'proxy_id'
                                    JOIN `course_group_contacts` AS c
                                    ON c.`proxy_id` = b.`id`
                                    WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                    ".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`etarget_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
                                    AND c.`cgroup_id` = ".$db->qstr($cgroup_id)."
                                    AND a.`target_active` = 1";
                        $evaluation_target_users = $db->GetAll($query);
                        if ($evaluation_target_users) {
                            $target_found = true;
                            foreach ($evaluation_target_users as $evaluation_target_user) {
                                    if ($simple) {
                                            $evaluation_targets[] = $evaluation_target_user["proxy_id"];
                                    } else {
                                            $evaluation_targets[] = $evaluation_target_user;
                                    }
                            }
                            if (!$simple) {
                                $sort_lastname = array();
                                $sort_firstname = array();
                                foreach ($evaluation_targets as $temp_target) {
                                        $sort_lastname[] = $temp_target["lastname"];
                                        $sort_firstname[] = $temp_target["firstname"];
                                }
                                array_multisort($sort_lastname, SORT_ASC, $sort_firstname, SORT_ASC, $evaluation_targets);
                            }
                        } else {
                            $target_found = false;
                        }
                    }
                    if (!isset($target_found) || !$target_found) {
                        if ($evaluator_proxy_id && $available_only) {
                            $unavailable_proxy_ids_string = "";
                            $query = "SELECT `etarget_id` FROM `evaluation_progress`
                                        WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
                                        AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                        AND `progress_value` = 'complete'";
                            $unavailable_proxy_ids = $db->GetAll($query);
                            if ($unavailable_proxy_ids) {
                                foreach ($unavailable_proxy_ids as $unavailable_proxy_id) {
                                    $unavailable_proxy_ids_string .= ($unavailable_proxy_ids_string ? ", " : "").$db->qstr($unavailable_proxy_id["etarget_id"]);
                                }
                            }
                        }
                        $query = "SELECT ".($simple ? "a.`target_value` as `proxy_id`" : "*, a.`target_value` as `proxy_id`")." FROM `evaluation_targets` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`target_value` = b.`id`
                                    AND a.`target_type` = 'proxy_id'
                                    WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                    ".($evaluator_proxy_id && $available_only && $unavailable_proxy_ids_string ? "AND a.`etarget_id` NOT IN (".$unavailable_proxy_ids_string.")" : "")."
                                    AND a.`target_active` = 1";
                        $evaluation_target_users = $db->GetAll($query);
                        if ($evaluation_target_users) {
                            foreach ($evaluation_target_users as $evaluation_target_user) {
                                if ($simple) {
                                    $evaluation_targets[] = $evaluation_target_user["proxy_id"];
                                } else {
                                    $evaluation_targets[] = $evaluation_target_user;
                                }
                            }
                        }
                        if (!$simple) {
                            $sort_lastname = array();
                            $sort_firstname = array();
                            foreach ($evaluation_targets as $temp_target) {
                                $sort_lastname[] = $temp_target["lastname"];
                                $sort_firstname[] = $temp_target["firstname"];
                            }
                            array_multisort($sort_lastname, SORT_ASC, $sort_firstname, SORT_ASC, $evaluation_targets);
                        }
                    }
				break;
				case "course" :
				default :
                    if ($evaluator_proxy_id && $available_only) {
                        $unavailable_course_ids_string = "";
                        $query = "SELECT `etarget_id` FROM `evaluation_progress`
									WHERE `proxy_id` = ".$db->qstr($evaluator_proxy_id)."
									AND `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
									AND `progress_value` = 'complete'";
                        $unavailable_course_ids = $db->GetAll($query);
                        if ($unavailable_course_ids) {
                            foreach ($unavailable_course_ids as $unavailable_course_id) {
                                $unavailable_course_ids_string .= ($unavailable_course_ids_string ? ", " : "").$db->qstr($unavailable_course_id["etarget_id"]);
                            }
                        }
                    }
					$query = "SELECT ".($simple ? "b.`course_id`" : "*")." FROM `evaluation_targets` AS a
								JOIN `courses` AS b
								ON a.`target_value` = b.`course_id`
								AND a.`target_type` = 'course_id'
								WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
								".($evaluator_proxy_id && $available_only && $unavailable_course_ids_string ? "AND a.`etarget_id` NOT IN (".$unavailable_course_ids_string.")" : "")."
								AND a.`target_active` = 1
								ORDER BY b.`course_code` ASC";
					$evaluation_target_courses = $db->GetAll($query);
					if ($evaluation_target_courses) {
						foreach ($evaluation_target_courses as $evaluation_target_course) {
							if ($simple) {
								$evaluation_targets[] = $evaluation_target_course["course_id"];
							} else {
								$evaluation_targets[] = $evaluation_target_course;
							}
						}
					}
				break;
			}
		}
        if ($request_id) {
            $query = "SELECT * FROM `evaluation_requests`
                        WHERE `erequest_id` = ".$db->qstr($request_id)."
                        AND `evaluation_id` = ".$db->qstr($evaluation_id)."
                        AND (
                            `request_expires` = 0
                            OR `request_expires` > ".$db->qstr(time())."
                        )
                        AND `target_proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
            $request = $db->GetRow($query);
            if ($request) {
                $temp_evaluation_targets = $evaluation_targets;
                $evaluation_targets = array();
                foreach ($temp_evaluation_targets as $evaluation_target) {
                    if ($simple) {
                        $temp_proxy_id = $evaluation_target;
                    } else {
                        $temp_proxy_id = $evaluation_target["proxy_id"];
                    }
                    $requests = Classes_Evaluation::getTargetRequests($temp_proxy_id, $evaluation_id, $request_id, ($request["request_code"] ? true : false));
                    if ($requests && count($requests)) {
                        if (!$simple && $requests[0]["erequest_id"] == $request_id) {
                            $evaluation_target["requested"] = true;
                        } elseif (!$simple) {
                            $evaluation_target["requested"] = false;
                        }
                        if ($evaluation["require_requests"]) {
                            $evaluation_targets[] = $evaluation_target;
                        }
                    }
                    if (!$evaluation["require_requests"]) {
                        $evaluation_targets[] = $evaluation_target;
                    }
                }
            } else {
                $temp_evaluation_targets = $evaluation_targets;
                $evaluation_targets = array();
                foreach ($temp_evaluation_targets as $evaluation_target) {
                    if ($simple) {
                        $temp_proxy_id = $evaluation_target;
                    } else {
                        $temp_proxy_id = $evaluation_target["proxy_id"];
                    }
                    $requests = Classes_Evaluation::getTargetRequests($temp_proxy_id, $evaluation_id);
                    if ($requests && count($requests)) {
                        if (!$simple && !count($evaluation_targets)) {
                            $evaluation_target["requested"] = true;
                        } elseif (!$simple) {
                            $evaluation_target["requested"] = false;
                        }
                        if ($evaluation["require_requests"]) {
                            $evaluation_targets[] = $evaluation_target;
                        }
                    }
                    if (!$evaluation["require_requests"]) {
                        $evaluation_targets[] = $evaluation_target;
                    }
                }
            }
        }

		return $evaluation_targets;
	}

	public static function getTargetRequests ($proxy_id, $evaluation_id = false, $request_id = false, $codes_only = false) {
		global $db;

        $output_requests = array();

        $query = "SELECT * FROM `evaluation_requests` AS a
             JOIN `evaluations` AS b
             ON a.`evaluation_id` = b.`evaluation_id`
             WHERE `proxy_id` = ".$db->qstr($proxy_id)."
             ".($evaluation_id ? "AND a.`evaluation_id` = ".$db->qstr($evaluation_id) : "")."
             ".($request_id ? "AND a.`erequest_id` = ".$db->qstr($request_id) : "")."
             ".($codes_only ? "AND a.`request_code` IS NOT NULL" : "")."
             AND (
                 a.`request_expires` = 0
                 OR a.`request_expires` > ".$db->qstr(time())."
             )
             AND a.`request_fulfilled` = 0";
         $evaluation_request = $db->GetRow($query);
         if ($evaluation_request) {
             $output_requests[] = $evaluation_request;
         }
		if (!$codes_only) {
            $query = "SELECT * FROM `evaluation_requests` AS a
                JOIN `evaluations` AS b
                ON a.`evaluation_id` = b.`evaluation_id`
                WHERE `proxy_id` = ".$db->qstr($proxy_id)."
                ".($evaluation_id ? "AND a.`evaluation_id` = ".$db->qstr($evaluation_id) : "")."
                ".($request_id ? "AND a.`erequest_id` != ".$db->qstr($request_id) : "")."
                ".($codes_only ? "AND a.`request_code` IS NOT NULL" : "")."
                AND (
                    a.`request_expires` = 0
                    OR a.`request_expires` > ".$db->qstr(time())."
                )
                AND a.`request_fulfilled` = 0";
            $evaluation_requests = $db->GetAll($query);
            if ($evaluation_requests) {
                foreach ($evaluation_requests as $temp_request) {
                    $output_requests[] = $temp_request;
                }
            }
        }

        return $output_requests;
	}

	public static function getEvaluationsPending ($evaluation, $recent = false) {
		global $db;

		$pending_evaluations = array();

		$query = "SELECT * FROM `evaluation_evaluators` WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"]);
		$evaluators = $db->GetAll($query);
		foreach ($evaluators as $evaluator) {
			$evaluator_users = Classes_Evaluation::getEvaluatorUsers($evaluator, true);
			foreach ($evaluator_users as $evaluator_user) {
				$temp_evaluation = Classes_Evaluation::getUserPendingEvaluation($evaluation, $evaluator, $evaluator_user, $recent);
				if ($temp_evaluation) {
                    if ($evaluation["event_id"]) {
                        $temp_evaluation["event_id"] = $evaluation["event_id"];
                    }
					$pending_evaluations[] = $temp_evaluation;
				}
			}
		}
		return $pending_evaluations;
	}

	public static function getOverdueEvaluations ($evaluation) {
		global $db;

		$overdue_evaluations = array();

		$query = "SELECT * FROM `evaluation_evaluators` WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"]);
		$evaluators = $db->GetAll($query);
		foreach ($evaluators as $evaluator) {
			$evaluator_users = Classes_Evaluation::getEvaluatorUsers($evaluator, true);
			foreach ($evaluator_users as $evaluator_user) {
				$temp_evaluation = Classes_Evaluation::getUserOverdueEvaluation($evaluation, $evaluator, $evaluator_user);
				if ($temp_evaluation) {
					$overdue_evaluations[] = $temp_evaluation;
				}
			}
		}
		return $overdue_evaluations;
	}

	public static function getEvaluatorUsers ($evaluator, $available_only = false) {
		global $db;

		switch ($evaluator["evaluator_type"]) {
			case "proxy_id" :
				$query = "SELECT *, CONCAT_WS(', ', `lastname`, `firstname`) AS `fullname`, `id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data`
							WHERE `id` = ".$db->qstr($evaluator["evaluator_value"]).( $available_only ? "
							AND `id` NOT IN (
								SELECT `proxy_id` FROM `evaluation_evaluator_exclusions`
								WHERE `evaluation_id` = ".$db->qstr($evaluator["evaluation_id"])."
							)" : "");
			break;
			case "cohort" :
				$query = "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a
							JOIN `group_members` AS b
							ON a.`id` = b.`proxy_id`
							WHERE b.`group_id` = ".$db->qstr($evaluator["evaluator_value"])."
							AND b.`member_active` = 1".( $available_only ? "
							AND a.`id` NOT IN (
								SELECT `proxy_id` FROM `evaluation_evaluator_exclusions`
								WHERE `evaluation_id` = ".$db->qstr($evaluator["evaluation_id"])."
							)" : "");
			break;
			case "cgroup_id" :
				$query = "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a
							JOIN `course_group_audience` AS b
							ON a.`id` = b.`proxy_id`
							WHERE b.`cgroup_id` = ".$db->qstr($evaluator["evaluator_value"])."
							AND b.`active` = 1".( $available_only ? "
							AND a.`id` NOT IN (
								SELECT `proxy_id` FROM `evaluation_evaluator_exclusions`
								WHERE `evaluation_id` = ".$db->qstr($evaluator["evaluation_id"])."
							)" : "");
			break;
			case "organisation_id" :
				$query = "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a
							JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON a.`id` = b.`user_id`
							AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							WHERE b.`organisation_id` = ".$db->qstr($evaluator["evaluator_value"])."
							AND b.`account_active` = 'true'".( $available_only ? "
							AND a.`id` NOT IN (
								SELECT `proxy_id` FROM `evaluation_evaluator_exclusions`
								WHERE `evaluation_id` = ".$db->qstr($evaluator["evaluation_id"])."
							)" : "");
			break;
		}
		if ($query) {
			$evaluator_users = array();
			$evaluator_user_records = $db->GetAll($query);
			if ($evaluator_user_records) {
				foreach ($evaluator_user_records as $evaluator_user) {
					$evaluator_users[$evaluator_user["id"]] = $evaluator_user;
				}
				return $evaluator_users;
			}
		}
		return false;
	}

	public static function getUserPendingEvaluation ($evaluation, $evaluator, $evaluator_user, $recent = false) {
		global $db;
		switch ($evaluation["target_shortname"]) {
			case "self" :
			case "peer" :
			case "student" :
			case "resident" :
			case "teacher" :
			case "course" :
			default :
				if ($evaluation["max_submittable"]) {
					$query = "SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
								WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
								AND `proxy_id` = ".$db->qstr($evaluator_user["id"])."
								AND `progress_value` = 'complete'";
					$submissions_count = $db->GetOne($query);
				}
				if ((!isset($submissions_count) || !$submissions_count || $submissions_count < $evaluation["max_submittable"]) && ($evaluation["evaluation_start"] <= time() && $evaluation["evaluation_start"] >= strtotime("-1 day"))) {
					$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluator["eevaluator_id"], $evaluator_user["id"], true, true);
				}
			break;
			case "preceptor" :
			case "rotation_core" :
			case "rotation_elective" :
				$temp_evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluator["eevaluator_id"], $evaluator_user["id"], true, true, $recent);
                if ($evaluation["event_id"]) {
                    foreach ($temp_evaluation_targets as $key => $evaluation_target) {
                        if ($evaluation["event_id"] == $evaluation_target) {
                            $evaluation_targets[] = $evaluation_target;
                        }
                    }
                } else {
                    $evaluation_targets = $temp_evaluation_targets;
                }
			break;
		}
		if (isset($evaluation_targets) && $evaluation_targets) {
			$evaluation["evaluator"] = $evaluator;
			$evaluation["user"] = $evaluator_user;
			$evaluation["targets"] = $evaluation_targets;
			return $evaluation;
		} else {
			return false;
		}
	}

	public static function getUserOverdueEvaluation ($evaluation, $evaluator, $evaluator_user) {
		global $db;
		if ($evaluation["evaluation_mandatory"] && ($evaluation["min_submittable"] || $evaluation["target_shortname"] == "peer")) {
			switch ($evaluation["target_shortname"]) {
				case "peer" :
					if (!$evaluation["min_submittable"]) {
						$evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluator["eevaluator_id"], $evaluator_user["id"], true, false);
						$evaluation["min_submittable"] = (count($evaluation_targets_list) ? count($evaluation_targets_list) : 0);
					}
				case "self" :
				case "student" :
				case "resident" :
				case "teacher" :
				case "course" :
				default :
					$query = "SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
								WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
								AND `proxy_id` = ".$db->qstr($evaluator_user["id"])."
								AND `progress_value` = 'complete'";
					$submissions_count = $db->GetOne($query);
					if ((!isset($submissions_count) || !$submissions_count || $submissions_count < $evaluation["min_submittable"]) && ($evaluation["evaluation_finish"] <= time() && $evaluation["evaluation_start"] <= strtotime("-1 day"))) {
						$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluator["eevaluator_id"], $evaluator_user["id"], true, true);
					}
				break;
				case "preceptor" :
				case "rotation_core" :
				case "rotation_elective" :
					$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluator["eevaluator_id"], $evaluator_user["id"], true, true, false, false, true);
				break;
			}
			if (isset($evaluation_targets) && $evaluation_targets) {
				$evaluation["evaluator"] = $evaluator;
				$evaluation["user"] = $evaluator_user;
				$evaluation["targets"] = $evaluation_targets;
				return $evaluation;
			} else {
				return false;
			}
		}
	}

	public static function loadProgress($eprogress_id = 0) {
		global $db;

		$output = array();

		if ($eprogress_id = (int) $eprogress_id) {
		/**
			 * Grab the specified progress identifier, but you better be sure this
			 * is the correct one, and the results are being returned to the proper
			 * user.
		 */
			$query		= "	SELECT *
							FROM `evaluation_progress` AS a
							JOIN `evaluations` AS b
							ON a.`evaluation_id` = b.`evaluation_id`
							WHERE a.`eprogress_id` = ".$db->qstr($eprogress_id);
			$progress	= $db->GetRow($query);
			if ($progress) {
			/**
			 * Add all of the qquestion_ids to the $output array so they're set.
			 */
				$query		= "SELECT * FROM `evaluation_form_questions` AS a JOIN `evaluations_lu_questions` AS b ON a.`equestion_id` = b.`equestion_id` WHERE a.`eform_id` = ".$db->qstr($progress["eform_id"])." ORDER BY a.`question_order` ASC";
				$questions	= $db->GetAll($query);
				if ($questions) {
					foreach ($questions as $question) {
						$output[$question["equestion_id"]] = 0;
					}
				} else {
					return false;
				}

				/**
				 * Update the $output array with any currently selected responses.
				 */
				$query		= "SELECT a.*, b.`equestion_id`
                                                        FROM `evaluation_responses` AS a
                                                        JOIN `evaluation_form_questions` AS b
                                                        ON a.`efquestion_id` = b.`efquestion_id`
                                                        WHERE a.`eprogress_id` = ".$db->qstr($eprogress_id);
				$responses	= $db->GetAll($query);
				if ($responses) {
					foreach ($responses as $response) {
						$output[$response["equestion_id"]] = array();
						$output[$response["equestion_id"]]["eqresponse_id"] = $response["eqresponse_id"];
						$output[$response["equestion_id"]]["comments"] = $response["comments"];
					}
				}
			} else {
				return false;
			}
		}

		return $output;
	}

	public static function getMinimumPassingLevel ($equestion_id) {
		global $db;

		$query = "SELECT `response_order` FROM `evaluations_lu_question_response`
					WHERE `equestion_id` = ".$db->qstr($equestion_id)."
					AND `minimum_passing_level` = 1";
		$minimum_passing_level = $db->GetOne($query);
		if ($minimum_passing_level) {
			return $minimum_passing_level;
		} else {
			return 0;
		}
	}

	public static function getThresholdNotificationRecipients ($evaluation_id, $eprogress_id, $eevaluator_id) {
		global $db;

		$notification_recipients = array();

		$query = "SELECT * FROM `evaluations`
					WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
		$evaluation = $db->GetRow($query);

		if (isset($evaluation["threshold_notifications_type"]) && $evaluation["threshold_notifications_type"] && $evaluation["threshold_notifications_type"] != "disabled") {
			$query = "SELECT * FROM `evaluation_progress` AS a
						JOIN `evaluation_targets` AS b
						ON b.`etarget_id` = a.`etarget_id`
						JOIN `evaluation_evaluators` AS c
						ON a.`evaluation_id` = c.`evaluation_id`
						WHERE a.`eprogress_id` = ".$db->qstr($eprogress_id)."
						AND c.`eevaluator_id` = ".$db->qstr($eevaluator_id);
			$evaluation_progress = $db->GetRow($query);
			switch ($evaluation["threshold_notifications_type"]) {
				case "authors" :
				default :
					$query = "SELECT b.*, b.`id` AS `proxy_id` FROM `evaluation_form_contacts` AS a
								JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								WHERE a.`eform_id` = ".$db->qstr($evaluation["eform_id"])."
								AND a.`contact_role` = 'author'";
					$notification_recipients = $db->GetAll($query);
				break;
				case "reviewers" :
					$query = "SELECT b.*, b.`id` AS `proxy_id` FROM `evaluation_contacts` AS a
								JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
								AND a.`contact_role` = 'reviewer'";
					$notification_recipients = $db->GetAll($query);
				break;
				case "tutors" :
					if ($evaluation_progress["target_value"]) {
						$query = "SELECT b.*, b.`id` AS `proxy_id` FROM `course_group_contacts` AS a
									JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON a.`proxy_id` = b.`id`
									WHERE a.`cgroup_id` = ".$db->qstr($evaluation_progress["target_value"]);
					} else {
						$query = "SELECT b.*, b.`id` AS `proxy_id` FROM `course_group_contacts` AS a
									JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON a.`proxy_id` = b.`id`
									WHERE a.`cgroup_id` = ".$db->qstr($evaluation_progress["evaluator_value"]);
					}
					$notification_recipients = $db->GetAll($query);
				break;
				case "directors" :
				case "pcoordinators" :
				case "ccoordinators" :
					$contact_type = rtrim($evaluation["threshold_notifications_type"], "s");
					if ($evaluation_progress["target_type"] == "rotation_id") {
						$query = "SELECT `course_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` WHERE `rotation_id` = ".$db->qstr($evaluation_progress["target_value"]);
						$course_id = $db->GetOne($query);
					} elseif ($evaluation_progress["target_type"] == "course_id") {
						$course_id = $evaluation_progress["target_value"];
					}
					if ($course_id) {
						$query = "SELECT b.*, b.`id` AS `proxy_id` FROM `course_contacts` AS a
									JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON a.`proxy_id` = b.`id`
									WHERE a.`course_id` = ".$db->qstr($course_id)."
									AND a.`contact_type` = ".$db->qstr($contact_type).($contact_type == "pcoordinator" ? "
									UNION
									SELECT b.*, b.`id` AS `proxy_id` FROM `courses` AS a
									JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON a.`pcoord_id` = b.`id`
									WHERE a.`course_id` = ".$db->qstr($course_id) : "");
						$notification_recipients = $db->GetAll($query);
					}
				break;
			}
		}
		return $notification_recipients;
	}

	public static function getReviewPermissions ($evaluation_id) {
		global $db, $ENTRADA_USER, $ENTRADA_ACL;

		$permissions = array();

		$query = "SELECT a.`allow_target_review`, a.`max_submittable`, c.`target_shortname` FROM `evaluations` AS a
					JOIN `evaluation_forms` AS b
					ON a.`eform_id` = b.`eform_id`
					JOIN `evaluations_lu_targets` AS c
					ON b.`target_id` = c.`target_id`
					WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
		$evaluation = $db->GetRow($query);
		if ($evaluation) {
			if ($evaluation["allow_target_review"]) {
				$query = "SELECT `target_type`, `target_value` FROM `evaluation_targets`
							WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
				$evaluation_targets = $db->GetAll($query);
				if ($evaluation_targets) {
					switch ($evaluation["target_shortname"]) {
						case "preceptor" :
                            if (!$reviewer_only || $specific_target == $ENTRADA_USER->getActiveId()) {
                                $permissions[] = array("preceptor_proxy_id" => $ENTRADA_USER->getActiveId(), "target_type" => "rotation_id", "contact_type" => "preceptor");
                            }
                            if (is_department_head($ENTRADA_USER->getActiveId(), false)) {
                                $query = "SELECT `department_id` FROM `" . AUTH_DATABASE . "`.`department_heads` WHERE `user_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
                                $head_department_ids = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                            } elseif ($ENTRADA_ACL->amIAllowed("evaluationreport", "read", true)) {
                                $query = "SELECT `department_id` FROM `" . AUTH_DATABASE . "`.`departments` WHERE `entity_id` = 3";
                                $head_department_ids = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                            }
                            if (isset($head_department_ids) && $head_department_ids) {
                                $department_ids_string = "";
                                $department_ids_to_check = array();
                                foreach ($head_department_ids as $department) {
                                    $department_ids_to_check[] = $department["department_id"];
                                    $department_ids_string .= ($department_ids_string ? ", " : "").$db->qstr($department["department_id"]);
                                }
								unset($head_department_ids);
                                $index = 0;
                                while (count($department_ids_to_check) >= ($index + 1) && $index < 1000) {
                                    $query = "SELECT `department_id` FROM `" . AUTH_DATABASE . "`.`departments`
                                                        WHERE `parent_id` = ".$db->qstr($department_ids_to_check[$index]);
                                    $child_departments = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                    if ($child_departments) {
                                        foreach ($child_departments as $department) {
                                            $department_ids_to_check[] = $department["department_id"];
                                            $department_ids_string .= ($department_ids_string ? ", " : "").$db->qstr($department["department_id"]);
                                        }
                                    }
                                    $index++;
                                }
                                $query = "SELECT `user_id` FROM `" . AUTH_DATABASE . "`.`user_departments`
                                                    WHERE `dep_id` IN (".$department_ids_string.")
                                                    ".($specific_target ? "AND `user_id` = ".$db->qstr($specific_target) : "")."
                                                    GROUP BY `user_id`";
                                $department_users = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                if ($department_users) {
                                    $department_user_ids_string = "";
                                    $department_user_ids = array();
                                    foreach ($department_users as $department_user) {
                                        $department_user_ids_string .= ($department_user_ids_string ? ", " : "").$db->qstr($department_user["user_id"]);
                                        $department_user_ids[] = $department_user["user_id"];
                                    }
                                    if ($department_user_ids) {
                                        $permissions[] = array("target_values" => $department_user_ids, "target_values_string" => $department_user_ids_string, "target_type" => "proxy_id", "contact_type" => "department_head");
                                    }
									unset($department_users);
                                }
                            }
                        break;
						case "rotation_core" :
						case "rotation_elective" :
							$rotation_ids_string = "";
							foreach ($evaluation_targets as $evaluation_target) {
								if ($evaluation_target["target_type"] == "rotation_id") {
									$rotation_ids_string .= ($rotation_ids_string ? ", " : "").$db->qstr($evaluation_target["target_value"]);
								}
							}
							if ($rotation_ids_string) {
								$query = "SELECT b.`contact_type`, a.`rotation_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
												JOIN `course_contacts` AS b
												ON a.`course_id` = b.`course_id`
												AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
												WHERE a.`rotation_id` IN (".$rotation_ids_string.")
												AND b.`contact_type` IN ('director', 'pcoordinator')";
								$courses_contacts = $db->GetAll($query);
								if ($courses_contacts) {
									foreach ($courses_contacts as $courses_contact) {
										$permissions[] = array("target_value" => $courses_contact["rotation_id"], "target_type" => "rotation_id", "contact_type" => $courses_contact["contact_type"]);
									}
									unset($courses_contacts);
								}
								unset($rotation_ids_string);
							}
						break;
						case "course" :
							if ($evaluation["target_shortname"] == "course") {
								$course_ids_string = "";
								foreach ($evaluation_targets as $evaluation_target) {
									if ($evaluation_target["target_type"] == "course_id") {
										$course_ids_string .= ($course_ids_string ? ", " : "").$db->qstr($evaluation_target["target_value"]);
									}
								}

								if ($course_ids_string) {
									$query = "SELECT `course_id`, `contact_type` FROM `course_contacts`
													WHERE `course_id` IN (" . $course_ids_string . ")
													AND `proxy_id` = " . $db->qstr($ENTRADA_USER->getActiveId()) . "
													AND `contact_type` IN ('director', 'pcoordinator')";
									$courses_contacts = $db->GetAll($query);
									if ($courses_contacts) {
										foreach ($courses_contacts as $courses_contact) {
											$permissions[] = array("target_value" => $courses_contact["course_id"], "target_type" => "course_id", "contact_type" => $courses_contact["contact_type"]);
										}
										unset($courses_contacts);
									}
								}
							}
						break;
						case "peer" :
							$skip_evaluator_check = false;
							$cgroup_ids_string = "";
							foreach ($evaluation_targets as $evaluation_target) {
								if ($evaluation_target["target_type"] == "cgroup_id") {
									$cgroup_ids_string .= ($cgroup_ids_string ? ", " : "").$db->qstr($evaluation_target["target_value"]);
								}
							}

							if ($cgroup_ids_string) {
								$query = "SELECT `cgroup_id` FROM `course_group_contacts`
												WHERE `cgroup_id` IN (".$cgroup_ids_string.")
												AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
								$tutor_record = $db->GetRow($query);
								if ($tutor_record) {
									$permissions[] = array("target_value" => $tutor_record["cgroup_id"], "target_type" => "cgroup_id", "contact_type" => "tutor");
									$skip_evaluator_check = true;
									unset($tutor_record);
								}
							}

							if (!$skip_evaluator_check) {
                                if (!$reviewer_only) {
                                    $cohort_ids = groups_get_enrolled_group_ids($ENTRADA_USER->getActiveId(), false, $ENTRADA_USER->getActiveOrganisation(), false);
                                    $cohort_ids_string = "";
                                    if (isset($cohort_ids) && is_array($cohort_ids)) {
                                        foreach ($cohort_ids as $cohort_id) {
                                            $cohort_ids_string .= ($cohort_ids_string ? ", " : "") . $db->qstr($cohort_id);
                                        }
                                    }

                                    $query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
                                                JOIN `course_groups` AS b
                                                ON a.`cgroup_id` = b.`cgroup_id`
                                                WHERE a.`proxy_id` = " . $db->qstr($ENTRADA_USER->getActiveId()) . "
                                                AND a.`active` = 1
                                                AND b.`active` = 1";
                                    $course_groups = $db->GetAll($query);

                                    $cgroup_ids_string = "";
                                    if (isset($course_groups) && is_array($course_groups)) {
                                        foreach ($course_groups as $course_group) {
                                            if ($cgroup_ids_string) {
                                                $cgroup_ids_string .= ", " . $db->qstr($course_group["cgroup_id"]);
                                            } else {
                                                $cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
                                            }
                                        }
										unset($course_groups);
                                    }
                                }
								$query = "	SELECT b.`eevaluator_id` FROM `evaluations` AS a
											JOIN `evaluation_evaluators` AS b
											ON a.`evaluation_id` = b.`evaluation_id`
											WHERE
											(
												(
													b.`evaluator_type` = 'proxy_id'
													AND b.`evaluator_value` = ".$db->qstr($ENTRADA_USER->getActiveId())."
												)
												".(isset($cohort_ids_string) && $cohort_ids_string ? " OR (
													b.`evaluator_type` = 'cohort'
													AND b.`evaluator_value` IN (".$cohort_ids_string.")
												)" : "").($cgroup_ids_string ? " OR (
													b.`evaluator_type` = 'cgroup_id'
													AND b.`evaluator_value` IN (".$cgroup_ids_string.")
												)" : "")."
											)
											AND a.`evaluation_start` < ".$db->qstr(time())."
											AND a.`evaluation_active` = 1
											AND a.`evaluation_id` = ".$db->qstr($evaluation_id)."
											ORDER BY a.`evaluation_finish` DESC";
								$evaluator_records = $db->GetAll($query);
								if ($evaluator_records) {
									if ($evaluation["max_submittable"] == 0) {
										$evaluation_targets_list = array();
										foreach ($evaluator_records as $evaluator_record) {
											$temp_targets = Classes_Evaluation::getTargetsArray($evaluation_id, $evaluator_record["eevaluator_id"], $ENTRADA_USER->getActiveId());
											foreach ($temp_targets as $temp_target) {
												$evaluation_targets_list[] = $temp_target;
											}
										}
										if ($evaluation_targets_list) {
											$max_submittable = count($evaluation_targets_list);
										}
										unset($evaluator_records);
									} else {
										$max_submittable = $evaluation["max_submittable"];
									}
									if (isset($max_submittable) && $max_submittable) {
										$query = "SELECT `eprogress_id` FROM `evaluation_progress`
													WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
													AND `progress_value` = 'complete'
													AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
										$eprogress_ids = $db->GetAll($query);
										if ($eprogress_ids) {
											if ($max_submittable <= count($eprogress_ids)) {
												$permissions[] = array("target_record_id" => $ENTRADA_USER->getActiveId(), "contact_type" => "target");
											}
											unset($eprogress_ids);
										}
									}
								}
							}
						break;
						case "teacher" :
							foreach ($evaluation_targets as $evaluation_target) {
								if ($evaluation_target["target_type"] == "proxy_id") {
                                    if (!$reviewer_only && $evaluation_target["target_value"] == $ENTRADA_USER->getActiveId()) {
                                        $permissions[] = array("target_value" => $ENTRADA_USER->getActiveId(), "target_type" => "proxy_id", "contact_type" => "faculty");
                                    }
                                    if (is_department_head($ENTRADA_USER->getActiveId(), false)) {
                                        $query = "SELECT `department_id` FROM `" . AUTH_DATABASE . "`.`department_heads` WHERE `user_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
                                        $head_department_ids = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                    } elseif ($ENTRADA_ACL->amIAllowed("evaluationreport", "read", true)) {
                                        $query = "SELECT `department_id` FROM `" . AUTH_DATABASE . "`.`departments` WHERE `entity_id` = 3";
                                        $head_department_ids = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                    }
                                    if (isset($head_department_ids) && $head_department_ids) {
                                        $department_ids_string = "";
                                        $department_ids_to_check = array();
                                        foreach ($head_department_ids as $department) {
                                            $department_ids_to_check[] = $department["department_id"];
                                            $department_ids_string .= ($department_ids_string ? ", " : "").$db->qstr($department["department_id"]);
                                        }
										unset($head_department_ids);
                                        $index = 0;
                                        while (count($department_ids_to_check) >= ($index + 1) && $index < 1000) {
                                            $query = "SELECT `department_id` FROM `" . AUTH_DATABASE . "`.`departments`
                                                        WHERE `parent_id` = ".$db->qstr($department_ids_to_check[$index]);
                                            $child_departments = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                            if ($child_departments) {
                                                foreach ($child_departments as $department) {
                                                    $department_ids_to_check[] = $department["department_id"];
                                                    $department_ids_string .= ($department_ids_string ? ", " : "").$db->qstr($department["department_id"]);
                                                }
                                            }
                                            $index++;
                                        }
                                        $query = "SELECT `user_id` FROM `" . AUTH_DATABASE . "`.`user_departments`
                                                    WHERE `dep_id` IN (".$department_ids_string.")
                                                    ".($specific_target ? "AND `user_id` = ".$db->qstr($specific_target) : "")."
                                                    GROUP BY `user_id`";
                                        $department_users = $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                        if ($department_users) {
                                            $department_user_ids_string = "";
                                            $department_user_ids = array();
                                            foreach ($department_users as $department_user) {
                                                $department_user_ids_string .= ($department_user_ids_string ? ", " : "").$db->qstr($department_user["user_id"]);
                                                $department_user_ids[] = $department_user["user_id"];
                                            }
                                            if ($department_user_ids) {
                                                $permissions[] = array("target_values" => $department_user_ids, "target_values_string" => $department_user_ids_string, "target_type" => "proxy_id", "contact_type" => "department_head");
                                            }
											unset($department_users);
                                        }
                                    }
								}
							}
						break;
						case "student" :
						case "resident" :
							$query = "SELECT `eprogress_id` FROM `evaluation_progress`
										WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
										AND `target_record_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
										AND `progress_value` = 'complete'";
							$progress_record = $db->GetOne($query);
							if ($progress_record) {
								$permissions[] = array("target_record_id" => $ENTRADA_USER->getActiveId(), "contact_type" => "target");
								unset($progress_record);
							}
						break;
						case "self" :
							$skip_evaluator_check = false;
							$query = "SELECT `evaluator_type`, `evaluator_value` FROM `evaluation_evaluators`
										WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
							$evaluation_evaluators = $db->GetAll($query);
							$cgroup_ids_string = "";
							if ($evaluation_evaluators) {
								foreach ($evaluation_evaluators as $evaluation_evaluator) {
									if ($evaluation_evaluator["evaluator_type"] == "cgroup_id") {
										$cgroup_ids_string .= ($cgroup_ids_string ? ", " : "").$db->qstr($evaluation_evaluator["evaluator_value"]);
									}
								}
								unset($evaluation_evaluators);
							}
							if ($cgroup_ids_string) {
								$query = "SELECT `cgroup_id` FROM `course_group_contacts`
													WHERE `cgroup_id` IN (" . $cgroup_ids_string . ")
													AND `proxy_id` = " . $db->qstr($ENTRADA_USER->getActiveId());
								$tutor_record = $db->GetRow($query);
								if ($tutor_record) {
									$permissions[] = array("target_value" => 0, "evaluator_type" => "cgroup_id", "evaluator_value" => $tutor_record["cgroup_id"], "target_type" => "self", "contact_type" => "tutor");
									$skip_evaluator_check = true;
									unset($tutor_record);
								}
							}
							if (!$skip_evaluator_check) {
                                $query = "SELECT `eprogress_id` FROM `evaluation_progress`
                                            WHERE `progress_value` = 'complete'
                                            AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
                                            AND `evaluation_id` = ".$db->qstr($evaluation_id);
                                $completed_evaluation = $db->getRow($query);
                                if ($completed_evaluation) {
                                    $permissions[] = array("target_record_id" => $ENTRADA_USER->getActiveId(), "contact_type" => "target");
									unset($completed_evaluation);
                                }
							}
						break;
					}
				}
			}
			$query = "SELECT `econtact_id` FROM `evaluation_contacts`
						WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
						AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND `contact_role` = 'reviewer'";
			$evaluation_contact = $db->GetOne($query);
			if ($evaluation_contact) {
				$permissions[] = array("contact_type" => "reviewer");
				unset($evaluation_contact);
			}
		}

		return $permissions;
	}

	public static function getReviewerEvaluations() {
		global $db, $ENTRADA_ACL;

		$query = "SELECT a.`evaluation_id`, a.`organisation_id`, a.`evaluation_title`, a.`evaluation_completions`, d.`target_shortname`, d.`target_title`, a.`evaluation_finish` FROM `evaluations` AS a
					JOIN `evaluation_targets` AS b
					ON a.`evaluation_id` = b.`evaluation_id`
					JOIN `evaluation_forms` AS c
					ON a.`eform_id` = c.`eform_id`
					JOIN `evaluations_lu_targets` AS d
					ON c.`target_id` = d.`target_id`
					GROUP BY a.`evaluation_id`";
		$evaluations = $db->GetAll($query);

		$output_evaluations = array();

		foreach ($evaluations as $evaluation) {
			$permissions = array();
            if ($ENTRADA_ACL->amIAllowed(new EvaluationResource($evaluation["evaluation_id"], $evaluation["organisation_id"], true), 'update')) {
                $evaluation["admin"] = true;
                $permissions[] = array("contact_type" => "reviewer");
            } else {
                $evaluation["admin"] = false;
            }
			if (!$permissions) {
				$permissions = Classes_Evaluation::getReviewPermissions($evaluation["evaluation_id"], $reviewer_only, $specific_target, $specific_target_type);
			}
            if ($permissions) {
                if (!$evaluation["admin"]) {
                    if (count($permissions) > 1) {
                        foreach ($permissions as $permission) {
                            if (isset($permission["contact_type"]) && $permission["contact_type"] == "reviewer") {
                                $evaluation["admin"] = true;
                            }
                        }
                    } elseif (isset($permissions[0]["contact_type"]) && $permissions[0]["contact_type"] == "reviewer") {
                        $evaluation["admin"] = true;
                    }
                }
                if (!$evaluation["admin"]) {
                    $progress_records = Classes_Evaluation::getProgressRecordsByPermissions($evaluation["evaluation_id"], $permissions, true, $evaluation["target_shortname"]);
                    if (isset($progress_records) && count($progress_records)) {
                        $evaluation["completed_attempts"] = count($progress_records);
                        $evaluation["permissions"] = $permissions;
                        $output_evaluations[] = $evaluation;
                    }
                    unset($progress_records);
                } else {
                    if (isset($evaluation["evaluation_completions"]) && $evaluation["evaluation_completions"]) {
                        $evaluation["completed_attempts"] = $evaluation["evaluation_completions"];
                        $evaluation["permissions"] = $permissions;
                        $output_evaluations[] = $evaluation;
                    }
                }
            }
		}

		return $output_evaluations;

	}

	public static function getTargetEvaluations() {
		global $db, $ENTRADA_USER, $ENTRADA_ACL;

        $cohort_ids = groups_get_enrolled_group_ids($ENTRADA_USER->getID(), false, $ENTRADA_USER->getActiveOrganisation(), false);
        $cohort_ids_string = "";
        if (isset($cohort_ids) && is_array($cohort_ids)) {
            foreach ($cohort_ids as $cohort_id) {
                $cohort_ids_string .= ($cohort_ids_string ? ", " : "").$db->qstr($cohort_id);
            }
        }

		$query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
					JOIN `course_groups` AS b
					ON a.`cgroup_id` = b.`cgroup_id`
					WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					AND a.`active` = 1
					AND b.`active` = 1";
		$course_groups = $db->GetAll($query);

		$cgroup_ids_string = "";
		if (isset($course_groups) && is_array($course_groups)) {
			foreach ($course_groups as $course_group) {
				if ($cgroup_ids_string) {
					$cgroup_ids_string .= ", ".$db->qstr($course_group["cgroup_id"]);
				} else {
					$cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
				}
			}
		}

		$query = "	SELECT * FROM `evaluations` AS a
					JOIN `evaluation_targets` AS b
					ON a.`evaluation_id` = b.`evaluation_id`
					JOIN `evaluation_forms` AS c
					ON a.`eform_id` = c.`eform_id`
					JOIN `evaluations_lu_targets` AS d
					ON c.`target_id` = d.`target_id`
					WHERE
					(
						(
							b.`target_type` = 'proxy_id'
							AND b.`target_value` = ".$db->qstr($ENTRADA_USER->getID())."
						)
						".(isset($cohort_ids_string) && $cohort_ids_string ? " OR (
							b.`target_type` = 'cohort'
							AND b.`target_value` IN (".$cohort_ids_string.")
						)" : "").($cgroup_ids_string ? " OR (
							b.`target_type` = 'cgroup_id'
							AND b.`target_value` IN (".$cgroup_ids_string.")
						)" : "")."
					)
					AND a.`evaluation_start` < ".$db->qstr(time())."
					AND a.`evaluation_active` = 1
					GROUP BY a.`evaluation_id`
					ORDER BY a.`evaluation_finish` DESC";
		$evaluations = $db->GetAll($query);

		return $evaluations;

	}

	public static function getFormAuthorPermissions($eform_id) {
		global $db, $ENTRADA_USER;

		$query = "SELECT * FROM `evaluation_form_contacts`
					WHERE `eform_id` = ".$db->qstr($eform_id)."
					AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					AND `contact_role` = 'author'";
		$permissions = $db->GetRow($query);
		if ($permissions) {
			return $permissions;
		} else {
			return false;
		}
	}

	public static function getProgressRecordsByPermissions ($evaluation_id, $permissions, $complete_only = false, $evaluation_type = false) {
		global $db;

		$progress_records = array();
		if (is_array($permissions) && count($permissions)) {
			foreach ($permissions as $permission) {
				if ($permission["contact_type"] == "reviewer") {
					$query = "SELECT *, a.`eprogress_id` FROM `evaluation_progress` AS a
								JOIN `evaluation_targets` AS b
								ON a.`etarget_id` = b.`etarget_id`
                                ".($evaluation_type && array_search($evaluation_type, array("preceptor", "rotation_core", "rotation_elective")) !== false ? "
								LEFT JOIN `evaluation_progress_clerkship_events` AS c
								ON a.`eprogress_id` = c.`eprogress_id`" : "")."
								WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
								AND a.`progress_value` = 'complete'";
					$progress_records = $db->GetAll($query);
					break;
				}
			}
			if (!count($progress_records)) {
				foreach ($permissions as $permission) {
					switch ($permission["contact_type"]) {
						case "target" :
							$query = "SELECT *, a.`eprogress_id` FROM `evaluation_progress` AS a
										JOIN `evaluation_targets` AS b
										ON a.`etarget_id` = b.`etarget_id`
                                        ".($evaluation_type && array_search($evaluation_type, array("preceptor", "rotation_core", "rotation_elective")) !== false ? "
                                        LEFT JOIN `evaluation_progress_clerkship_events` AS c
                                        ON a.`eprogress_id` = c.`eprogress_id`" : "")."
										WHERE a.`target_record_id` = ".$db->qstr($permission["target_record_id"])."
										AND a.`evaluation_id` = ".$db->qstr($evaluation_id);
							$temp_progress_records = $db->GetAll($query);
							if ($temp_progress_records) {
								foreach ($temp_progress_records as $temp_progress_record) {
									$progress_records[] = $temp_progress_record;
								}
							}
						break;
						case "faculty" :
						case "tutor" :
						case "director" :
						case "pcoordinator" :
                            $proxy_ids_string = "";
                            if (isset($permission["evaluator_type"]) && $permission["evaluator_type"] == "cgroup_id" && $permission["evaluator_value"]) {
                                $query = "SELECT `proxy_id` FROM `course_group_audience`
                                            WHERE `cgroup_id` = ".$db->qstr($permission["evaluator_value"])."
                                            AND `active` = 1";
                                $proxy_ids = $db->GetAll($query);
                                if ($proxy_ids) {
                                    foreach ($proxy_ids as $proxy_id) {
                                        $proxy_ids_string .= ($proxy_ids_string ? ", " : "").$db->qstr($proxy_id["proxy_id"]);
                                    }
                                }
                            }
                            $query = "SELECT *, a.`eprogress_id` FROM `evaluation_progress` AS a
                                        JOIN `evaluation_targets` AS b
                                        ON a.`etarget_id` = b.`etarget_id`
                                        ".($evaluation_type && array_search($evaluation_type, array("preceptor", "rotation_core", "rotation_elective")) !== false ? "
                                        LEFT JOIN `evaluation_progress_clerkship_events` AS c
                                        ON a.`eprogress_id` = c.`eprogress_id`" : "")."
                                        WHERE b.`target_type` = ".$db->qstr($permission["target_type"])."
                                        AND b.`target_value` = ".$db->qstr($permission["target_value"])."
                                        ".(isset($proxy_ids_string) && $proxy_ids_string ? "AND a.`proxy_id` IN (".$proxy_ids_string.")" : "")."
                                        AND a.`evaluation_id` = ".$db->qstr($evaluation_id);
                            $temp_progress_records = $db->GetAll($query);
                            if ($temp_progress_records) {
                                foreach ($temp_progress_records as $temp_progress_record) {
                                    $progress_records[] = $temp_progress_record;
                                }
                            }
						break;
						case "preceptor" :
							$query = "SELECT *, a.`eprogress_id` FROM `evaluation_progress` AS a
										JOIN `evaluation_targets` AS b
										ON a.`etarget_id` = b.`etarget_id`
										JOIN `evaluation_progress_clerkship_events` AS c
										ON a.`eprogress_id` = c.`eprogress_id`
										WHERE b.`target_type` = 'rotation_id'
										AND c.`preceptor_proxy_id` = ".$db->qstr($permission["preceptor_proxy_id"])."
										AND a.`evaluation_id` = ".$db->qstr($evaluation_id);
							$temp_progress_records = $db->GetAll($query);
							if ($temp_progress_records) {
								foreach ($temp_progress_records as $temp_progress_record) {
									$progress_records[] = $temp_progress_record;
								}
							}
						break;
					}
				}
			}
		}

		$output_progress_records = array();

		if ($complete_only) {
			foreach ($progress_records as $progress_record) {
				if ($progress_record["progress_value"] == "complete") {
					$output_progress_records[$progress_record["eprogress_id"]] = $progress_record;
				}
			}
		} else {
			$output_progress_records = $progress_records;
		}
		return $output_progress_records;
	}

	public static function responsesBelowThreshold($evaluation_id, $eprogress_id) {
		global $db;

		$query = "SELECT c.*, d.* FROM `evaluations` AS a
					JOIN `evaluation_forms` AS b
					ON a.`eform_id` = b.`eform_id`
					JOIN `evaluation_form_questions` AS c
					ON b.`eform_id` = c.`eform_id`
					JOIN `evaluations_lu_questions` AS d
					ON c.`equestion_id` = d.`equestion_id`
					WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id);
		$evaluation_questions = $db->GetAll($query);
		if ($evaluation_questions) {
			foreach ($evaluation_questions as $evaluation_question) {
				$query = "SELECT `response_order` FROM `evaluations_lu_question_responses`
							WHERE `equestion_id` = ".$db->qstr($evaluation_question["equestion_id"])."
							AND `minimum_passing_level` = 1";
				$minimum_passing_level = $db->GetOne($query);
				if ($minimum_passing_level) {
					$query = "SELECT c.`response_order` FROM `evaluation_progress` AS a
								JOIN `evaluation_responses` AS b
								ON a.`eprogress_id` = b.`eprogress_id`
								JOIN `evaluations_lu_question_responses` AS c
								ON b.`eqresponse_id` = c.`eqresponse_id`
								WHERE b.`equestion_id` = ".$db->qstr($evaluation_question["equestion_id"]);
					$response_level = $db->GetOne($query);
					if ($response_level && $response_level < $minimum_passing_level) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public static function getEvaluatorEvaluations($proxy_id, $organisation_id) {
		global $db;

		$evaluations = array();

        $cohort_ids = groups_get_enrolled_group_ids($proxy_id, false, $organisation_id, false);
        $cohort_ids_string = "";
        if (isset($cohort_ids) && is_array($cohort_ids)) {
            foreach ($cohort_ids as $cohort_id) {
                $cohort_ids_string .= ($cohort_ids_string ? ", " : "").$db->qstr($cohort_id);
            }
        }

		$query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
					JOIN `course_groups` AS b
					ON a.`cgroup_id` = b.`cgroup_id`
					WHERE a.`proxy_id` = ".$db->qstr($proxy_id)."
					AND a.`active` = 1
					AND b.`active` = 1";
		$course_groups = $db->GetAll($query);

		$cgroup_ids_string = "";
		if (isset($course_groups) && is_array($course_groups)) {
			foreach ($course_groups as $course_group) {
				if ($cgroup_ids_string) {
					$cgroup_ids_string .= ", ".$db->qstr($course_group["cgroup_id"]);
				} else {
					$cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
				}
			}
		}

		$query = "	SELECT * FROM `evaluations` AS a
					JOIN `evaluation_evaluators` AS b
					ON a.`evaluation_id` = b.`evaluation_id`
					JOIN `evaluation_forms` AS c
					ON a.`eform_id` = c.`eform_id`
					JOIN `evaluations_lu_targets` AS d
					ON c.`target_id` = d.`target_id`
					WHERE
					(
						(
							b.`evaluator_type` = 'proxy_id'
							AND b.`evaluator_value` = ".$db->qstr($proxy_id)."
						)
						OR
						(
							b.`evaluator_type` = 'organisation_id'
							AND b.`evaluator_value` = ".$db->qstr($organisation_id)."
						)".(isset($cohort_ids_string) && $cohort_ids_string ? " OR (
							b.`evaluator_type` = 'cohort'
							AND b.`evaluator_value` IN (".$cohort_ids_string.")
						)" : "").($cgroup_ids_string ? " OR (
							b.`evaluator_type` = 'cgroup_id'
							AND b.`evaluator_value` IN (".$cgroup_ids_string.")
						)" : "")."
					)
					AND a.`evaluation_start` < ".$db->qstr(time())."
					AND a.`evaluation_active` = 1
					GROUP BY a.`evaluation_id`
					ORDER BY a.`evaluation_finish` DESC";
		$temp_evaluations = $db->GetAll($query);
		if ($temp_evaluations) {
			foreach ($temp_evaluations as $evaluation) {
                $temp_evaluation = Classes_Evaluation::getEvaluationDetails($evaluation, $proxy_id);
                if ($temp_evaluation) {
                    $evaluations[] = $temp_evaluation;
                }
			}
		}
		return $evaluations;
	}

    public static function getEvaluationDetails ($evaluation, $proxy_id) {
        global $db;

        if ($evaluation["max_submittable"]) {
            $evaluation["base_max_submittable"] = $evaluation["max_submittable"];
        }
        if ($evaluation["min_submittable"]) {
            $evaluation["base_min_submittable"] = $evaluation["min_submittable"];
        }
        if (isset($evaluation["require_requests"]) && $evaluation["require_requests"]) {
            $requests = Classes_Evaluation::getEvaluationRequests($evaluation["evaluation_id"], $proxy_id);
        }
        if (!(isset($evaluation["require_requests"]) && $evaluation["require_requests"]) || (is_array($requests) && count($requests))) {
            $evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluation["eevaluator_id"], $proxy_id);
            if ($evaluation_targets_list) {
                $event_ids_string = "";
                $evaluation["evaluation_targets"] = $evaluation_targets_list;
                if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false) {
                    foreach ($evaluation_targets_list as $event) {
                        $event_ids_string .= ($event_ids_string ? ", " : "").$db->qstr($event);
                    }
                }
                if ($event_ids_string) {
                    $query = "	SELECT COUNT(a.`eprogress_id`) FROM `evaluation_progress` AS a
                                JOIN `evaluation_progress_clerkship_events` AS b
                                ON a.`eprogress_id` = b.`eprogress_id`
                                WHERE a.`evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                AND a.`proxy_id` = ".$db->qstr($proxy_id)."
                                AND a.`progress_value` = 'complete'
                                AND b.`event_id` NOT IN (".$event_ids_string.")";
                    $completed_past_evaluations = $db->GetOne($query);
                }
                if (!isset($completed_past_evaluations) || !$completed_past_evaluations) {
                    $completed_past_evaluations = 0;
                }
                $evaluation_targets_count = count($evaluation_targets_list);
                if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation["max_submittable"]) {
                    $evaluation["max_submittable"] = ($evaluation_targets_count * (int) $evaluation["max_submittable"]) + $completed_past_evaluations;
                } elseif ($evaluation["target_shortname"] == "peer" && $evaluation["max_submittable"] == 0) {
                    $evaluation["max_submittable"] = $evaluation_targets_count + $completed_past_evaluations;
                }
                if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation["min_submittable"]) {
                    $evaluation["min_submittable"] = ($evaluation_targets_count * (int) $evaluation["min_submittable"]) + $completed_past_evaluations;
                } elseif ($evaluation["target_shortname"] == "peer" && $evaluation["min_submittable"] == 0) {
                    $evaluation["min_submittable"] = $evaluation_targets_count + $completed_past_evaluations;
                }
                $evaluation_target_title = fetch_evaluation_target_title($evaluation_targets_list[0], $evaluation_targets_count, $evaluation["target_shortname"]);
                if ($evaluation["target_shortname"] == "peer" && $evaluation["max_submittable"] == 0) {
                    $evaluation["max_submittable"] = $evaluation_targets_count;
                } elseif ($evaluation["max_submittable"] == 0 && $evaluation["allow_repeat_targets"]) {
                    $evaluation["max_submittable"] = 2147483647;
                }

                if ($evaluation_target_title) {
                    $evaluation["evaluation_target_title"] = $evaluation_target_title;
                }
            }

            $query = "	SELECT COUNT(`efquestion_id`) FROM `evaluation_form_questions`
                        WHERE `eform_id` = ".$db->qstr($evaluation["eform_id"])."
                        GROUP BY `eform_id`";
            $evaluation_questions = $db->GetOne($query);
            if ($evaluation_questions) {
                $evaluation["evaluation_questions"] = $evaluation_questions;
            } else {
                $evaluation["evaluation_questions"] = 0;
            }

            $query = "	SELECT * FROM `evaluation_progress`
                        WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                        AND `proxy_id` = ".$db->qstr($proxy_id)."
                        AND `progress_value` = 'complete'";
            $evaluation_progress = $db->GetAll($query);
            if ($evaluation_progress) {
                $evaluation["evaluation_progress"] = $evaluation_progress;
            } else {
                $evaluation["evaluation_progress"] = 0;
            }

            $query = "	SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
                        WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                        AND `proxy_id` = ".$db->qstr($proxy_id)."
                        AND `progress_value` = 'complete'";
            $completed_attempts = $db->GetOne($query);
            if ($completed_attempts) {
                $evaluation["completed_attempts"] = $completed_attempts;
            } else {
                $evaluation["completed_attempts"] = 0;
            }

			if (defined("EVALUATION_LOCKOUT") && EVALUATION_LOCKOUT && ($evaluation["evaluation_finish"] + EVALUATION_LOCKOUT) < time()) {
				$evaluation["max_submittable"] = $evaluation["completed_attempts"];
			}

            if ($evaluation["max_submittable"] > $evaluation["completed_attempts"]) {
                $evaluation["click_url"] = ENTRADA_URL."/evaluations?section=attempt&id=".$evaluation["evaluation_id"];
            } else {
                $evaluation["click_url"] = "";
            }

            if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) === false || (isset($evaluation_targets_count) && $evaluation_targets_count)) {
                return $evaluation;
            }
        }

        return false;
    }

	public static function getOutstandingEvaluations($proxy_id, $organisation_id, $return_count_only = false) {
		global $db;

		$evaluations = array();

        $cohort_ids = groups_get_enrolled_group_ids($proxy_id, false, $organisation_id, false);
        $cohort_ids_string = "";
        if (isset($cohort_ids) && is_array($cohort_ids)) {
            foreach ($cohort_ids as $cohort_id) {
                $cohort_ids_string .= ($cohort_ids_string ? ", " : "").$db->qstr($cohort_id);
            }
        }

		$query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
					JOIN `course_groups` AS b
					ON a.`cgroup_id` = b.`cgroup_id`
					WHERE a.`proxy_id` = ".$db->qstr($proxy_id)."
					AND a.`active` = 1
					AND b.`active` = 1";
		$course_groups = $db->GetAll($query);

		$cgroup_ids_string = "";
		if (isset($course_groups) && is_array($course_groups)) {
			foreach ($course_groups as $course_group) {
				if ($cgroup_ids_string) {
					$cgroup_ids_string .= ", ".$db->qstr($course_group["cgroup_id"]);
				} else {
					$cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
				}
			}
		}

		$query = "	SELECT * FROM `evaluations` AS a
					JOIN `evaluation_evaluators` AS b
					ON a.`evaluation_id` = b.`evaluation_id`
					JOIN `evaluation_forms` AS c
					ON a.`eform_id` = c.`eform_id`
					JOIN `evaluations_lu_targets` AS d
					ON c.`target_id` = d.`target_id`
					WHERE
                    (
						(
							b.`evaluator_type` = 'proxy_id'
							AND b.`evaluator_value` = ".$db->qstr($proxy_id)."
						)
						OR
						(
							b.`evaluator_type` = 'organisation_id'
							AND b.`evaluator_value` = ".$db->qstr($_SESSION["details"]["organisation_id"])."
						)".(isset($cohort_ids_string) && $cohort_ids_string ? " OR (
							b.`evaluator_type` = 'cohort'
							AND b.`evaluator_value` IN (".$cohort_ids_string.")
						)" : "").($cgroup_ids_string ? " OR (
							b.`evaluator_type` = 'cgroup_id'
							AND b.`evaluator_value` IN (".$cgroup_ids_string.")
						)" : "")."
					)
					AND a.`evaluation_start` < ".$db->qstr(time())."
					AND a.`evaluation_active` = 1
					GROUP BY a.`evaluation_id`
					ORDER BY a.`evaluation_finish` DESC";
		$temp_evaluations = $db->GetAll($query);
		if ($temp_evaluations) {
			foreach ($temp_evaluations as $evaluation) {
                if (isset($evaluation["require_requests"]) && $evaluation["require_requests"]) {
                    $requests = Classes_Evaluation::getEvaluationRequests($evaluation["evaluation_id"], $proxy_id);
                }
                if (!(isset($evaluation["require_requests"]) && $evaluation["require_requests"]) || (is_array($requests) && count($requests))) {
                    $evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation["evaluation_id"], $evaluation["eevaluator_id"], $proxy_id);
                    if ($evaluation_targets_list) {
                        $evaluation_targets_count = count($evaluation_targets_list);
                        if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation["max_submittable"]) {
                            $evaluation["max_submittable"] = ($evaluation_targets_count * (int) $evaluation["max_submittable"]);
                        } elseif ($evaluation["target_shortname"] == "peer" && $evaluation["max_submittable"] == 0) {
                            $evaluation["max_submittable"] = $evaluation_targets_count;
                        } elseif ($evaluation["max_submittable"] == 0 && $evaluation["allow_repeat_targets"]) {
                            $evaluation["max_submittable"] = 2147483647;
                        }
                        if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation["min_submittable"]) {
                            $evaluation["min_submittable"] = ($evaluation_targets_count * (int) $evaluation["min_submittable"]);
                        } elseif ($evaluation["target_shortname"] == "peer" && $evaluation["min_submittable"] == 0) {
                            $evaluation["min_submittable"] = $evaluation_targets_count;
                        }
                        $evaluation_target_title = fetch_evaluation_target_title($evaluation_targets_list[0], $evaluation_targets_count, $evaluation["target_shortname"]);


                        if ($evaluation_target_title) {
                            $evaluation["evaluation_target_title"] = $evaluation_target_title;
                        }

                        if ($evaluation_targets_list) {
                            $evaluation["evaluation_targets"] = $evaluation_targets_list;
                        }
                    }

                    $query = "	SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
                                WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                AND `proxy_id` = ".$db->qstr($proxy_id)."
                                AND `progress_value` = 'complete'";
                    $completed_attempts = $db->GetOne($query);
                    if ($completed_attempts) {
                        $evaluation["completed_attempts"] = $completed_attempts;
                    } else {
                        $evaluation["completed_attempts"] = 0;
                    }
					
					
			
					if (defined("EVALUATION_LOCKOUT") && EVALUATION_LOCKOUT && ($evaluation["evaluation_finish"] + EVALUATION_LOCKOUT) < time()) {
						$evaluation["max_submittable"] = $evaluation["completed_attempts"];
					}

                    if ($completed_attempts >= $evaluation["max_submittable"]) {
                        continue;
                    } else {
                        $query = "	SELECT COUNT(`efquestion_id`) FROM `evaluation_form_questions`
                                    WHERE `eform_id` = ".$db->qstr($evaluation["eform_id"])."
                                    GROUP BY `eform_id`";
                        $evaluation_questions = $db->GetOne($query);
                        if ($evaluation_questions) {
                            $evaluation["evaluation_questions"] = $evaluation_questions;
                        } else {
                            $evaluation["evaluation_questions"] = 0;
                        }

                        $query = "	SELECT * FROM `evaluation_progress`
                                    WHERE `evaluation_id` = ".$db->qstr($evaluation["evaluation_id"])."
                                    AND `proxy_id` = ".$db->qstr($proxy_id)."
                                    AND `progress_value` = 'complete'";
                        $evaluation_progress = $db->GetAll($query);
                        if ($evaluation_progress) {
                            $evaluation["evaluation_progress"] = $evaluation_progress;
                        } else {
                            $evaluation["evaluation_progress"] = 0;
                        }

                        if ($evaluation["max_submittable"] > $evaluation["completed_attempts"]) {
                            $evaluation["click_url"] = ENTRADA_URL."/evaluations?section=attempt&id=".$evaluation["evaluation_id"];
                        } else {
                            $evaluation["click_url"] = "";
                        }

                        if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) === false || (isset($evaluation_targets_count) && $evaluation_targets_count)) {
                            $evaluations[] = $evaluation;
                        }
                    }
                }
			}
		}
		return (($return_count_only) ? count($evaluations) : $evaluations);
	}

    public static function getEvaluationRequests($evaluation_id, $proxy_id) {
        global $db;

        $query = "SELECT * FROM `evaluation_requests`
                    WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
                    AND `target_proxy_id` = ".$db->qstr($proxy_id)."
                    AND (
                        `request_expires` = 0
                        OR `request_expires` > ".$db->qstr(time())."
                    )
                    AND `request_fulfilled` = 0
                    AND `request_code` IS NULL";
        $requests = $db->GetAll($query);
        if ($requests) {
            return $requests;
        } else {
            return false;
        }
    }

	public static function getAuthorEvaluations() {
		global $db, $ENTRADA_ACL, $ENTRADA_USER;

		$evaluations = array();

		$query = "	SELECT a.`evaluation_id`, a.`evaluation_title`, a.`evaluation_active`, a.`evaluation_start`, a.`evaluation_finish`, a.`organisation_id`, c.`target_shortname` AS `evaluation_type`
							FROM `evaluations` AS a
							JOIN `evaluation_targets` AS b
							ON a.`evaluation_id` = b.`evaluation_id`
							JOIN `evaluations_lu_targets` AS c
							ON b.`target_id` = c.`target_id`
                            WHERE a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							GROUP BY a.`evaluation_id`";
		$temp_evaluations = $db->GetAll($query);
		foreach ($temp_evaluations as $evaluation) {
			if ($ENTRADA_ACL->amIAllowed(new EvaluationResource($evaluation["evaluation_id"], $evaluation["organisation_id"], true), 'update')) {
				$evaluations[] = $evaluation;
			}
		}

		return $evaluations;

	}

	public static function getAuthorEvaluationForms() {
		global $db, $ENTRADA_USER, $ENTRADA_ACL;

		$evaluation_forms = array();

		$query = "SELECT a.*, b.`target_shortname`, b.`target_title`
					FROM `evaluation_forms` AS a
					JOIN `evaluations_lu_targets` AS b
					ON a.`target_id` = b.`target_id`
					WHERE a.`form_active` = 1
                    AND a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					GROUP BY a.`eform_id`";
		$temp_evaluation_forms = $db->GetAll($query);
		foreach ($temp_evaluation_forms as $evaluation_form) {
			if ($ENTRADA_ACL->amIAllowed(new EvaluationFormResource($evaluation_form["eform_id"], $evaluation_form["organisation_id"], true), 'update')) {
				$evaluation_forms[] = $evaluation_form;
			}
		}

		return $evaluation_forms;

	}

	public static function getAuthorEvaluationQuestions($require_code = false) {
		global $db, $ENTRADA_USER, $ENTRADA_ACL;

		$evaluation_questions = array();

		$query = "SELECT a.*, b.`questiontype_shortname`, b.`questiontype_title`
					FROM `evaluations_lu_questions` AS a
					JOIN `evaluations_lu_questiontypes` AS b
					ON a.`questiontype_id` = b.`questiontype_id`
					LEFT JOIN `evaluation_rubric_questions` AS c
					ON a.`equestion_id` = c.`equestion_id`
					WHERE a.`question_active` = 1
					".($require_code ? "AND a.`question_code` IS NOT NULL" : "")."
                    AND a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					GROUP BY a.`equestion_id`
					ORDER BY c.`erubric_id`, c.`question_order`, b.`questiontype_id`";
		$temp_evaluation_questions = $db->GetAll($query);
		foreach ($temp_evaluation_questions as $evaluation_question) {
			if ($ENTRADA_ACL->amIAllowed(new EvaluationQuestionResource($evaluation_question["equestion_id"], $evaluation_question["organisation_id"], true), 'update')) {
				$evaluation_questions[] = $evaluation_question;
			}
		}

		return $evaluation_questions;

	}

	public static function getEvaluators($evaluation_id, $available_only = false) {
		global $db;

		$evaluators_output = array();

		$query = "SELECT * FROM `evaluation_evaluators`
					WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
		$evaluators = $db->GetAll($query);

		if ($evaluators) {
			foreach ($evaluators as $evaluator) {
				$evaluator_users = Classes_Evaluation::getEvaluatorUsers($evaluator, $available_only);
				$evaluators_output = array_merge($evaluators_output, $evaluator_users);
			}
		}

		return $evaluators_output;
	}


	public static function getClinicalPresentations($parent_id = 0, $presentations = array(), $equestion_id = 0, $presentation_ids = false, $org_id = 0) {
		global $db, $ENTRADA_USER, $translate;

		$org_id = ($org_id == 0 ? $ENTRADA_USER->getActiveOrganisation() : (int) $org_id );

		if ($equestion_id) {
			$presentation_ids = array();

			$query = "	SELECT `objective_id`
						FROM `evaluation_form_question_objectives`
						WHERE `equestion_id` = ".$db->qstr($equestion_id);
			$allowed_objectives = $db->GetAll($query);
			if ($allowed_objectives) {
				foreach ($allowed_objectives as $presentation) {
					$presentation_ids[] = $presentation["objective_id"];
				}
			}
		}

		if ($parent_id) {
			$query = "	SELECT a.*
						FROM `global_lu_objectives` AS a
						JOIN `objective_organisation` AS b
						ON a.`objective_id` = b.`objective_id`
						WHERE `objective_active` = '1'
						AND `objective_parent` = ".$db->qstr($parent_id)."
						AND b.`organisation_id` = ".$db->qstr($org_id);
		} else {
			$objective_name = $translate->_("events_filter_controls");
			$objective_name = $objective_name["cp"]["global_lu_objectives_name"];

			$query = "	SELECT a.*
						FROM `global_lu_objectives` AS a
						JOIN `objective_organisation` AS b
						ON a.`objective_id` = b.`objective_id`
						WHERE a.`objective_active` = '1'
						AND b.`organisation_id` = ".$db->qstr($org_id)."
						AND a.`objective_name` = ".$db->qstr($objective_name);
		}

		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				if ($parent_id) {
					$presentations[] = $result;
				}
				$presentations = Classes_Evaluation::getClinicalPresentations($result["objective_id"], $presentations, 0, (isset($presentation_ids) && $presentation_ids ? $presentation_ids : array()), $org_id);
			}
		}

		if (!$parent_id && is_array($presentation_ids)) {
			foreach ($presentations as $key => $presentation) {
				if (array_search($presentation["objective_id"], $presentation_ids) === false) {
					unset($presentations[$key]);
				}
			}
		}

		return $presentations;
	}

	public static function getMobileQuestionAnswerControls($questions, $form_id, $eprogress_id = 0) {
		global $db;
		$output = "";
		$output .= "<div data-role=\"content\" id=\"form-content-questions-holder\">\n";
		$output .= "	<ol id=\"form-questions-list\">\n";
        if ($eprogress_id) {
            $current_progress_record = Classes_Evaluation::loadProgress($eprogress_id);
        } else {
            $current_progress_record = false;
        }
        $rubric_id = 0;
        $show_rubric_headers = false;
        $show_rubric_footers = false;
        $rubric_table_open = false;
        $original_question_id = 0;
        $comments_enabled = false;
        $modified_count = 0;
        $desctext_count = 0;
        foreach ($questions as $key => $question) {
            if (isset($question["questiontype_id"]) && $question["questiontype_id"]) {
                $query = "SELECT * FROM `evaluations_lu_questiontypes`
                            WHERE `questiontype_id` = ".$db->qstr($question["questiontype_id"]);
                $questiontype = $db->GetRow($query);
            } else {
                $questiontype = array("questiontype_shortname" => "matrix_single");
            }
            switch ($questiontype["questiontype_shortname"]) {
                case "rubric" :
                    $query = "SELECT * FROM `evaluation_rubric_questions` AS a
                                JOIN `evaluations_lu_rubrics` AS b
                                ON a.`erubric_id` = b.`erubric_id`
                                WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"]);
                    $rubric = $db->GetRow($query);
                    if ($rubric) {
                        if ($rubric["erubric_id"] != $rubric_id) {
                            if ($rubric_id) {
                                $show_rubric_footers = true;
                            }
                            $rubric_id = $rubric["erubric_id"];
                            $show_rubric_headers = true;
                            $original_question_id = $question["equestion_id"];
                            $comments_enabled = $question["allow_comments"];
                        }
                        if ($show_rubric_footers) {
                            $show_rubric_footers = false;
                            $rubric_table_open = false;
                            $output .= "</table></div>";
                            if ($comments_enabled) {
                                $output .= "	<div class=\"clear\"></div>\n";
                                $output .= "	<div class=\"comments\">\n";
                                $output .= "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
                                $output .= "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
                                $output .= "	</div>\n";
                            } else {
                                $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
                            }
                            $original_question_id = $question["equestion_id"];
                            $comments_enabled = $question["allow_comments"];
                            $output .= "</li>";
                        }
                        if ($show_rubric_headers) {
                            $rubric_table_open = true;
                            $output .= "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">\n";
                            $output .= "<span id=\"question_text_".$question["equestion_id"]."\" style=\"display: none;\">".$rubric["rubric_title"].(stripos($rubric["rubric_title"], "rubric") === false ? " Grouped Item" : "")."</span>";
                            $output .= (isset($rubric["rubric_title"]) && $rubric["rubric_title"] ? "<h2>".$rubric["rubric_title"] : "")."<span style=\"font-weight: normal; margin-left: 10px; padding-right: 30px;\" class=\"content-small\">".$rubric["rubric_description"]."</span>".(isset($rubric["rubric_title"]) && $rubric["rubric_title"] ? "</h2>\n" : "\n");
                            $modified_count++;
                            $output .= "<br /><div class=\"question\"><table class=\"rubric\">\n";
                            $output .= "	<tr>\n";
                            $columns = 0;
                            $query = "	SELECT a.*
                                        FROM `evaluations_lu_question_responses` AS a
                                        WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
                                        ORDER BY a.`response_order` ASC";
                            $responses = $db->GetAll($query);
                            if ($responses) {
                                $response_width = floor(100 / (count($responses) + 1));
                                $output .= "		<th style=\"width: ".$response_width."%; text-align: left; border-bottom: \">\n";
                                $output .= "			Categories";
                                $output .= "		</th>\n";
                                foreach ($responses as $response) {
                                    $columns++;
                                    $output .= "<th style=\"width: ".$response_width."%; text-align: left;\">\n";
                                    $output .= clean_input($response["response_text"], "specialchars");
                                    $output .= "</th>\n";
                                }
                            }
                            $output .= "	</tr>\n";
                            $show_rubric_headers = false;
                        }

                        $question_number = ($key + 1);

                        $output .= "<tr id=\"question_".$question["equestion_id"]."\">";

                        $query = "	SELECT b.*, a.`equestion_id`, a.`minimum_passing_level`
                                    FROM `evaluations_lu_question_responses` AS a
                                    LEFT JOIN `evaluations_lu_question_response_criteria` AS b
                                    ON a.`eqresponse_id` = b.`eqresponse_id`
                                    WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
                                    ORDER BY a.`response_order` ASC";
                        $criteriae = $db->GetAll($query);
                        if ($criteriae) {
                            $criteria_width = floor(100 / (count($criteriae) + 1));
                            $output .= "		<td style=\"width: ".$criteria_width."%\">\n";
                            $output .= "			<div class=\"td-stretch\" style=\"position: relative; width: 100%; vertical-align: middle;\">\n";
                            echo "				        <div style=\"position: relative; top: 50%;\">\n";
                            echo "                          <strong>".$question["question_text"]."</strong>\n";
                            echo "                          <div class=\"space-above content-small\">".nl2br($question["question_description"])."</div>";
                            echo "                      </div>\n";
                            $output .= "			</div>\n";
                            $output .= "		</td>\n";
                            $blank_lines = "\n";
                            foreach ($criteriae as $criteria) {
                                $new_blank_lines = preg_replace('/\S/', " ", $criteria["criteria_text"]);
                                if (strlen($blank_lines) < strlen($new_blank_lines)) {
                                    $blank_lines = $new_blank_lines;
                                }
                            }
                            $output .= "	<fieldset data-role=\"controlgroup\">";
                            foreach ($criteriae as $criteria) {
                                $criteria_text = clean_input(nl2br($criteria["criteria_text"]), "allowedtags");
                                if (!trim($criteria_text)) {
                                    $criteria_text = nl2br($blank_lines);
                                }
                                $output .= "<td style=\"width: ".$criteria_width."%; vertical-align: top;\" >\n";
                                $output .= "	<div style=\"width: 3em; margin: 0 auto;\">";
                                $output .= "		<input data-theme=\"b\" type=\"radio\" id=\"".$form_id."_".$criteria["equestion_id"]."_".$criteria["eqresponse_id"]."\" name=\"responses[".$question["equestion_id"]."]\"".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $criteria["eqresponse_id"] ? " checked=\"checked\"" : "")." value=\"".$criteria["eqresponse_id"]."\" />";
                                $output .= "		<label for=\"".$form_id."_".$criteria["equestion_id"]."_".$criteria["eqresponse_id"]."\" >&nbsp;</label>";
                                $output .= "	</div>\n";
                                $output .= "	".$criteria_text;
                                $output .= "</td>\n";
                            }
                            $output .= "	</fieldset>\n";
                        }
                        $output .= "</tr>";
                    }
                break;
                case "descriptive_text" :
                case "free_text" :
                    if ($rubric_table_open) {
                        $rubric_table_open = false;
                        $rubric_id = 0;
                        $output .= "</table></div>";
                        if ($comments_enabled) {
                            $output .= "	<div class=\"clear\"></div>\n";
                            $output .= "	<div class=\"comments\">\n";
                            $output .= "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
                            $output .= "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
                            $output .= "	</div>\n";
                        } else {
                            $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
                        }
                        $original_question_id = 0;
                        $comments_enabled = false;
                        $output .= "</li>";
                    }
                    $question_number = ($key + 1);

                    $output .= "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                    $output .= "	<div id=\"question_text_".$question["equestion_id"]."\" for=\"".$question["equestion_id"]."_comment\" class=\"question\">\n";
                    $output .= "		".clean_input($question["question_text"], "specialchars");
                    $output .= "	</div>\n";
                    $output .= "	<div class=\"clear\"></div>";
                    if ($questiontype["questiontype_shortname"] == "free_text") {
                        $output .= "	<div class=\"comments\">";
                        $output .= "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
                        $output .= "	</div>";
                    }
                    $output .= "</li>\n";
                    $modified_count++;
                break;
                case "selectbox" :
                    if ($rubric_table_open) {
                        $rubric_table_open = false;
                        $rubric_id = 0;
                        $output .= "</table></div>";
                        if ($comments_enabled) {
                            $output .= "	<div class=\"clear\"></div>\n";
                            $output .= "	<div class=\"comments\">\n";
                            $output .= "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
                            $output .= "	<textarea id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
                            $output .= "	</div>\n";
                        } else {
                            $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
                        }
                        $original_question_id = 0;
                        $comments_enabled = false;
                        $output .= "</li>";
                    }
                    $question_number = ($key + 1);

                    $output .= "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                    $output .= "	<div id=\"question_text_".$question["equestion_id"]."\" class=\"question\">\n";
                    $output .= "		".clean_input($question["question_text"], "specialchars");
                    $output .= "	</div>\n";
                    $output .= "	<div class=\"responses\">\n";
                    $query = "	SELECT a.*
                                FROM `evaluations_lu_question_responses` AS a
                                WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
                                ORDER BY a.`response_order` ASC";
                    $responses = $db->GetAll($query);
                    if ($responses) {
                        $response_width = floor(100 / count($responses)) - 1;
                        //echo "<div class=\"clearfix\">\n";
                        $output .= "<fieldset data-role=\"controlgroup\">\n";
                        $output .= "<select id=\"responses_".$question["equestion_id"]."\" name=\"responses[".$question["equestion_id"]."]\">\n";
                        $output .= "  <option value=\"0\">-- Select a response --</option>\n";
                        foreach ($responses as $response) {
                            $output .= "  <option value=\"".$response["eqresponse_id"]."\"".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $response["eqresponse_id"] ? " selected=\"selected\"" : "").">".clean_input($response["response_text"], "specialchars")."</option>\n";
                        }
                        $output .= "</select>\n";
                        $output .= "</fieldset>\n";
                        //echo "</div>\n";
                    }
                    $output .= "	</div>\n";
                    if ($question["allow_comments"]) {
                        $output .= "	<div class=\"clear\"></div>";
                        $output .= "	<div class=\"comments\">";
                        $output .= "	<label for=\"".$question["equestion_id"]."_comment\" class=\"form-nrequired\">Comments:</label>";
                        $output .= "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
                        $output .= "	</div>";
                    } else {
                        $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
                    }
                    $output .= "</li>\n";
                    $modified_count++;
                break;
                case "vertical_matrix" :
                case "matrix_single" :
                default :
                    if ($rubric_table_open) {
                        $rubric_table_open = false;
                        $rubric_id = 0;
                        $output .= "</table></div>";
                        if ($comments_enabled) {
                            $output .= "	<div class=\"clear\"></div>\n";
                            $output .= "	<div class=\"comments\">\n";
                            $output .= "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
                            $output .= "	<textarea id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
                            $output .= "	</div>\n";
                        } else {
                            $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
                        }
                        $original_question_id = 0;
                        $comments_enabled = false;
                        $output .= "</li>";
                    }
                    $question_number = ($key + 1);

                    $output .= "<li id=\"question_".$question["equestion_id"]."\"".(($modified_count % 2) ? " class=\"odd\"" : "").">";
                    $output .= "	<div id=\"question_text_".$question["equestion_id"]."\" class=\"question\">\n";
                    $output .= "		".clean_input($question["question_text"], "specialchars");
                    $output .= "	</div>\n";
                    $output .= "	<div class=\"responses\">\n";
                    $query = "	SELECT a.*
                                FROM `evaluations_lu_question_responses` AS a
                                WHERE a.`equestion_id` = ".$db->qstr($question["equestion_id"])."
                                ORDER BY a.`response_order` ASC";
                    $responses = $db->GetAll($query);
                    if ($responses) {
                        $output .= "<fieldset data-role=\"controlgroup\">\n";
                        foreach ($responses as $response) {
                            $output .= "	<input type=\"radio\" id=\"response_".$question["equestion_id"]."_".$response["eqresponse_id"]."\" name=\"responses[".$response["equestion_id"]."]\"".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["eqresponse_id"]) && $current_progress_record[$question["equestion_id"]]["eqresponse_id"] == $response["eqresponse_id"] ? " checked=\"checked\"" : "")." value=\"".$response["eqresponse_id"]."\" />";
                            $output .= "	<label for=\"response_".$question["equestion_id"]."_".$response["eqresponse_id"]."\">".clean_input($response["response_text"], "specialchars")."</label>";
                        }
                        $output .= "</fieldset>\n";
                    }
                    $output .= "	</div>\n";
                    if ($question["allow_comments"]) {
                        $output .= "	<div class=\"clear\"></div>";
                        $output .= "	<div class=\"comments\">";
                        $output .= "	<label for=\"".$question["equestion_id"]."_comment\" class=\"form-nrequired\">Comments:</label>";
                        $output .= "	<textarea name=\"comments[".$question["equestion_id"]."]\" id=\"".$question["equestion_id"]."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$question["equestion_id"]]["comments"]) ? $current_progress_record[$question["equestion_id"]]["comments"] : "")."</textarea>";
                        $output .= "	</div>";
                    } else {
                        $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
                    }
                    $output .= "</li>\n";
                    $modified_count++;
                break;
            }
        }
        if ($rubric_table_open) {
            $output .= "</table></div>";
            if ($comments_enabled) {
                $output .= "	<div class=\"clear\"></div>\n";
                $output .= "	<div class=\"comments\">\n";
                $output .= "	<label for=\"".$original_question_id."_comment\" class=\"form-nrequired\">Comments:</label>\n";
                $output .= "	<textarea name=\"comments[".$original_question_id."]\" id=\"".$original_question_id."_comment\" class=\"expandable\" style=\"width:95%; height:40px;\">".($current_progress_record && isset($current_progress_record[$original_question_id]["comments"]) ? $current_progress_record[$original_question_id]["comments"] : "")."</textarea>\n";
                $output .= "	</div>\n";
            } else {
                $output .= "<input data-theme=\"b\" type=\"hidden\" value=\"\" id=\"".$original_question_id."_comment\" />\n";
            }
            $output .= "</li>";
        }

        $output .= "    </ol>\n";
        $output .= "</div>\n";
        return $output;
	}

    public static function getQuestionParents ($equestion_id) {
        global $db;
        $question_parents = array();
        $query = "SELECT * FROM `evaluations_lu_questions` WHERE `equestion_id` = ".$db->qstr($equestion_id);
        $evaluation_questions = $db->GetAll($query);
        foreach ($evaluation_questions as $evaluation_question) {
            $question_parents[$evaluation_question["equestion_id"]] = $evaluation_question;
            $question_grandparents = Classes_Evaluation::getQuestionParents($evaluation_question["question_parent_id"]);
            foreach ($question_grandparents as $question_grandparent) {
                $question_parents[$question_grandparent["equestion_id"]] = $question_grandparent;
            }
        }

        return $question_parents;
    }

    public static function getQuestionObjectives ($equestion_id) {
        global $db, $translate;

        $objective_name = $translate->_("events_filter_controls");
        $objective_name = $objective_name["co"]["global_lu_objectives_name"];
        $question_objectives = array();
        $query = "SELECT b.* FROM `evaluation_question_objectives` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`equestion_id` = ".$db->qstr($equestion_id);
        $evaluation_question_objectives = $db->GetAll($query);
        foreach ($evaluation_question_objectives as $objective) {
            $objective_parent_id = $objective["objective_parent"];
            $objective["parents"] = array();
            $objective["parent_ids"] = array();
            $objective["equestion_ids"][] = $equestion_id;
            $last_objective_parent = false;
            $objective_parent = false;
            while ($objective_parent_id) {
                $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_id` = ".$db->qstr($objective_parent_id);
                $objective_parent = $db->GetRow($query);
                if ($objective_parent["objective_name"] != $objective_name) {
                    $objective_parent_id = $objective_parent["objective_parent"];
                    $objective["parents"][] = $objective_parent;
                    $objective["parent_ids"][] = $objective_parent["objective_id"];
                    $last_objective_parent = $objective_parent;
                } else {
                    $objective_parent_id = 0;
                }
            }
            if ($objective_parent) {
                $objective["top_parent"] = $last_objective_parent;
            }
            $question_objectives[$objective["objective_id"]] = $objective;
        }
        return $question_objectives;
    }

	public static function evaluation_save_response($eprogress_id, $eform_id, $equestion_id, $eqresponse_id, $comments) {
		global $db, $ENTRADA_USER;

		/**
		 * Check to ensure the provided question id is associated with the provided form id
		 */
		$form_question = Models_Evaluation_Form_Question::fetchRowByFormIDQuestionID($eform_id, $equestion_id);
		if ($form_question) {
			$question = Models_Evaluation_Question::fetchRowByID($equestion_id);
			if ($question) {
				/**
				 * Check to ensure that this response is associated with this question.
				 */
				if ($eqresponse_id !== 0) {
					$response = Models_Evaluation_Question_Response::fetchRowByEQResponseIDEQuestionID($eqresponse_id, $equestion_id);
					if (!$response) {
						return false;
					}
				}
				/**
				 * See if they have already responded to this question or not as this
				 * determines whether an INSERT or an UPDATE is required.
				 */
				$evaluation_response = Models_Evaluation_Response::fetchRowByEProgressIDEFQuestionIDProxyID($eprogress_id, $form_question->getID(), $ENTRADA_USER->getActiveID());
				if ($evaluation_response) {
					$rubric_question = Models_Evaluation_Rubric_Question::fetchRowByEQuestionID($equestion_id);
					if ($rubric_question) {
						$all_rubric_questions = Models_Evaluation_Rubric_Question::fetchAllByERubricID($rubric_question->getErubricID());
						foreach ($all_rubric_questions as $tmp_rubric_question) {
							$tmp_form_question = Models_Evaluation_Form_Question::fetchRowByFormIDQuestionID($eform_id, $tmp_rubric_question->getEquestionID());;
							if ($tmp_form_question) {
								$tmp_response = Models_Evaluation_Response::fetchRowByEProgressIDEFQuestionIDProxyID($eprogress_id, $tmp_form_question->getEfquestionID(), $ENTRADA_USER->getActiveID());
								if ($tmp_response && $tmp_response->getComment()) {
									$comment_response_id = $tmp_response->getID();
								}
							}
						}
					}
					/**
					 * Checks to see if the response is different from what was previously
					 * stored in the event_evaluation_responses table.
					 */
					if ($eqresponse_id != $evaluation_response->getEqresponseID() || $comments != $evaluation_response->getComments()) {
						if ($eqresponse_id != $evaluation_response->getEqresponseID() && $eqresponse_id == 0) {
							$eqresponse_id = $evaluation_response->getEqresponseID();
						}
						$evaluation_response_array = array(
							"eresponse_id"	=> $evaluation_response->getID(),
							"eqresponse_id" => $eqresponse_id,
							"efquestion_id" => $form_question->getID(),
							"comments" => (!isset($comment_response_id) || !$comment_response_id ? $comments : NULL),
							"updated_date" => time(),
							"updated_by" => $ENTRADA_USER->getActiveID()
						);
						$new_evaluation_response = new Models_Evaluation_Response($evaluation_response_array);
						if ($new_evaluation_response->update()) {
							if (isset($comment_response_id) && $comment_response_id) {
								$comment_evaluation_response = Models_Evaluation_Response::fetchRowByID($comment_response_id);
								$evaluation_comment_response_array = $comment_evaluation_response->toArray();

								$evaluation_comment_response_array["comments"] = $comments;
								$evaluation_comment_response_array["updated_date"] = time();
								$evaluation_comment_response_array["updated_by"] = $ENTRADA_USER->getID();
								$comment_evaluation_response = $comment_evaluation_response->fromArray($evaluation_comment_response_array);

								if ($comment_evaluation_response->update()) {
									return true;
								} else {
									application_log("error", "Unable to update the comments for a question that has already been recorded. Database said: " . $db->ErrorMsg());
								}
							} else {
								return true;
							}
						} else {
							application_log("error", "Unable to update a response to a question that has already been recorded. Database said: " . $db->ErrorMsg());
						}
					} else {
						return true;
					}
				} else {
					$evaluation_response_array = array(
						"eprogress_id" => $eprogress_id,
						"eform_id" => $eform_id,
						"proxy_id" => $ENTRADA_USER->getID(),
						"efquestion_id" => $form_question->getID(),
						"eqresponse_id" => $eqresponse_id,
						"comments" => $comments,
						"updated_date" => time(),
						"updated_by" => $ENTRADA_USER->getID()
					);

					$evaluation_response = new Models_Evaluation_Response($evaluation_response_array);

					if ($evaluation_response->insert()) {
						return true;
					} else {
						application_log("error", "Unable to record a response to a question that was submitted. Database said: " . $db->ErrorMsg());
					}
				}
			}
		}

		return false;
	}
}