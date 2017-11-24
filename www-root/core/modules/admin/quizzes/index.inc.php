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
 * This file displays the list of all quizzes available to the particular
 * individual who is accessing this file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!(bool) $_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_RELATIVE);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("quiz", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else { 
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js"."\"></script>";

    if ($ENTRADA_USER->getActiveRole() != "admin" || $ENTRADA_USER->getActiveGroup() != "medtech") {
        $quizzes = Models_Quiz::fetchAllRecordsByProxyID($ENTRADA_USER->getID());
    } else {
        $quizzes = Models_Quiz::fetchAllRecords();
    }
    $quiz_data = array();
    $i = 0;

    if ($quizzes) {
		foreach ($quizzes as $quiz) {
			$quiz_data[$i]["quiz_id"]           = $quiz->getQuizID();
			$quiz_data[$i]["quiz_title"]        = $quiz->getQuizTitle();
	
			$quiz_questions = array_filter($quiz->getQuizQuestions(), function ($question) {
				return $question->getQuestiontypeID() != 3;
			});
			
			$quiz_data[$i]["question_total"]    = count($quiz_questions);
			$quiz_data[$i]["quiz_status"]       = $quiz->getQuizActive() ? "Active" : "Disabled";
			$quiz_data[$i]["quiz_author"]       = $quiz->getQuizAuthor() ? $quiz->getQuizAuthor()->getFullName(false) : "";
			$quiz_data[$i]["updated_date"]      = $quiz->getUpdatedDate();
			$i++;
		}
    }
    
	if (isset($_GET["ajax"]) && $_GET["ajax"] && isset($_GET["method"]) && $_GET["method"] == "list") {
		ob_clear_open_buffers();
		$output = array("aaData" => array());
		$count = 0;
		if ($quiz_data) {
			/*
			* Ordering
			*/
			if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(0, 1, 2, 3, 4))) {
				$aColumns = array("modified", "quiz_title", "author", "question_total", "updated_date");
				$sort_array = array();
				foreach ($quiz_data as $quiz) {
					$quiz_array = $quiz;
					$key = $aColumns[clean_input($_GET["iSortCol_0"], "int")];
					if (isset($quiz_array[$key])) {
						$sort_array[] = $quiz_array[$key];
					} else {
						$sort_array[] = NULL;
					}
				}
				array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), (clean_input($_GET["iSortCol_0"], "int") == 3 ? SORT_NUMERIC : SORT_STRING), $quiz_data);
			}
			if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
				$start = (int)$_GET["iDisplayStart"];
				$limit = (int)$_GET["iDisplayLength"];
			} else {
				$start = 0;
				$limit = count($quiz_data) - 1;
			}
			if ($_GET["sSearch"] != "") {
				$search_value = $_GET["sSearch"];
			}
			foreach ($quiz_data as $quiz) {
				if (!isset($search_value) || stripos($quiz["quiz_title"], $search_value) !== false || stripos($quiz["author"], $search_value) !== false || stripos($quiz["question_total"], $search_value) !== false || !isset($search_value) || stripos(date("Y-m-d g:ia", $quiz["updated_date"]), $search_value) !== false) {
					if ($count >= $start && $count < ($start + $limit)) {
						$row = array();
						$row["modified"] = "<input class=\"delete-control\" type=\"checkbox\" name=\"delete[]\" value=\"".$quiz["quiz_id"]."\" />";
						$row["quiz_title"] = "<a href=\"". ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".$quiz["quiz_id"]."\">".$quiz["quiz_title"]."</a>";
						$row["author"] = "<a href=\"". ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".$quiz["quiz_id"]."\">". $quiz["quiz_author"] ."</a>";
						$row["question_total"] = "<a href=\"". ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".$quiz["quiz_id"]."\">". $quiz["question_total"] ."</a>";
						$row["updated_date"] = "<a href=\"". ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".$quiz["quiz_id"]."\">".date("Y-m-d g:ia", $quiz["updated_date"])."</a>";
						$row["id"] = $quiz["quiz_id"];
						$output["aaData"][] = $row;
					}
					$count++;
				}
			}
		}
		$output["iTotalRecords"] = (is_array($quiz_data) ? @count($quiz_data) : 0);
		$output["iTotalDisplayRecords"] = $count;
		$output["sEcho"] = clean_input($_GET["sEcho"], "int");
		if ($output && count($output)) {
		   echo json_encode($output);
		}
		exit;
	}
	?>
	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery("#delete-quizzes").on("click", function (event) {
				var checked = document.querySelectorAll("input.delete-control:checked").length === 0 ? false : true;
				if (!checked) {
					event.preventDefault();
					var errors = new Array();
					errors[0] = "You must select at least 1 quiz to delete by checking the checkbox to the left the quiz.";
					display_error(errors, "#msg");
				}
			});
			
			jQuery('#quiz-list').dataTable(
				{
					'sPaginationType': 'full_numbers',
					'bInfo': false,
					'bAutoWidth': false,
					'sAjaxSource': '?ajax=true&method=list',
					'bServerSide': true,
					'bProcessing': true,
					'aoColumns': [
						{ 'mDataProp': 'modified', 'bSortable': false },
						{ 'mDataProp': 'quiz_title' },
						{ 'mDataProp': 'author' },
						{ 'mDataProp': 'question_total' },
						{ 'mDataProp': 'updated_date' }
					],
					'aoColumnDefs': [ {
						'aTargets': [0,1,2,3,4],
						'fnCreatedCell': function (nTd, sData, oData, iRow, iCol) {
							if (iCol == 0) {
								jQuery(nTd).addClass('modified')
							} else if ( iCol == 1 ) {
								jQuery(nTd).addClass('title')
							} else if ( iCol == 2 ) {
								jQuery(nTd).addClass('author')
							} else if ( iCol == 3 ) {
								jQuery(nTd).addClass('questions')
							} else if ( iCol == 4 ) {
								jQuery(nTd).addClass('updated')
							}
						}
					}],
					'fnRowCallback': function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
						jQuery(nRow).attr('data-id', aData.id);

						return nRow;
					},
					'oLanguage': {
						'sEmptyTable': 'There are currently no quizzes in the system.',
						'sZeroRecords': 'No quizzes found.'
					},
                    'aaSorting': [[ 1, 'asc' ]]
				}
			);
		}); 
	</script>
	<h1><?php echo $MODULES[strtolower($MODULE)]["title"]; ?></h1>
	<div id="msg"></div>
    <div class="row-fluid">
		<a href="<?php echo ENTRADA_RELATIVE; ?>/admin/<?php echo $MODULE; ?>?section=add" class="btn btn-primary space-below pull-right">Create New Quiz</a>
    </div>
	<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/<?php echo $MODULE; ?>?section=delete" method="post">
		<table class="table table-striped table-bordered" id="quiz-list" summary="List of Quizzes">
			<thead>
				<tr>
					<th width="5%">&nbsp;</th>
					<th width="30%">Quiz Title</th>
					<th width="25%">Author</th>
					<th width="15%">Questions</th>
					<th width="25%">Last Updated</th>
				</tr>
			</thead>
			<tbody>
				
			</tbody>
		</table>
		<?php
		if ($quizzes) { ?>
		<div class="row-fluid">
			<input id="delete-quizzes" type="submit" class="btn btn-danger" value="Delete Selected" />
		</div>
		<?php
		}
		?>
	</form>
    <?php
}