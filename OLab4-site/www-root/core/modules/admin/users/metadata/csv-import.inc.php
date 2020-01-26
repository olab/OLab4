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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
ini_set('auto_detect_line_endings', true);

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MANAGE_USER_DATA"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "create", false)) {
    add_error($translate->_("module_no_permission") . str_ireplace(array("%admin_email%","%admin_name%"), array(html_encode($AGENT_CONTACTS["administrator"]["email"]),html_encode($AGENT_CONTACTS["administrator"]["name"])), $translate->_("module_assistance")));
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    require_once("Entrada/metadata/functions.inc.php");

    echo "<h1>".$translate->_("metadata_heading_import")."</h1>";

    $csv_headings = array(
        "proxy_id"        => array("title" => $translate->_("metadata_field_id")),
        "number"          => array("title" => $translate->_("metadata_field_number"),  "required" => true),
        "role"            => array("title" => $translate->_("metadata_field_role")),
        "group"           => array("title" => $translate->_("metadata_field_group")),
        "first_name"      => array("title" => $translate->_("metadata_field_first")),
        "last_name"       => array("title" => $translate->_("metadata_field_last")),
        "username"        => array("title" => $translate->_("metadata_field_user")),
        "type"            => array("title" => $translate->_("metadata_field_type")),
        "value"           => array("title" => $translate->_("metadata_field_value"),  "required" => true),
        "notes"           => array("title" => $translate->_("metadata_field_notes")),
        "effective_date"  => array("title" => $translate->_("metadata_field_effective")),
        "expiry_date"     => array("title" => $translate->_("metadata_field_expiry"))
    );

    $url = ENTRADA_URL ."/admin/$MODULE/$SUBMODULE?section=load";
    
    $posts = false;

    if (isset($_POST["organisation_id"]) && $organisation_id = clean_input($_POST["organisation_id"], "int")) {
        if (isset($_POST["group"]) && $group = clean_input($_POST["group"], array("notags", "trim"))) {
            if (isset($_POST["role"]) && $role = clean_input($_POST["role"], array("notags", "trim"))) {
                if (isset($_POST["cat_id"]) && $cat_id = clean_input($_POST["cat_id"], "int")) {
                    $posts = true;
                }
            }
        }
    }

    if (!$posts) {
        add_error($translate->_("metadata_error_criteria"));
        echo display_error();

    } else {
	switch ($STEP) {
		case 2 :
			
                if (isset($_POST["csv_file"]) && $tmp_input = clean_input($_POST["csv_file"], "trim")) {
				$PROCESSED["csv_file"] = $tmp_input;
			}

			if (isset($_POST["csv"]) && $tmp_input = clean_input($_POST["csv"], "alphanumeric")) {
				$PROCESSED["csv_filename"] = $tmp_input;
			}
			
			if (isset($_POST["mapped_headings"]) && is_array($_POST["mapped_headings"])) {
				foreach ($_POST["mapped_headings"] as $col => $heading) {
					$PROCESSED["col_map"][(int) $col] = clean_input($heading, array("trim", "striptags"));
				}
			}

			$replace = isset($_POST["replace_entry"]);
			$delete = isset($_POST["delete_empty"]);

			if (file_exists(CACHE_DIRECTORY."/".$PROCESSED["csv_filename"])) {

				$csv_importer = new CsvImporter($cat_id, $role, $group, $organisation_id, $ENTRADA_USER->getActiveId(), $PROCESSED["col_map"], $delete, $replace);

				$csv_importer->importCsv(CACHE_DIRECTORY."/".$PROCESSED["csv_filename"]);
				
				$csv_errors = $csv_importer->getErrors();
				if ($csv_errors) {
					$err_msg  = $translate->_("metadata_error_import");
					$err_msg .= "<pre>";
					foreach ($csv_errors as $rowid => $error) {
						foreach ($error as $msg) {
							$err_msg .= "Row ".$rowid.": ".html_encode($msg)."\n";
						}
					}
					$err_msg .= "</pre>";
					$err_msg .=  str_ireplace("%url%", $url, $translate->_("metadata_error_import_return"));

					add_error($err_msg);
					echo display_error();
				} else {
					$csv_success = $csv_importer->getSuccess();
					$csv_empty = $csv_importer->getEmpty();

					if ($csv_success) {
						add_success(str_ireplace(array("%count%", "%csv%"), array(count($csv_success),html_encode($PROCESSED["csv_file"])),$translate->_("metadata_notice_imported")).($csv_empty?"":str_ireplace(array("%time%","%url%"), array(10,$url), $translate->_("metadata_redirect"))));
						echo display_success();

						application_log("success", "Proxy_id [" . $ENTRADA_USER->getActiveId() . "] successfully imported " . count($csv_success) . " records into meta_values table");
					}

					if ($csv_importer->getDeleted()) {
						add_notice(str_ireplace(array("%count%", "%csv%"), array($csv_importer->getDeleted(),html_encode($PROCESSED["csv_file"])),$translate->_("metadata_notice_delete")));

						application_log("success", "Proxy_id [" . $ENTRADA_USER->getActiveId() . "] successfully deleted " . $csv_importer->getDeleted() . " records from meta_values table");
					}

					if ($csv_empty) {
						add_notice(str_ireplace(array("%count%", "%csv%"), array(count($csv_empty),html_encode($PROCESSED["csv_file"])),$translate->_("metadata_notice_empty")).str_ireplace(array("%time%","%url%"), array(10,$url), $translate->_("metadata_redirect")));		
					}
					echo display_notice();

					$ONLOAD[] = "setTimeout('window.location=\\'${url}\\'', 5000)";
				}
			} else {
				add_error($translate->_("metadata_error_file_missing"));

				application_log("error", "Unable to find expected file [".CACHE_DIRECTORY."/".$PROCESSED["csv_filename"]."]");
			}
			
			break;
		case 1 :
		default :
			$unmapped_fields = $csv_headings;

			if (isset($_FILES["csv_file"])) {
				switch ($_FILES["csv_file"]["error"]) {
					case 1 :
					case 2 :
					case 3 :
						add_error(str_ireplace("%url%", $url, $translate->_("metadata_error_file1-3")));
					break;
					case 4 :
						add_error(str_ireplace("%url%", $url, $translate->_("metadata_error_file4")));
					break;
					case 6 :
					case 7 :
						add_error(str_ireplace("%url%", $url, $translate->_("metadata_error_file6-7")));
					break;
					default :
						continue;
					break;
				}
			} else {
				add_error($translate->_("metadata_error_file_none"));
			}

			if (!has_error()) {
				?>
				<style type="text/css">
					#unmapped-fields {
						margin-left:0px;
						padding-bottom:14px;
					}
					ul.nostyle {
						list-style:none;
						margin:0px;
						padding:0px;
					}
					.drop-target {
						background:#D9EDF7!important;
						border-top:1px dashed #C8DCE6 !important;
						border-right:1px dashed #C8DCE6  !important;
						border-bottom:1px dashed #C8DCE6  !important;
						border-left:1px dashed #C8DCE6  !important;
					}
					.draggable-title {
						cursor: pointer;
						margin-right:4px;
						margin-bottom:5px;
					}
				</style>
				<?php
				echo display_generic($translate->_("metadata_notice_csv"));
				echo "<h4>".$translate->_("metadata_heading_unmapped")."</h4>";
				if (($handle = fopen($_FILES["csv_file"]["tmp_name"], "r")) !== FALSE) {
					$tmp_name = explode("/", $_FILES["csv_file"]["tmp_name"]);
					$new_filename = md5(end($tmp_name));
					
					// add some error handling here
					copy($_FILES["csv_file"]["tmp_name"], CACHE_DIRECTORY."/".$new_filename);
					
					// we just want the headings
					$i = 0;
					while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $i < 5) {
						if ($i === 0) {
							$j = 0;
							foreach ($data as $d) {
								$mapped = false;
								$title = "";
								$key = strtolower(str_replace(" ", "_", clean_input($d, "boolops")));
								if (isset($csv_headings[$key])){
									$mapped = true;
									$title = $csv_headings[$key]["title"];
									unset($unmapped_fields[$key]);
								}

								$output[] = "<tr class=\"".($mapped === true && isset($csv_headings[$key]["required"]) && $csv_headings[$key]["required"] === true ? "success" : "")."\">\n";
								$output[] = "<td style=\"text-align:center!important;\">".($mapped === true ? "<a href=\"#\" class=\"remove\"><i class=\"icon-remove-sign\"></i></a>" : "")."</td>\n";
								$output[] = "<td class=\"" . ($mapped === false ? "droppable-title-container" : "") . "\">" . $title . ($mapped === false ? " " : " <input type=\"hidden\" name=\"mapped_headings[" . $j . "]\" value=\"" . $key . "\" />") . "</td>\n";
								$output[] = "<td><strong>".$d."</strong></td>\n";
								$output[] = "</tr>\n";

								$j++;
							}  
						}
						$output_data = array();
						foreach ($data as $key => $field) {
							$clean_field = str_replace("'", "&#39;", $field);
							$output_data[$key] = $clean_field;
						}
						$json_rows[] = $output_data;
						$i++;
					}

					fclose($handle);

					echo "<div class=\"space-below row well\" id=\"unmapped-fields\">";
					if (!empty($unmapped_fields)) {

						foreach ($unmapped_fields as $field_name => $field) {
							echo "<span data-field-name=\"" . $field_name . "\" class=\"draggable-title label pull-left " . (isset($field["required"]) && $field["required"] === true ? "label-important" : "") . "\"><i class=\"icon-move icon-white\"></i> <span class=\"label-text\">" . $field["title"] . "</span></span>";
						}
					}
					echo "</div>";

					?>

					<script type="text/javascript">
						var json_rows = <?php echo json_encode($json_rows); ?>;

						jQuery(function($) {

							var current_row = 0;
							var max_row = (json_rows.length - 1);

							$(".row-nav").on("click", function(e) {
								var button = $(this);

								if (button.children("i").hasClass("icon-arrow-right")) {
									if (current_row < max_row) {
										current_row++;
										updateRows(json_rows[current_row]);
									}
								} else {
									if (current_row > 0) {
										current_row--;
										updateRows(json_rows[current_row]);
									}
								}

								e.preventDefault();
							});

							function updateRows(jsonData) {
								for (var i = 0; i < jsonData.length; i++) {
									$(".csv-map tbody tr").eq(i).children("td").eq(2).html("<strong>" + jsonData[i] + "</strong>");
								}
							}

							$(".draggable-title").draggable({
								start: function (event, ui) {
									$(".droppable-title-container").addClass("drop-target");
								},
								stop: function (event, ui) {
									$(".droppable-title-container").removeClass("drop-target");
								},
								revert: true
							});

							$(".droppable-title-container").droppable({
								drop: function (event, ui) {
									var drop_target = $(this);
									handleDrop(drop_target, event, ui);
								}
							});

							$(".csv-map").on("click", "a.remove", function(e) {
								var parent = $(this).closest("tr");
								var draggable_title = $(document.createElement("span"));
								var field_name = parent.children("td").eq(1).children("input[type=hidden]").val();
								parent.children("td").eq(1).children("input[type=hidden]").remove();
								draggable_title.addClass("label draggable-title pull-left " + (parent.hasClass("success") ? "label-important" : "")).attr("data-field-name", field_name).html("<i class=\"icon-move icon-white\"></i> <span class=\"label-text\">" + parent.children("td").eq(1).html() + "</span>").draggable({
									start: function (event, ui) {
										$(".droppable-title-container").addClass("drop-target");
									},
									stop: function (event, ui) {
										$(".droppable-title-container").removeClass("drop-target");
									},
									revert: true
								});

								$("#unmapped-fields").append(draggable_title);
								parent.removeClass("success");
								parent.children("td").eq(0).html("");
								parent.children("td").eq(1).html("").addClass("droppable-title-container").droppable({
									drop: function (event, ui) {
										var drop_target = $(this);
										handleDrop(drop_target, event, ui);
									}
								});
								e.preventDefault();
							});

							function handleDrop(drop_target, event, ui) {
								$(".droppable-title-container").removeClass("drop-target");
								drop_target.html(ui.draggable.children(".label-text").html()).removeClass("droppable-title-container").droppable("destroy");

								var input = $(document.createElement("input"));
								input.attr({type : 'hidden', value : ui.draggable.data("field-name"), name : 'mapped_headings['+drop_target.closest("tbody tr").index()+']'})

								drop_target.append(input);

								if (ui.draggable.hasClass("label-important")) {
									drop_target.closest("tr").addClass("success");
								}

								var remove_link = $(document.createElement("a"));
								remove_link.addClass("remove").attr("href", "#");
								var remove_icon = $(document.createElement("i"));
								remove_icon.addClass("icon-remove-sign").wrap($(document.createElement("a")));
								remove_link.append(remove_icon);
								drop_target.closest("tr").children("td").eq(0).append(remove_link);

								ui.draggable.remove();
							}
						});
					</script>
					<form class="form" action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE . "/" . $SUBMODULE; ?>?section=csv-import" method="POST">
						<input type="hidden" name="csv" value="<?php echo $new_filename; ?>" />
						<input type="hidden" name="csv_file" value="<?php echo $_FILES["csv_file"]["name"]; ?>"/>
						<input type="hidden" name="step" value="2" />
						<?php
						echo "<input type=\"hidden\" name=\"organisation_id\" value=\"$organisation_id\" />";
						echo "<input type=\"hidden\" name=\"group\" value=\"$group\" />";
						echo "<input type=\"hidden\" name=\"role\" value=\"$role\" />";
						echo "<input type=\"hidden\" name=\"cat_id\" value=\"$cat_id\" />";
						?>
						<table class="table table-striped table-bordered csv-map">
							<thead>
								<tr>
									<th width="6%"></th>
									<th width="47%"><?php echo $translate->_("metadata_heading_mapped") ?></th>
									<th width="47%"><?php echo $_FILES["csv_file"]["name"]; ?> <div class="pull-right"><a href="#" class="row-nav"><i class="icon-arrow-left"></i></a><a href="#" class="row-nav"><i class="icon-arrow-right"></i></a></div></th>
								</tr>
							</thead>
							<tbody>
								<?php echo implode("", $output); ?>
							</tbody>
						</table>
						<label class="checkbox form-required space-above offset1" for="replace_entry">
								<input type="checkbox" id="replace_entry" name="replace_entry" value="1" checked="checked" /><?php echo $translate->_("metadata_replace_entry") ?>
						</label>
						<label class="checkbox form-required space-above offset1" for="delete_empty">
								<input type="checkbox" id="delete_empty" name="delete_empty" value="1" /><?php echo $translate->_("metadata_delete_empty") ?>
						</label>
						<div class="row-fluid">
							<a href="<?php echo $url; ?>" class="btn">Back</a>
							<input class="btn btn-primary pull-right" type="Submit" value="Import" />
						</div>
					</form>
					<?php
				}
			} else {
				echo display_error();
				$ONLOAD[] = "setTimeout('window.location=\\'$url\\'', 5000)";
			}

			break;
		}
    }
}
