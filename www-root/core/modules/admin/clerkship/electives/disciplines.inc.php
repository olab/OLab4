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
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP")) || (!defined("IN_ELECTIVES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif ((!isset($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"])) || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "staff" && $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "medtech" && $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "faculty")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} elseif (!permissions_check(array("medtech" => "admin", "faculty" => "admin", "staff" => "admin"))) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/clerkship/electives?".replace_query(array("section" => "add")), "title" => "Discipline Breakdown");
	
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
	
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/clerkship_disciplines.js\"></script>";
	
	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}
			if ($NOTICE) {
				echo display_notice();
			}
			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			$ONLOAD[] = "initDynamicTable()";
			$ONLOAD[] = "updatePollTypeIcon('4')";
			$ONLOAD[] = "updateColorIcon('1')";
			
			$proxy_id = clean_input($_GET["id"], "int");
			
			$student_name = get_account_data("firstlast", $proxy_id);
			
			echo "<h1>Discipline Breakdown For ".$student_name."</h1>\n";

			if ($ERROR) {
				echo display_error();
			}
			?>
			<form name="viewDisciplines" action="<?php echo ENTRADA_URL; ?>/clerkship/electives" method="post">
			
			<div>
			 	<div style="float: left; vertical-align: middle;">
					<label for="PollingType" class="form-nrequired">Chart Style:</label> 
					
					<span id="polling-type-list">
						<img id="polling-type-1"src="<?php echo ENTRADA_URL; ?>/images/polling-type-bar.gif" width="16" height="16" alt="Bar" title="Bar" onclick="updatePollTypeIcon('1');" />
						<img id="polling-type-2"src="<?php echo ENTRADA_URL; ?>/images/polling-type-line.gif" width="16" height="16" alt="Line" title="Line" onclick="updatePollTypeIcon('2');" />
						<img id="polling-type-3"src="<?php echo ENTRADA_URL; ?>/images/polling-type-pie.gif" width="16" height="16" alt="Pie" title="Pie" onclick="updatePollTypeIcon('3');" />
						<img id="polling-type-4"src="<?php echo ENTRADA_URL; ?>/images/polling-type-list.gif" width="16" height="16" alt="List" title="List" onclick="updatePollTypeIcon('4');" />
					</span>
					
					<input type="hidden" id="polling-type" name="polling-type" value="4" />
				</div>
	            
	            <div id="display-colours" style="float: right; vertical-align: middle; margin-bottom: 15px; display: none">
		            <label id="label_colors" class="form-nrequired" style="vertical-align: middle">Chart Colours:</label> 
		            
		            <span id="color-icon-list">
						<img id="color-icon-1"src="<?php echo ENTRADA_URL; ?>/images/list-color-1.gif" width="16" height="16" alt="Blue" title="Blue" onclick="updateColorIcon('1');" />
						<img id="color-icon-2"src="<?php echo ENTRADA_URL; ?>/images/list-color-2.gif" width="16" height="16" alt="Red" title="Red" onclick="updateColorIcon('2');" />
						<img id="color-icon-3"src="<?php echo ENTRADA_URL; ?>/images/list-color-3.gif" width="16" height="16" alt="Green" title="Green" onclick="updateColorIcon('3');" />
						<img id="color-icon-4"src="<?php echo ENTRADA_URL; ?>/images/list-color-4.gif" width="16" height="16" alt="Purple" title="Purple" onclick="updateColorIcon('4');" />
						<img id="color-icon-5"src="<?php echo ENTRADA_URL; ?>/images/list-color-5.gif" width="16" height="16" alt="Cyan" title="Cyan" onclick="updateColorIcon('5');" />
						<img id="color-icon-6"src="<?php echo ENTRADA_URL; ?>/images/list-color-6.gif" width="16" height="16" alt="Orange" title="Orange" onclick="updateColorIcon('6');" />
					</span>
					
					<input type="hidden" id="color-icon" name="color-icon" value="1" />
				</div>

				<div id="display-graph" style="clear: both; height: 850px;">
					<canvas id="graph" height="400" width="750"></canvas>
				</div>
				
				<div id="display-list" style="clear: both; display: none">
					<table style="padding: 10px" class="tableList" cellspacing="0" summary="Vote Report">
					<colgroup>
						<col class="general" style="width: 70%;" />
						<col class="report-hours" style="width: 30%;" />
					</colgroup>
					<thead>
						<tr>
							<td class="general" style="width: 70%; padding-left: 5px; border-left: 1px solid #999999">Disciplines</td>
							<td class="report-hours" style="width: 30%;" />Weeks (Pending / Approved)</td>
						</tr>
					</thead>
					<tbody>
						<?php
						$query		 = "	SELECT `event_start`, `event_finish`, `discipline_id`
											FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`electives`, `".CLERKSHIP_DATABASE."`.`event_contacts`
											WHERE `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
											AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`
											AND `".CLERKSHIP_DATABASE."`.`event_contacts`.`etype_id` = ".$db->qstr($proxy_id)." 
											AND `".CLERKSHIP_DATABASE."`.`events`.`event_type` = \"elective\"
											AND `".CLERKSHIP_DATABASE."`.`events`.`event_status` != \"trash\"
											ORDER BY `discipline_id` ASC";
						$results	= $db->GetAll($query);
						$count		= 1;
						
						$lastDiscipline	= "";
						$weeksCounter 	= 0;
						$disciplines    = array();
						
						foreach ($results as $result) {
							$discipline = clerkship_fetch_specific_discipline($result["discipline_id"]);
							
							if (($lastDiscipline == "") || ($lastDiscipline == $discipline)) {
								$difference = ($result["event_finish"] - $result["event_start"]) / 604800;
								$weeksCounter = $weeksCounter + ceil($difference);
							} else {
								$disciplines[$lastDiscipline] = $weeksCounter;
								$difference = ($result["event_finish"] - $result["event_start"]) / 604800;
								$weeksCounter = ceil($difference);
							}
							$lastDiscipline = $discipline;
						}
						
						$currentDiscipline = clerkship_fetch_specific_discipline($result["discipline_id"]);
						$disciplines[$currentDiscipline] = $weeksCounter;
						
						$xTicks = "";
						
						$testCounter = 0;
						
						foreach ($disciplines as $key => $value) {
							if ($xTicks != "")
							{
								// The following comment is an example of what to pass to PlotKit.
								//[{label: '01', v: 0}, {label: '02', v: 1}, {label: '03', v: 2}, {label: '04', v: 3}, {label: '05', v: 4}, {label: '06', v: 5}, {label: '07', v: 6}, {label: '08', v: 7}, {label: '09', v: 8}, {label: '10', v: 9}, {label: '11', v: 10}, {label: '12', v: 11}, {label: '13', v: 12}, {label: '14', v: 13}, {label: '15', v: 14}, {label: '16', v: 15}, {label: '17', v: 16}, {label: '18', v: 17}, {label: '19', v: 18}, {label: '20', v: 19}, {label: '21', v: 20}, {label: '22', v: 21}, {label: '23', v: 22}, {label: '24', v: 23}, {label: '25', v: 24}, {label: '26', v: 25}, {label: '27', v: 26}, {label: '28', v: 27}, {label: '29', v: 28}, {label: '30', v: 29}, {label: '31', v: 30}]
								$xTicks				.= ", {label: '".addslashes($key)."', v: ".((int)$testCounter)."}";
								$javaResultString	.= ", [".$testCounter.", ".$value."]";
							}
							else 
							{
								$xTicks				= "[{label: '".addslashes($key)."', v: ".((int)$testCounter)."}";
								$javaResultString	= "[[".$testCounter.", ".$value."]";
							}
							
							$testCounter++;
							$phpOutPutArray[$key] = $value;
						}
						
						$javaResultString		.= "]";
						$xTicks					.= "]";
						$i						= 0;
						
						foreach ($phpOutPutArray as $key => $value)
						{
							?>
							<tr <?php echo (($i % 2) ? " class=\"odd\"" : ""); ?>>
								<td class="general" style="width: 75%; padding-left: 5px"><?php echo $key; ?></td>
								<td class="report-hours" align="center"><?php echo ((int)$value == 0 || !isset($value) ? "0" : $value); ?></td>
							</tr>
							<?php
							$i++;
						}
						?>
					</tbody>
					</table>
				</div>
			</div>
			</form>
			<script type="text/javascript">
			function chartReload() {
					var chartStyleSelected = $('polling-type').value;
			   	    
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
			   	    	renderer.clear();
						
			   	    	$("display-graph").style.display = "none";
			   	    	$("display-colours").style.display = "none";
						$("display-list").appear({ duration: 0.3 });
						
						return false;
			   	    }
			   	    else
			   	    {
			   	    	$("display-list").style.display = "none";
			   	    	$("display-colours").appear({ duration: 0.3 });
			   	    	$("display-graph").appear({ duration: 0.3 });
			   	    	
						$("graph").style.display = "none";
			   	    	$("graph").appear({ duration: 0.3 });
						$("label_colors").appear({ duration: 0.3 });
						for (forCounter = 1; forCounter<7; forCounter++)
						{
							$("color-icon-"+forCounter).appear({ duration: 0.3 });
						}
						
				   	    var colorSchemeSelected = $('color-icon').value;
		
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
				   	     
						var options = {
						   'yTickPrecision':	1,
						   'xTicks':	 	<?php echo $xTicks; ?>
						}
						
				        // setup layout options
				        var themeName = "office" + colorScheme;
				        var theme = PlotKit.Base[themeName]();
				        MochiKit.Base.update(options, theme);
				        
				        layout.style = chartStyle;
				        MochiKit.Base.update(layout.options, options);
				        MochiKit.Base.update(renderer.options, options);
						
				        layout.addDataset('disciplines', <?php echo $javaResultString; ?>);
						
						// update
				        layout.evaluate();
				        renderer.clear();
				        renderer.render();
			   	    }
			   }
			</script>
			<?php
		break;
	}
}