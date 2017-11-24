<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to show the user the their own voting history within a particular
 * community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_POLLS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

/**
 * Add PlotKit to the beginning of the $HEAD array.
 */
array_unshift($HEAD,
	"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/MochiKit/MochiKit.js\"></script>",
	"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/excanvas.js\"></script>",
	"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Base.js\"></script>",
	"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Layout.js\"></script>",
	"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Canvas.js\"></script>",
	"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/SweetCanvas.js\"></script>"
	);
	
	
$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/polls.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Your Voting History</h1>\n";

if ($RECORD_ID) {
	
	$terminology = $db->GetOne("SELECT `poll_terminology` FROM `community_polls` WHERE `cpolls_id` = ".$RECORD_ID);
	
	$query				= "	SELECT * FROM `community_polls_responses` 
							WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)."
							ORDER BY `response_index`";
	$response_record	= $db->GetAll($query);
	
	if ($response_record) {
		$query = "	SELECT * FROM `community_polls_questions`
					WHERE `cpolls_id` = ".$RECORD_ID."
					AND `question_active` = '1'";
		$questions = $db->GetAll($query);
		$vote_record = communities_polls_votes_cast_by_member($RECORD_ID, $ENTRADA_USER->getActiveId());
	
		if (isset($vote_record["votes"]) && (int)$vote_record["votes"] > 0) {
			$allow_main_load = true;
		}
		if ($allow_main_load) {
			$BREADCRUMB[] 	= array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$COMMUNITY_MODULE."?section=vote-poll&amp;id=".$RECORD_ID, "title" => "Your Voting History");
			$query			= "SELECT * FROM `community_polls` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cpolls_id` = ".$db->qstr($RECORD_ID);
			$poll_record	= $db->GetRow($query);
			
			// Page Display
			switch($STEP) {
				case 2 :
					if ($NOTICE) {
						echo display_notice();
					}
					if ($SUCCESS) {
						echo display_success();
					}
				break;
				case 1 :
				default :
					$count = 1;
					foreach ($questions as $question) {
						$ONLOAD[] = "initDynamicTable('".$count."')";
						$count++;
					}
					$ONLOAD[] = "updatePollTypeIcon('1')";
					$ONLOAD[] = "updateColorIcon('1')";
					
					if ($ERROR) {
						echo display_error();
					}
					if ($NOTICE) {
						echo display_notice();
					}
					
					$count = 1;
					if (count($questions) > 1) {
						echo "<div class=\"pagination\" style=\"position: relative; text-align: right;\">\n";
						echo "	<ul>";
						echo "		<li class=\"active\"><a>1</a></li>";
						echo "		<li><a href=\"javascript:displayChart('1','2');\">2</a></li>";
							if (count($questions) > 2) {
								echo "		<li><a href=\"javascript:displayChart('1','3');\">3</a></li>";
								if (count($questions) > 3) {
									echo "		<li><a href=\"javascript:displayChart('1','4');\">4</a></li>";
									if (count($questions) > 4) {
										echo "		<li><a href=\"javascript:displayChart('1','5');\">5</a></li>";
										if (count($questions) > 5) {
											echo "		<li><a href=\"javascript:displayChart('1','".count($questions)."');\">...".count($questions)."</a></li>";
										}
									}
								}
							}
							echo "		<li><a style=\"height: 20px;\" href=\"javascript:displayChart('1','2');\"><i class=\"icon-chevron-right\" style=\"margin-top: 3px;\"></i></a></li>";
							echo "	</ul>";
							echo "</div>\n";
							echo "<span id=\"no-questions\" style=\"display: none;\">".count($questions)."</span>";
					}
							
					foreach ($questions as $question) {
						?>
						
						<form name="options" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>" method="post" <?php echo ($count != 1 ? "style=\"display: none;\"" : "")." id=\"question-".$count."\""; ?>>
							<table summary="<?php echo $terminology; ?> Results">
								<tbody>
									<td><h2><?php echo html_encode($poll_record["poll_title"]); ?></h2></td>
									<tr>
										<td>
											<table class="table table-striped table-bordered">
												<thead>
													<tr>
														<td>Question</td>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><?php echo html_encode($question["poll_question"]); ?></td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>


									<tr>
										<td>
											<table class="table table-striped table-bordered" summary="Vote Report">
												<colgroup>
													<col class="general" style="width: 75%;" />
													<col class="report-hours" />
												</colgroup>
												<thead>
													<tr>
														<td class="general" style="width: 75%;">Responses</td>
														<td class="report-hours" style="border-left: none;">Votes</td>
													</tr>
												</thead>
												<tbody>
													<?php
													$voteResponsesQuery		= "	SELECT * FROM `community_polls_responses`
																				WHERE `cpquestion_id` = ".$db->qstr($question["cpquestion_id"])."
																				ORDER BY `response_index` ASC";
													
													$voteResponsesResults	= $db->GetAll($voteResponsesQuery);
													
													$phpOutPutArray			= array();
													$javaResultString 		= "";
													$xTicks					= "";
													
													foreach($voteResponsesResults as $values)
													{
														if ($values["cpquestion_id"] == $question["cpquestion_id"]) {
															$getVotesQuery 	= "SELECT count(cpresponses_id) AS `total_count` 
															FROM `community_polls_results`
															WHERE `cpresponses_id` = ".$db->qstr($values["cpresponses_id"])."
															AND `proxy_id` = ".$db->qstr($PROXY_ID);
															
															if (!$voteResults = $db->GetRow($getVotesQuery))
															{
																$voteResults["total_count"] = 0;
															}
															
															if ($values["response_index"] > 1)
															{
																// The following comment is an example of what to pass to PlotKit.
																//[{label: '01', v: 0}, {label: '02', v: 1}, {label: '03', v: 2}, {label: '04', v: 3}, {label: '05', v: 4}, {label: '06', v: 5}, {label: '07', v: 6}, {label: '08', v: 7}, {label: '09', v: 8}, {label: '10', v: 9}, {label: '11', v: 10}, {label: '12', v: 11}, {label: '13', v: 12}, {label: '14', v: 13}, {label: '15', v: 14}, {label: '16', v: 15}, {label: '17', v: 16}, {label: '18', v: 17}, {label: '19', v: 18}, {label: '20', v: 19}, {label: '21', v: 20}, {label: '22', v: 21}, {label: '23', v: 22}, {label: '24', v: 23}, {label: '25', v: 24}, {label: '26', v: 25}, {label: '27', v: 26}, {label: '28', v: 27}, {label: '29', v: 28}, {label: '30', v: 29}, {label: '31', v: 30}]
																$xTicks				.= ", {label: '".addslashes($values["response"])."', v: ".((int)$values["response_index"])."}";
																$javaResultString	.= ", [".$values["response_index"].", ".$voteResults["total_count"]."]";
															}
															else 
															{
																$xTicks				= "[{label: '".addslashes($values["response"])."', v: ".((int)$values["response_index"])."}";
																$javaResultString	= "[[".$values["response_index"].", ".$voteResults["total_count"]."]";
															}
															
															$phpOutPutArray[$values["response"]] = $voteResults["total_count"];
														}
													}
													
													$javaResultString		.= "]";
													$xTicks					.= "]";
													$i						= 0;
													
													foreach($phpOutPutArray as $key => $value)
													{
														?>
														<tr<?php echo (($i % 2) ? " class=\"odd\"" : ""); ?>>
															<td class="general" style="width: 75%;"><?php echo $key; ?></td>
															<td class="report-hours"><?php echo ((int)$value == 0 || !isset($value) ? "0" : $value); ?></td>
														</tr>
														<?php
														$i++;
													}
													?>
												</tbody>
											</table>
											<!--<div>-->

												<!-- Chart Style
												 	<div style="float: left; vertical-align: middle;">
														<label for="polling-type-list-<?php echo $count; ?>" class="form-nrequired">Chart Style:</label>
														<span id="polling-type-list-<?php echo $count; ?>">
															<img id="polling-type-1" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/polling-type-bar.gif"; ?>" width="16" height="16" alt="Bar" title="Bar" onclick="updatePollTypeIcon('<?php echo $count; ?>', '1');" />
															<img id="polling-type-2" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/polling-type-line.gif"; ?>" width="16" height="16" alt="Line" title="Line" onclick="updatePollTypeIcon('<?php echo $count; ?>', '2');" />
															<img id="polling-type-3" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/polling-type-pie.gif"; ?>" width="16" height="16" alt="Pie" title="Pie" onclick="updatePollTypeIcon('<?php echo $count; ?>', '3');" />
															<img id="polling-type-4" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/polling-type-list.gif"; ?>" width="16" height="16" alt="List" title="List" onclick="updatePollTypeIcon('<?php echo $count; ?>', '4');" />
														</span>
														<input type="hidden" id="polling-type-<?php echo $count; ?>" name="polling-type-<?php echo $count; ?>" value="1" />
													</div>
												-->

									            <!-- Chart Colors
									            <div id="display-colours-<?php echo $count; ?>" style="float: right; vertical-align: middle; margin-bottom: 15px">
										            <label id="label_colors-<?php echo $count; ?>" class="form-nrequired" style="vertical-align: middle">Chart Colours:</label> 
										            
										            <span id="color-icon-list-<?php echo $count; ?>">
														<img id="color-icon-1" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-color-1.gif"; ?>" width="16" height="16" alt="Blue" title="Blue" onclick="updateColorIcon('<?php echo $count; ?>', '1');" />
														<img id="color-icon-2" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-color-2.gif"; ?>" width="16" height="16" alt="Red" title="Red" onclick="updateColorIcon('<?php echo $count; ?>', '2');" />
														<img id="color-icon-3" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-color-3.gif"; ?>" width="16" height="16" alt="Green" title="Green" onclick="updateColorIcon('<?php echo $count; ?>', '3');" />
														<img id="color-icon-4" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-color-4.gif"; ?>" width="16" height="16" alt="Purple" title="Purple" onclick="updateColorIcon('<?php echo $count; ?>', '4');" />
														<img id="color-icon-5" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-color-5.gif"; ?>" width="16" height="16" alt="Cyan" title="Cyan" onclick="updateColorIcon('<?php echo $count; ?>', '5');" />
														<img id="color-icon-6" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-color-6.gif"; ?>" width="16" height="16" alt="Orange" title="Orange" onclick="updateColorIcon('<?php echo $count; ?>', '6');" />
													</span>
													
													<input type="hidden" id="color-icon-<?php echo $count; ?>" name="color-icon-<?php echo $count; ?>" value="1" />
												</div>

												-->

												<!--
													<div id="display-graph-<?php echo $count; ?>" style="clear: both; height: 600px">
														<canvas id="graph-<?php echo $count; ?>" height="300" width="550"></canvas>
													</div>
													
													<div id="display-list-<?php echo $count; ?>" style="clear: both; display: none">
														
													</div>
												-->

											<!--</div>-->
										</td>
									</tr>
								</tbody>
							</table>
						</form>
						<script type="text/javascript">
						function chartReload<?php echo $count; ?>(question_id) {
						   	    var chartStyleSelected = $('polling-type-'+question_id).value;
						   	    
						   	    var chartStyle = "";
								
						   	    switch (chartStyleSelected) {
									case "1": 
										chartStyle = 'bar'; 
										break;
									case "2": 
										chartStyle = 'line'; 
										break;
									case "3": 
										chartStyle = 'pie'; 
										break;
									case "4": 
										chartStyle = 'list'; 
										break;
									default: 
										chartStyle = 'bar';
								}
								
						   	    var forCounter = 1;
								
						   	    if (chartStyle == "list")
						   	    {
						   	    	renderer[<?php echo $count; ?>].clear();
									
						   	    	$("display-graph-"+question_id).style.display = "none";
						   	    	$("display-colours-"+question_id).style.display = "none";
									$("display-list-"+question_id).appear({ duration: 0.3 });
									
									return false;
						   	    }
						   	    else
						   	    {
						   	    	$("display-list-"+question_id).style.display = "none";
						   	    	$("display-colours-"+question_id).appear({ duration: 0.3 });
						   	    	$("display-graph-"+question_id).appear({ duration: 0.3 });
						   	    	
									$("graph-"+question_id).style.display = "none";
						   	    	$("graph-"+question_id).appear({ duration: 0.3 });
									$("label_colors-"+question_id).appear({ duration: 0.3 });
									for(forCounter = 1; forCounter<7; forCounter++)
									{
										$("color-icon-"+forCounter).appear({ duration: 0.3 });
									}
									
							   	    var colorSchemeSelected = $('color-icon-'+question_id).value;
					
							   	    var colorScheme = '';
							   	    
							   	    switch (colorSchemeSelected) {
										case "1": 
											colorScheme = 'Blue'; 
											break;
										case "2": 
											colorScheme = 'Red'; 
											break;
										case "3": 
											colorScheme = 'Green'; 
											break;
										case "4": 
											colorScheme = 'Purple'; 
											break;
										case "5": 
											colorScheme = 'Cyan'; 
											break;
										case "6": 
											colorScheme = 'Orange'; 
											break;
										default: 
											colorScheme = 'Blue';
									}
							   	    
									
									options[<?php echo $count; ?>] = {
									   'yTickPrecision':	1,
									   'xTicks':	 	<?php echo $xTicks; ?>
									}
									
							        // setup layout options
							        var themeName = "office" + colorScheme;
							        var theme<?php echo $count; ?> = PlotKit.Base[themeName]();
							        MochiKit.Base.update(options[<?php echo $count; ?>], theme<?php echo $count; ?>);
							        layout[<?php echo $count; ?>].style = chartStyle;
							        MochiKit.Base.update(layout[<?php echo $count; ?>].options, options[<?php echo $count; ?>]);
							        MochiKit.Base.update(renderer[<?php echo $count; ?>].options, options[<?php echo $count; ?>]);
									
							        layout[<?php echo $count; ?>].addDataset('votes', <?php echo $javaResultString; ?>);
									
									// update
							        layout[<?php echo $count; ?>].evaluate();
							        renderer[<?php echo $count; ?>].clear();
							        renderer[<?php echo $count; ?>].render();
						   	    }
						   }
						</script>
					
						<?php
						
						$count++;
					}
				break;
			} 
		} else {
			$ERROR++;
			$ERRORSTR[] = "This is not a valid id.  Please provide a valid id.";
			
			echo display_error();
			
			application_log("error", "The provided poll id was invalid [".$RECORD_ID."] (My Votes).");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The id that you have provided does not exist in the system. Please provide a valid id to proceed.";
		
		echo display_error();
		
		application_log("error", "The provided poll id was invalid [".$RECORD_ID."] (Vote Poll).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid id to proceed.";

	echo display_error();

	application_log("error", "No poll id was provided to view history. (My Votes)");
}
?>