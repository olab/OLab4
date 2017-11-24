<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to edit users from the entrada_auth.user_data table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($PROXY_ID) {
		
		$ajax_action = clean_input($_POST["ajax_action"], "alpha");

		if (!empty($ajax_action)) {
			ob_clear_open_buffers();

			switch ($ajax_action) {
				case "uploadimage" :
                    $sizes = array("upload" => array("width" => 250, "height" => 250), "upload-thumbnail" => array("width" => 98, "height" => 98));

                    $filesize = moveImage($_FILES["image"]["tmp_name"], $PROXY_ID, $_POST["coordinates"], $_POST["dimensions"], "user", $sizes);

					if ($filesize) {
						$PROCESSED_PHOTO["proxy_id"]			= $PROXY_ID;
						$PROCESSED_PHOTO["photo_active"]		= 1;
						$PROCESSED_PHOTO["photo_type"]			= 1;
						$PROCESSED_PHOTO["updated_date"]		= time();
						$PROCESSED_PHOTO["photo_filesize"]		= $filesize;

						$query = "SELECT `photo_id` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `proxy_id` = ".$db->qstr($PROXY_ID);
						$photo_id = $db->GetOne($query);

						if ($photo_id) {
							if ($db->AutoExecute(AUTH_DATABASE.".user_photos", $PROCESSED_PHOTO, "UPDATE", "`photo_id` = ".$db->qstr($photo_id))) {
								echo json_encode(array("status" => "success", "data" => webservice_url("photo", array($PROXY_ID, "upload"))."/".time()));
							}
						} else {
							if ($db->AutoExecute(AUTH_DATABASE.".user_photos", $PROCESSED_PHOTO, "INSERT")) {
								echo json_encode(array("status" => "success", "data" => webservice_url("photo", array($PROXY_ID, "upload"))."/".time()));
							} else {
								echo json_encode(array("status" => "error"));
							}
						}
					}
				break;
				case "togglephoto" :
					$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_photos` WHERE `proxy_id` = ".$db->qstr($PROXY_ID);
					$photo_record = $db->GetRow($query);
					if ($photo_record) {
						$photo_active = ($photo_record["photo_active"] == "1" ? "0" : "1");
						$query = "UPDATE `".AUTH_DATABASE."`.`user_photos` SET `photo_active` = ".$db->qstr($photo_active)." WHERE `proxy_id` = ".$db->qstr($PROXY_ID);
						if ($db->Execute($query)) {
							echo json_encode(array("status" => "success", "data" => array("imgurl" => webservice_url("photo", array($PROXY_ID, $photo_active == "1" ? "upload" : "official" ))."/".time(), "imgtype" => $photo_active == "1" ? "uploaded" : "official")));
						} else {
							application_log("error", "An error occurred while attempting to update user photo active flag for user [".$PROXY_ID."], DB said: ".$db->ErrorMsg());
							echo json_encode(array("status" => "error"));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "No uploaded photo record on file. You must upload a photo before you can toggle photos."));
					}
				break;
			}
			
			exit;
			
		}
		
		$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($PROXY_ID);
		$user_record = $db->GetRow($query);
		if ($user_record) {
			$BREADCRUMB[] = array("url" => "", "title" => "Overview");

			$PROCESSED_ACCESS = array();
			$PROCESSED_DEPARTMENTS = array();
			$department_names = array();

			$PROCESSED = $user_record;

			$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($PROXY_ID)." AND `app_id` = ".$db->qstr(AUTH_APP_ID);
			$PROCESSED_ACCESS = $db->GetRow($query);

			$query = "SELECT `dep_id` FROM `".AUTH_DATABASE."`.`user_departments` WHERE `user_id` = ".$db->qstr($PROXY_ID);
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$PROCESSED_DEPARTMENTS[] = (int) $result["dep_id"];
					$query = "SELECT * FROM `".AUTH_DATABASE."`.`departments` WHERE `department_id` = " . (int) $result["dep_id"];
					$dept = $db->GetROW($query);
					if ($dept) {
						$department_names[] = $dept["department_title"];
					}
				}
				sort($department_names);
			}

			$gender = $user_record["gender"];

			$query = "SELECT * FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ". $user_record["organisation_id"];
			$default_organisation = $db->GetRow($query);

			$organisation_names = array();
			$query = "	SELECT `organisation_id`
						FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `user_id` = ".$db->qstr($PROXY_ID). "
						AND `app_id` = " . $db->qstr(AUTH_APP_ID);
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$query = "SELECT * FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ". $result["organisation_id"];
					$org = $db->GetRow($query);
					if ($org) {
						$organisation_names[] = $org["organisation_title"];
					}
				}
				sort($organisation_names);
			}

			if ($ERROR) {
				echo display_error();
			}

			if ($NOTICE) {
				echo display_notice();
			}
			?>

			<h1 title="User Profile Section">User Profile for <?php echo html_encode($user_record["firstname"]." ".$user_record["lastname"]); ?></h1>
			<div class="row-fluid">
				<div class="span5">
					<script src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery.imgareaselect.min.js" type="text/javascript"></script>
					<link href='<?php echo ENTRADA_URL; ?>/css/imgareaselect-default.css' rel='stylesheet' type='text/css' />
                    <link href='<?php echo ENTRADA_URL; ?>/css/profile/profile.css' rel='stylesheet' type='text/css' />
					<?php $profile_image = ENTRADA_ABSOLUTE . '/../public/images/' . $PROXY_ID . '/' . $PROXY_ID . '-large.png'; ?>
					<script type="text/javascript">
					function dataURItoBlob(dataURI, type) {
						type = typeof a !== 'undefined' ? type : 'image/jpeg';
						var binary = atob(dataURI.split(',')[1]);
						var array = [];
						for (var i = 0; i < binary.length; i++) {
							array.push(binary.charCodeAt(i));
						}
						return new Blob([new Uint8Array(array)], {type: type});
					}

					jQuery(function(){

						jQuery('#profile-image-container').on("click", '#btn-toggle .btn', function() {
							var clicked = jQuery(this);
							if (!clicked.parent().hasClass(clicked.html().toLowerCase())) {
								jQuery.ajax({
									url : "<?php echo ENTRADA_URL; ?>/admin/users/manage?id=<?php echo $PROXY_ID; ?>",
									data : "ajax_action=togglephoto",
									type : "post",
									async : true,
									success : function(data) {
										var jsonResponse = JSON.parse(data);
										jQuery("#profile-image-container span img").attr("src", jsonResponse.data.imgurl);
										jQuery("#btn-toggle .btn.active").removeClass("active");
										clicked.addClass("active");
										clicked.parent().removeClass((jsonResponse.data.imgtype == "uploaded" ? "official" : "uploaded")).addClass(jsonResponse.data.imgtype);
									}
								});
							}
							return false;
						});

						function selectImage(image){
							jQuery(".description").hide();
							var image_width;
							var image_height;
							var w_offset;
							var h_offset

							image_width = image.width();
							image_height = image.height();
							w_offset = parseInt((image_width - 150) / 2);
							h_offset = parseInt((image_height - 150) / 2);

							jQuery("#coordinates").attr("value", w_offset + "," + h_offset + "," + (w_offset + 153) + "," + (h_offset + 200));
							jQuery("#dimensions").attr("value", image_width + "," + image_height)

							image.imgAreaSelect({
								aspectRatio: '98:98',
								handles: true,
								x1: w_offset, y1: h_offset, x2: w_offset + 150, y2: h_offset + 150,
								instance: true,
								persistent: true,
								onSelectEnd: function (img, selection) {
									jQuery("#coordinates").attr("value", selection.x1 + "," + selection.y1 + "," + selection.x2 + "," + selection.y2);
								}
							});
						};

						jQuery(".org-profile-image").hover(function(){
							jQuery(this).find("#edit-button").animate({"opacity" : 100}, {queue: false}, 150).css("display", "block");
						}, function() {
							jQuery(this).find("#edit-button").animate({"opacity" : 0}, {queue: false}, 150);
						});

						/* file upload stuff starts here */

						var reader = new FileReader();

						reader.onload = function (e) {
							jQuery(".preview-image").attr('src', e.target.result)
							jQuery(".preview-image").load(function(){
								selectImage(jQuery(".preview-image"));
							});
						};

						// Required for drag and drop file access
						jQuery.event.props.push('dataTransfer');

						jQuery("#upload-image").on('drop', function(event) {

							jQuery(".modal-body").css("background-color", "#FFF");

							event.preventDefault();

							var file = event.dataTransfer.files[0];

							if (file.type.match('image.*')) {
								jQuery("#image").html(file);
								reader.readAsDataURL(file);
							} else {
								// However you want to handle error that dropped file wasn't an image
							}
						});

						jQuery("#upload-image").on("dragover", function(event) {
							jQuery(".modal-body").css("background-color", "#f3f3f3");
							return false;
						});

						jQuery("#upload-image").on("dragleave", function(event) {
							jQuery(".modal-body").css("background-color", "#FFF");
						});

						jQuery('#upload-image').on('hidden', function () {
							if (jQuery(".profile-image-preview").length > 0) {
								jQuery(".profile-image-preview").remove();
								jQuery(".imgareaselect-selection").parent().remove();
								jQuery(".imgareaselect-outer").remove();
								jQuery("#image").val("");
								jQuery(".description").show();
							}
						});

						jQuery('#upload-image').on('shown', function() {
							if (jQuery(".profile-image-preview").length <= 0) {
								var preview = jQuery("<div />").addClass("profile-image-preview");
								preview.append("<img />");
								preview.children("img").addClass("preview-image");
								jQuery(".preview-img").append(preview);
							}
						});

						jQuery('#upload-image').on("click", '#upload-image-button', function(){
							if (typeof jQuery(".preview-image").attr("src") != "undefined") {
								jQuery("#upload_profile_image_form").submit();
								jQuery('#upload-image').modal("hide");
							} else {
								jQuery('#upload-image').modal("hide");
							}
						});

						jQuery('#upload_profile_image_form').submit(function(){
							var imageFile = dataURItoBlob(jQuery(".preview-image").attr("src"));

							var xhr = new XMLHttpRequest();
							var fd = new FormData();
							fd.append('ajax_action', 'uploadimage');
							fd.append('image', imageFile);
							fd.append('coordinates', jQuery("#coordinates").val());
							fd.append('dimensions', jQuery("#dimensions").val());

							xhr.open('POST', "<?php echo ENTRADA_URL; ?>/admin/users/manage?id=<?php echo $PROXY_ID; ?>", true);
							xhr.send(fd);

							xhr.onreadystatechange = function() {
								if (xhr.readyState == 4 && xhr.status == 200) {
									var jsonResponse = JSON.parse(xhr.responseText);
									if (jsonResponse.status == "success") {
										jQuery("#profile-image-container img.img-polaroid").attr("src", jsonResponse.data);
										if (jQuery("#image-nav-right").length <= 0) {
											jQuery("#btn-toggle").append("<a href=\"#\" class=\"btn btn-small active\" id=\"image-nav-right\" style=\"display:none;\">Uploaded</a>");
											jQuery("#image-nav-right").removeClass("active");
										}
									} else {
										// Some kind of failure notification.
									};
								} else {
									// another failure notification.
								}
							}

							if (jQuery(".profile-image-preview").length > 0) {
								jQuery(".profile-image-preview").remove();
								jQuery(".imgareaselect-selection").parent().remove();
								jQuery(".imgareaselect-outer").remove();
								jQuery("#image").val("");
								jQuery(".description").show();
							}

							return false;
						});

						jQuery('#upload_profile_image_form').on("change", "#image", function(){
							var files = jQuery(this).prop("files");

							if (files && files[0]) {
								reader.readAsDataURL(files[0]);
							}
						});

						jQuery("#profile-image-container").hover(function(){
							jQuery("#profile-image-container .btn, #btn-toggle").fadeIn("fast");
						},
						function() {
							jQuery("#profile-image-container .btn").fadeOut("fast");
						});
					});
					</script>
					<div id="upload-image" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
							<h3 id="label">Upload Photo</h3>
						</div>
						<div class="modal-body">
							<div class="preview-img"></div>
							<div class="description alert" style="height:264px;width:483px;padding:20px;">
								To upload a new profile image you can drag and drop it on this area, or use the Browse button to select an image from your computer.
							</div>
						</div>
						<div class="modal-footer">
							<form name="upload_profile_image_form" id="upload_profile_image_form" action="<?php echo ENTRADA_URL; ?>/profile" method="post" enctype="multipart/form-data">
								<input type="hidden" name="coordinates" id="coordinates" value="" />
								<input type="hidden" name="dimensions" id="dimensions" value="" />
								<input type="file" name="image" id="image" />
							</form>
							<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
							<button id="upload-image-button" class="btn btn-primary">Upload</button>
						</div>
					</div>
					<div id="profile-image-container">
						<a href="#upload-image" id="upload-image-modal-btn" data-toggle="modal" class="btn btn-primary" id="upload-profile-image">Upload Photo</a>
						<?php
						$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_photos` WHERE `proxy_id` = ".$db->qstr($PROXY_ID);
						$uploaded_photo = $db->GetRow($query);
						?>
						<span><img src="<?php echo webservice_url("photo", array($PROXY_ID, $uploaded_photo ? "upload" : "official"))."/".time(); ?>" class="img-polaroid" /></span>
						<div class="btn-group" id="btn-toggle" class=" <?php echo $uploaded_photo ? "uploaded" : "official"; ?>">
							<a href="#" class="btn btn-small <?php echo $uploaded_photo["photo_active"] == "0" ? "active" : ""; ?>" id="image-nav-left">Official</a>
							<?php if ($uploaded_photo) { ?><a href="#" class="btn btn-small <?php echo $uploaded_photo["photo_active"] == "1" ? "active" : ""; ?>" id="image-nav-right">Uploaded</a><?php } ?>
						</div>
					</div>
				</div>
				<div class="span7">
					<div class="row">
						<div class="span3"><strong>Full Name:</strong></div>
						<div class="span9"><?php echo $user_record["prefix"]." ".$user_record["firstname"]." ".$user_record["lastname"]; ?></div>
					</div>
					<div class="row">
						<div class="span3"><strong>Number:</strong></div>
						<div class="span9"><?php echo $user_record["number"]; ?></div>
					</div>
					<div class="row">
						<div class="span3"><strong>Gender:</strong></div>
						<div class="span9"><?php echo display_gender($gender); ?></div>
					</div>
					<div class="row">
						<div class="span3"><strong>E-Mail Address:</strong></div>
						<div class="span9"><a href="mailto:<?php echo $user_record["email"]; ?>"><?php echo $user_record["email"]; ?></a></div>
					</div>
					<br />
					<div class="row">
						<div class="span3"><strong>Organisations:</strong></div>
						<div class="span9">
							<?php
							echo $default_organisation["organisation_title"];

							$organisation_names_diff = array_diff($organisation_names, array($default_organisation["organisation_title"]));
							if (count($organisation_names_diff) > 0) {
								echo "<br />";
								echo implode("<br />", $organisation_names_diff);
							}
							?>
						</div>
					</div>
					<?php if ($department_names) { ?>
					<div class="row">
						<div class="span3"><strong>Departments:</strong></div>
						<div class="span9"><?php echo implode(", ", $department_names) ?></div>
					</div>
					<?php } ?>
				</div> <!--/span10-->
			</div> <!-- /row-fluid-->
			
			<?php
			$query		= "SELECT a.*, CONCAT_WS(', ', b.lastname, b.firstname) as `reported_by` FROM `".AUTH_DATABASE."`.`user_incidents` as a LEFT JOIN `".AUTH_DATABASE."`.`user_data` as b ON `incident_author_id` = `id` WHERE a.`proxy_id` = ".$db->qstr($PROXY_ID)." AND `incident_status` > 0 ORDER BY `incident_date` ASC";
			$results	= $db->GetAll($query);
			if ($results) {
				?>
				<h2 title="Open Incidents Section" style="margin-top: 25px;">Open Incidents</h2>
				<div id="open-incidents-section">
					<table class="tableList" cellspacing="0" summary="List of Open Incidents">
						<colgroup>
							<col class="title" />
							<col class="date" />
							<col class="date" />
						</colgroup>
						<thead>
							<tr>
								<td class="title" style="border-left: 1px #999999 solid">Incident Title</td>
								<td class="date sortedASC" style="border-left: none"><a>Incident Date</a></td>
								<td class="date" style="border-left: none">Follow-up Date</td>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ($results as $result) {
							$url = ENTRADA_URL."/admin/users/manage/incidents?section=edit&id=".$result["proxy_id"]."&incident-id=".$result["incident_id"];
							echo "<tr ".(!$result["incident_status"] ? " class=\"closed\"" : "").">\n";
							echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Incident Title: ".html_encode($result["incident_title"])."\">[".html_encode($result["incident_severity"])."] ".html_encode(limit_chars($result["incident_title"], 75))."</a></td>\n";
							echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Incident Date\">".date(DEFAULT_DATE_FORMAT, $result["incident_date"])."</a></td>\n";
							echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Incident Follow-Up Date\">".(isset($result["follow_up_date"]) && ((int)$result["follow_up_date"]) ? date(DEFAULT_DATE_FORMAT, $result["follow_up_date"]) : "")."</a></td>\n";
							echo "</tr>\n";
						}
						?>
						</tbody>
					</table>
				</div>
				<?php
			} else {
				echo "<div style=\"height: 120px;\">&nbsp;</div>";
			}
		} else {
            add_error("In order to edit a user profile you must provide a valid identifier. The provided ID does not exist in this system.");

			echo display_error();

			application_log("notice", "Failed to provide a valid user identifer when attempting to edit a user profile.");
		}
	} else {
        add_error("In order to edit a user profile you must provide a user identifier.");

		echo display_error();

		application_log("notice", "Failed to provide user identifer when attempting to edit a user profile.");
	}
}