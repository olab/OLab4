<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_PROFILE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->isLoggedInAllowed('profile', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$ajax_action = (isset($_POST["ajax_action"]) ? clean_input($_POST["ajax_action"], "alpha") : "");

	if ($ajax_action == "uploadimageie") {
		$file_data = getimagesize($_FILES["image"]["tmp_name"]);
		$file_dimensions = $file_data[0] . "," . $file_data[1];
		
		$aspect_ratio = $file_data[0] / $file_data[1];
		if ($aspect_ratio >= 0.76) {
			$offset = round(($file_data[0] - ($file_data[0] * .76)) / 2);
			$coordinates = $offset . ",0,".($offset + round($file_data[0] * .76)).",".$file_data[1];
		} else {
			$offset = round(($file_data[1] - ($file_data[1] * .76)) / 2);
			$coordinates =  "0,".$offset.",".$file_data[0].",".($offset + round($file_data[1] * .76));
		}
		
		if ($coordinates) {
			$coords = explode(",", $coordinates);
			foreach($coords as $coord) {
				$tmp_coords[] = clean_input($coord, "int");
			}
			$PROCESSED["coordinates"] = implode(",", $tmp_coords);
		}
		if ($file_dimensions) {
			$dimensions = explode(",", $file_dimensions);
			foreach($dimensions as $dimension) {
				$tmp_dimensions[] = clean_input($dimension, "int");
			}
			$PROCESSED["dimensions"] = implode(",", $tmp_dimensions);
		}		
		$filesize = moveImage($_FILES["image"]["tmp_name"], $ENTRADA_USER->getID(), $PROCESSED["coordinates"], $PROCESSED["dimensions"]);

		if ($filesize) {
			$PROCESSED_PHOTO["proxy_id"]			= $ENTRADA_USER->getID();
			$PROCESSED_PHOTO["photo_active"]		= 1;
			$PROCESSED_PHOTO["photo_type"]			= 1;
			$PROCESSED_PHOTO["updated_date"]		= time();
			$PROCESSED_PHOTO["photo_filesize"]		= $filesize;

			$user_photo_object = new Models_User_Photo();
			$user_photo = Models_User_Photo::get($ENTRADA_USER->getID(), Models_User_Photo::UPLOADED);
			if ($user_photo) {
				if ($user_photo->fromArray($PROCESSED_PHOTO)->update()) {
					add_success("Your profile image has been successfully uploaded.");
				}
			} else {
				if ($user_photo_object->fromArray($PROCESSED_PHOTO)->insert()) {
					add_success("Your profile image has been successfully uploaded.");
				} else {
					add_error("An error ocurred while attempting to update your profile photo record, please try again later.");
				}
			}
		} else {
			add_error("An error ocurred while moving your image in the system, please try again later.");
		}
	}
	
	if (!empty($ajax_action) && $ajax_action != "uploadimageie") {
		
		ob_clear_open_buffers();

		switch ($ajax_action) {
			case "uploadimage" :

				if ($_POST["coordinates"]) {
					$coords = explode(",", $_POST["coordinates"]);
					foreach($coords as $coord) {
						$tmp_coords[] = clean_input($coord, "int");
					}
					$PROCESSED["coordinates"] = implode(",", $tmp_coords);
				}
				if ($_POST["dimensions"]) {
					$dimensions = explode(",", $_POST["dimensions"]);
					foreach($dimensions as $dimension) {
						$tmp_dimensions[] = clean_input($dimension, "int");
					}
					$PROCESSED["dimensions"] = implode(",", $tmp_dimensions);
				}

				$sizes = array("upload" => array("width" => 250, "height" => 250), "upload-thumbnail" => array("width" => 98, "height" => 98));

				$filesize = moveImage($_FILES["image"]["tmp_name"], $ENTRADA_USER->getID(), $PROCESSED["coordinates"], $PROCESSED["dimensions"], "user", $sizes);

				$details = getimagesize($_FILES["image"]["tmp_name"]);

				if ($filesize) {
					$PROCESSED_PHOTO["proxy_id"]			= $ENTRADA_USER->getID();
					$PROCESSED_PHOTO["photo_mimetype"]      = ($details && $details["mime"] ? $details["mime"] : "");
					$PROCESSED_PHOTO["photo_active"]		= 1;
					$PROCESSED_PHOTO["photo_type"]			= 1;
					$PROCESSED_PHOTO["updated_date"]		= time();
					$PROCESSED_PHOTO["photo_filesize"]		= $filesize;

					$user_photo_object = new Models_User_Photo();
					$user_photo = Models_User_Photo::get($ENTRADA_USER->getID(), Models_User_Photo::UPLOADED);

					if ($user_photo) {
						if ($user_photo->fromArray($PROCESSED_PHOTO)->update()) {
							echo json_encode(array("status" => "success", "data" => webservice_url("photo", array($ENTRADA_USER->getID(), "upload"))."/".time()));
						}
					} else {
						if ($user_photo_object->fromArray($PROCESSED_PHOTO)->insert()) {
							echo json_encode(array("status" => "success", "data" => webservice_url("photo", array($ENTRADA_USER->getID(), "upload"))."/".time()));
						} else {
							echo json_encode(array("status" => "error"));
						}
					}
				}
			break;
			case "togglephoto" :
				$user_photo = Models_User_Photo::get($ENTRADA_USER->getID(), Models_User_Photo::UPLOADED);
				if ($user_photo) {
					$photo_active = ($user_photo->isActive() ? "0" : "1");
					if ($user_photo->fromArray(array("photo_active" => $photo_active))->update()) {
						echo json_encode(array("status" => "success", "data" => array("imgurl" => webservice_url("photo", array($ENTRADA_USER->getID(), $photo_active == "1" ? "upload" : "official" ))."/".time(), "imgtype" => $photo_active == "1" ? "uploaded" : "official")));
					} else {
						application_log("error", "An error occurred while attempting to update user photo active flag for user [".$ENTRADA_USER->getID()."]");
						echo json_encode(array("status" => "error"));
					}
				} else {
					echo json_encode(array("status" => "error", "data" => "No uploaded photo record on file. You must upload a photo before you can toggle photos."));
				}
			break;
			case "generatehash" :
				$new_private_hash = generate_hash();
				$result = Models_User_Access::updateHash($new_private_hash, $ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());
				if ($result) {
					echo json_encode(array("status" => "success", "data" => $new_private_hash));
					$_SESSION["details"]["private_hash"] = $new_private_hash;
				} else {
					echo json_encode(array("status" => "error"));
				}
			break;
			case "resetpw" :
				
				if ($_POST["current_password"] && $tmp_input = clean_input($_POST["current_password"], array("trim", "striptags"))) {
					$PROCESSED["current_password"] = $tmp_input;
				}
				
				if ($_POST["new_password"] && $tmp_input = clean_input($_POST["new_password"], array("trim", "striptags"))) {
					$PROCESSED["new_password"] = $tmp_input;
				} else {
					$err[] = "An invalid password was provided.";
				}
				
				if ($_POST["new_password_confirm"] && $tmp_input = clean_input($_POST["new_password_confirm"], array("trim", "striptags"))) {
					$PROCESSED["new_password_confirm"] = $tmp_input;
				} else {
					$err[] = "An invalid password was provided.";
				}
				
				if ($PROCESSED["new_password"] !== $PROCESSED["new_password_confirm"]) {
					$errs[] = "New password dosen't match!";
				}
				
				$user_object = Models_User::getUserByIDAndPass($ENTRADA_USER->getID(),$PROCESSED["current_password"]);
				if ($user_object) {
					$result = $user_object->toArray();
					if (!$errs) {
						$user_password = $PROCESSED["new_password"];
						/**
						 * Check to see if password requires some updating.
						 */
						if (!$result["salt"]) {
							$salt = hash("sha256", (uniqid(rand(), 1) . time() . $result["id"]));
						} else {
							$salt = $result["salt"];
						}

						if ($user_object->fromArray(array("password" => sha1($user_password.$salt), "salt" => $salt))->update()) {
							application_log("auth_success", "Successfully updated password salt for user [".$result["id"]."] via local auth method.");
							echo json_encode(array("status" => "success", "data" => array("Your password has successfully been updated.")));
						} else {
							application_log("auth_error", "Failed to update password salt for user [".$result["id"]."] via local auth method.");
							echo json_encode(array("status" => "error", "data" => array("An error ocurred while attempting to update your password. An administrator has been informed, please try again later.")));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $errs));
					}
				} else {
					echo json_encode(array("status" => "error", "data" => array("The current password did not match the password on file.")));
				}
				
			break;
			default:
			break;
		}

		exit;

	}

	$PAGE_META["title"]			= "My ".APPLICATION_NAME." Profile";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$PROXY_ID					= $ENTRADA_USER->getID();
	$VALID_MIME_TYPES			= array("image/pjpeg" => "jpg", "image/jpeg" => "jpg", "image/jpg" => "jpg", "image/gif" => "gif", "image/png" => "png");
	$VALID_MAX_FILESIZE			= 2097512; // 2MB
	$VALID_MAX_DIMENSIONS		= array("photo-width" => 216, "photo-height" => 300, "thumb-width" => 75, "thumb-height" => 104);
	$RENDER						= false;

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/profile", "title" => "Personal Information");

	$PROCESSED		= array();

	if (isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && (count($_SESSION["permissions"]) > 1)) {
		$sidebar_html  = "<form id=\"masquerade-form\" action=\"".ENTRADA_URL."\" method=\"get\">\n";
		$sidebar_html .= "<label for=\"permission-mask\">Available permission masks:</label><br />";
		$sidebar_html .= "<select id=\"permission-mask\" name=\"mask\" style=\"width: 100%\" onchange=\"window.location='".ENTRADA_URL."/".$MODULE."/?".str_replace("&#039;", "'", replace_query(array("mask" => "'+this.options[this.selectedIndex].value")))."\">\n";
		$display_masks = true;
		$added_users = array();
		foreach ($_SESSION["permissions"] as $access_id => $result) {
			if ($result["organisation_id"] == $ENTRADA_USER->getActiveOrganisation() && is_int($access_id) && ((isset($result["mask"]) && $result["mask"]) || $access_id == $ENTRADA_USER->getDefaultAccessId() || ($result["id"] == $ENTRADA_USER->getID() && $ENTRADA_USER->getDefaultAccessId() != $access_id)) && array_search($result["id"], $added_users) === false) {
				if (isset($result["mask"]) && $result["mask"]) {
					$display_masks = true;
				}
				$added_users[] = $result["id"];
				$sidebar_html .= "<option value=\"".(($access_id == $ENTRADA_USER->getDefaultAccessId()) || !isset($result["permission_id"]) ? "close" : $result["permission_id"])."\"".(($result["id"] == $ENTRADA_USER->getActiveId()) ? " selected=\"selected\"" : "").">".html_encode($result["fullname"]) . "</option>\n";
			}
		}
		$sidebar_html .= "</select>\n";
		$sidebar_html .= "</form>\n";
		if ($display_masks) {
			new_sidebar_item("Permission Masks", $sidebar_html, "permission-masks", "open");
		}
	}

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/profile.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";

	if (file_exists(STORAGE_USER_PHOTOS."/".$PROXY_ID."-upload")) {
		$size_upload = getimagesize(STORAGE_USER_PHOTOS."/".$PROXY_ID."-upload");
	}
	if (file_exists(STORAGE_USER_PHOTOS."/".$PROXY_ID."-official")) {
		$size_official = getimagesize(STORAGE_USER_PHOTOS."/".$PROXY_ID."-official");
	}

	if ($ERROR) {
		fade_element("out", "display-error-box");
		echo display_error();
	}

	if ($SUCCESS) {
		fade_element("out", "display-success-box");
		echo display_success();
	}

	if ($NOTICE) {
		fade_element("out", "display-notice-box");
		echo display_notice();
	}

//	$ONLOAD[] = "provStateFunction(\$F($('profile-update')['country_id']))";

	$user	= Models_User::fetchRowByID($ENTRADA_USER->getID());
	if ($user && $user_data = $user->toArray()) {
		/*
		 * Get the user departments and the custom fields for the departments.
		 */
		$user_departments = get_user_departments($ENTRADA_USER->getID());
		foreach ($user_departments as $department) {
			$departments[$department["department_id"]] = $department["department_title"];
		}

		$custom_fields = fetch_department_fields($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());

		if ((isset($PROCESSED["province"]) && $PROCESSED["province"]) || (isset($PROCESSED["province_id"]) && $PROCESSED["province_id"])) {
			$source_arr = $PROCESSED;
		} else {
			$source_arr = $user_data;
		}
		$province = $source_arr["province"];
		$province_id = $source_arr["province_id"];
		$prov_state = ($province) ? $province : $province_id;
		$HEAD[] = "<script>var PROV_STATE = \"". $prov_state ."\";</script>";
		$HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";

			?>
		<h1>My <?php echo APPLICATION_NAME; ?> Profile</h1>
		<div id="msgs"></div>
		This section allows you to update your <?php echo APPLICATION_NAME; ?> user profile information. Please note that this information does not necessarily reflect any information stored at the main University. <span style="background-color: #FFFFCC; padding-left: 5px; padding-right: 5px">This is not your official institutional contact information.</span>
		<br /><br />
		<div id="profile-wrapper">
		<script src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery.imgareaselect.min.js" type="text/javascript"></script>
		<link href='<?php echo ENTRADA_URL; ?>/css/imgareaselect-default.css' rel='stylesheet' type='text/css' />
		<?php
        $profile_image = ENTRADA_ABSOLUTE . '/../public/images/' . $ENTRADA_USER->getID() . '/' . $ENTRADA_USER->getID() . '-large.png';
        ?>
		<div id="profile-image-container" class="pull-right">
			<a href="#upload-image" id="upload-image-modal-btn" data-toggle="modal" class="btn btn-primary" id="upload-profile-image">Upload Photo</a>
			<?php

			$photo_object = Models_User_Photo::get($user_data["id"], Models_User_Photo::UPLOADED);

			if ($photo_object) {
				$uploaded_photo = $photo_object->toArray();
			}
			?>
			<span>
                <img src="<?php echo webservice_url("photo", array($ENTRADA_USER->getID(), $uploaded_photo ? "upload" : "official"))."/".time(); ?>" class="img-polaroid" />
            </span>
			<div class="btn-group" id="btn-toggle" class="<?php echo $uploaded_photo ? "uploaded" : "official"; ?>">
				<a href="#" class="btn btn-small <?php echo $uploaded_photo["photo_active"] == "0" ? "active" : ""; ?>" id="image-nav-left">Official</a>
				<?php
                if ($uploaded_photo) {
                    ?>
                    <a href="#" class="btn btn-small <?php echo $uploaded_photo["photo_active"] == "1" ? "active" : ""; ?>" id="image-nav-right">Uploaded</a>
                    <?php
                }
                ?>
			</div>
		</div>

		<form class="form-horizontal" name="profile-update" id="profile-update" action="<?php echo ENTRADA_RELATIVE; ?>/profile" method="post" enctype="multipart/form-data" accept="<?php echo ((@is_array($VALID_MIME_TYPES)) ? implode(",", array_keys($VALID_MIME_TYPES)) : ""); ?>">
		    <input type="hidden" name="action" value="profile-update" />

            <fieldset>
                <legend>Personal Information</legend>

                <div class="control-group">
                    <?php
                    /*
                     * Only if there are prefixes defined in settings.
                     */
                    if (isset($PROFILE_NAME_PREFIX) && is_array($PROFILE_NAME_PREFIX) && !empty($PROFILE_NAME_PREFIX)) {
                        /*
                         * Only if the user is not a student, or is a student and already has a prefix set.
                         */
                        if (($ENTRADA_USER->getGroup() != "student") || trim($user_data["prefix"])) {
                            /**
                             * @todo Don't forget to set the $ORIGINAL_PREFIX_VALUE somewhere.
                             */
                            ?>
                            <label class="control-label" style="padding-top:0">
                                <select class="input-small" id="prefix" name="prefix">
                                    <option value=""<?php echo ((!$user_data["prefix"]) ? " selected=\"selected\"" : ""); ?>></option>
                                    <?php
                                    foreach ($PROFILE_NAME_PREFIX as $key => $prefix) {
                                        echo "<option value=\"".html_encode($prefix)."\"".(($user_data["prefix"] == $prefix) ? " selected=\"selected\"" : "").">".html_encode($prefix)."</option>\n";
                                    }
                                    ?>
                                </select>
                            </label>
                            <?php
                        }
                    } else {
                        ?>
                        <input type="hidden" name="prefix" value="" />
                        <?php
                    }
                    ?>
                    <div class="controls">
                        <span class="input-large lead"><?php echo html_encode($user_data["firstname"]." ".$user_data["lastname"]); ?></span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">Primary E-mail:</label>
                    <div class="controls">
                        <?php
                        if($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] == "faculty") {
                            ?>
                            <input type="text" class="input-large" id="email" name="email" value="<?php echo html_encode($user_data["email"]); ?>" style="width: 250px; vertical-align: middle" maxlength="128" />
                            <?php
                        } else {
                            ?>
                            <span class="input-large uneditable-input"><?php echo html_encode($user_data["email"]); ?></span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="email_alt">Secondary E-mail:</label>
                    <div class="controls">
                        <input class="input-large" name="email_alt" id="email_alt" type="text" placeholder="example@email.com" value="<?php echo html_encode($user_data["email_alt"]); ?>" />
                    </div>
                </div>
                <?php
                if ((bool) $GOOGLE_APPS["active"] && $user_data["google_id"]) {
                    ?>
                    <div class="control-group">
                        <label class="control-label">Google Account:<br /><a href="http://webmail.<?php echo $GOOGLE_APPS["domain"]; ?>" target="_blank">visit <?php echo html_encode($GOOGLE_APPS["domain"]); ?> webmail</a></label>
                        <div class="span5" id="google-account-details">
                            <?php
                            if (($user_data["google_id"] == "") || ($user_data["google_id"] == "opt-out") || ($user_data["google_id"] == "opt-in") || ($_SESSION["details"]["google_id"] == "opt-in")) {
                                ?>
                                Your <?php echo $GOOGLE_APPS["domain"]; ?> account is <strong>not active</strong>. <br /> ( <a href="javascript: create_google_account()" class="action">create my account</a> )
                                <script type="text/javascript">
                                function create_google_account() {
                                    $('google-account-details').update('<img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif\" width=\"16\" height=\"16\" alt=\"Please wait\" border=\"0\" style=\"margin-right: 2px; vertical-align: middle\" /> <span class=\"content-small\">Please wait while your account is created ...</span>');
                                    new Ajax.Updater('google-account-details', '<?php echo ENTRADA_URL; ?>/profile', { method: 'post', parameters: { 'action' : 'google-update', 'google_account' : 1, 'ajax' : 1 }});
                                }
                                </script>
                                <?php
                            } else {
                                $google_address = html_encode($user_data["google_id"]."@".$GOOGLE_APPS["domain"]);
                                ?>
                                <span class="input-large uneditable-input"><?php echo $google_address; ?></span>
                                <?php
                                if ($google_address) {
                                    ?>
                                    <div style="margin-top: 10px">
                                        <a href="#reset-google-password-box" id="reset-google-password" class="btn" data-toggle="modal">Change My <strong><?php echo ucwords($GOOGLE_APPS["domain"]); ?></strong> Password</a>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="control-group"></div>
                <div class="control-group">
                    <label class="control-label" for="telephone">Telephone Number:</label>
                    <div class="controls">
                        <input class="input-large" name="telephone" id="telephone" type="text" placeholder="Example: 613-533-6000 x74918" value="<?php echo html_encode($user_data["telephone"]); ?>" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="fax">Fax Number:</label>
                    <div class="controls">
                        <input class="input-large" name="fax" id="fax" type="text" placeholder="Example: 613-533-3204" value="<?php echo html_encode($user_data["fax"]); ?>" />
                    </div>
                </div>
                <div class="control-group"></div>
                <div class="control-group">
                    <label class="control-label" for="country_id">Country:</label>
                    <div class="controls">
                        <?php
                            $countries = fetch_countries();
                            if ((is_array($countries)) && (count($countries))) {

                                $country_id = (isset($PROCESSED["country_id"])) ? $PROCESSED["country_id"] : $user_data["country_id"];

                                echo "<select id=\"country_id\" name=\"country_id\" class=\"input-large\" >\n";
                                echo "<option value=\"0\"".(empty($country_id) ? " selected=\"selected\"" : "").">-- Select Country --</option>\n";
                                foreach ($countries as $country) {
                                    echo "<option value=\"".(int) $country["countries_id"]."\"".(($country_id == $country["countries_id"]) ? " selected=\"selected\"" : "").">".html_encode($country["country"])."</option>\n";
                                }
                                echo "</select>\n";
                            } else {
                                echo "<input type=\"hidden\" id=\"country_id\" name=\"country_id\" value=\"0\" />\n";
                                echo "Country information not currently available.\n";
                            }
                        ?>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="prov_state">Province:</label>
                    <div class="controls">
                        <div id="prov_state_div" class="padding5v">Please select a <strong>Country</strong> from above first.</div>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="city">City:</label>
                    <div class="controls">
                        <input class="input-large" name="city" id="city" type="text" value="<?php echo html_encode($user_data["city"]); ?>" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="address">Address:</label>
                    <div class="controls">
                        <input class="input-large" name="address" id="address" type="text" value="<?php echo html_encode($user_data["address"]); ?>" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="postcode">Postal Code:</label>
                    <div class="controls">
                        <input class="input-large" name="postcode" id="postcode" type="text" placeholder="Example: K7L 3N6" value="<?php echo html_encode($user_data["postcode"]); ?>" />
                    </div>
                </div>
                <?php
                if ($ENTRADA_USER->getGroup() != "student") {
                    ?>
                    <div class="control-group">
                        <label class="control-label" for="hours">Office Hours:</label>
                        <div class="controls">
                            <textarea id="office_hours" name="office_hours" class="expandable input-large" maxlength="100"><?php echo html_encode($user_data["office_hours"]); ?></textarea>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </fieldset>
            <fieldset>
                <legend><?php echo APPLICATION_NAME; ?> Account Information</legend>
                <div class="control-group">
                    <label class="control-label">Username:</label>
                    <div class="controls">
                        <span class="input-large uneditable-input"><?php echo html_encode($_SESSION["details"]["username"]); ?></span>
                    </div>
                </div>
                <?php
                if (empty($_SESSION["auth"]["method"]) || $_SESSION["auth"]["method"] == "local") {
                ?>
                <div class="control-group">
                    <label class="control-label">Password:</label>
                    <div class="controls">
                        <a class="btn" href="#password-change-modal" data-toggle="modal">Change My Password</a>
                    </div>
                </div>
                <?php
                }
                ?>
                <div class="control-group">
                    <label class="control-label">Private Hash:</label>
                    <div class="controls">
                        <div class="input-append">
                            <span class="input-large uneditable-input" id="hash-value"><?php echo $_SESSION["details"]["private_hash"]; ?></span>
                            <a class="add-on" href="#reset-hash-modal" data-toggle="modal"><i class="icon-repeat"></i></a>
                        </div>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">Last Login:</label>
                    <div class="controls">
                        <span class="input-large uneditable-input"><?php echo ((!$_SESSION["details"]["lastlogin"]) ? "Your first login" : date(DEFAULT_DATE_FORMAT, $_SESSION["details"]["lastlogin"])); ?></span>
                    </div>
                </div>
            </fieldset>
			<?php
			load_rte();
			if ($custom_fields) {
				echo "<h2>Department Specific Information</h2>";
				add_notice("The information below has been requested by departments the user is a member of. This information is considered public and may be published on department websites.");
				echo display_notice();
				echo "<div class=\"tabbable departments\">";
				echo "<ul class=\"nav nav-tabs\">";
				$i = 0;
				if (isset($departments)) {
					foreach ($departments as $department_id => $department) {
						if (count($custom_fields[$department_id]) >= 1) {
							?>
							<li class="<?php echo $i == 0 ? "active" : ""; ?>"><a data-toggle="tab" href="#dep-<?php echo $department_id; ?>"><?php echo strlen($department) > 15 ? substr($department, 0, 15)."..." : $department; ?></a></li>
							<?php
							$i++;
						}
					}
				}
				echo "</ul>";

				echo "<div class=\"tab-content\">";
				$i = 0;
				foreach ($departments as $department_id => $department) {
					if (count($custom_fields[$department_id]) >= 1) {
						echo "<div class=\"tab-pane ".($i == 0 ? "active" : "")."\" id=\"dep-".$department_id."\">";
						echo "<h4>".$department."</h4>";
						foreach ($custom_fields[$department_id] as $field) { ?>
							<div class="control-group">
								<label class="control-label <?php echo $field["required"] == "1" ? " form-required" : ""; ?>" for="<?php echo $field["name"]; ?>"><?php echo $field["title"]; ?></label>
								<div class="controls">
									<?php
										$field["type"] = strtolower($field["type"]);
										switch ($field["type"]) {
											case "textarea" :
												?>
												<textarea id="<?php echo $field["name"]; ?>" class="input-large expandable expanded" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" maxlength="<?php echo $field["length"]; ?>"><?php echo $field["value"]; ?></textarea>
												<?php
											break;
											case "textinput" :
											case "twitter" :
											case "link" :
												?>
												<input type="text" id="<?php echo $field["name"]; ?>" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" maxlength="<?php echo $field["length"]; ?>" value="<?php echo $field["value"]; ?>" />
												<?php
											break;
											case "richtext" :
												?>
												<textarea id="<?php echo $field["name"]; ?>" class="input-large" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" maxlength="<?php echo $field["length"]; ?>"><?php echo $field["value"]; ?></textarea>
												<?php
											break;
											case "checkbox" :
												?>
												<label class="checkbox"><input type="checkbox" id="<?php echo $field["name"]; ?>" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" value="<?php echo $field["value"]; ?>" <?php echo $field["value"] == "1" ? " checked=\"checked\"" : ""; ?> />
												<?php echo $field["helptext"] ? $field["helptext"] : ""; ?></label>
												<?php
											break;
										}
									?>

								</div>
							</div>
						<?php }

						echo "</div>";
						$i++;
					}
				}
				echo "</div>";
				echo "</div>";
			}
			?>
			<div>
				<div class="pull-right">
					<input type="submit" class="btn btn-primary btn-large" value="Save Profile" />
				</div>
			</div>
		</form>
		</div>
		<div id="upload-image" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
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
		<div class="modal hide fade" id="reset-hash-modal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3>Private Hash Reset</h3>
			</div>
			<div class="modal-body">
				<div class="alert alert-info">
					<strong>Please Note:</strong> You are about to reset your <?php echo APPLICATION_NAME; ?> account private hash. If you have active links to your <?php echo APPLICATION_NAME; ?> calendar or podcasting feeds from an external application such as Google Calendar or iTunes, don't forget to update those links accordingly.
				</div>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn pull-left" data-dismiss="modal">Cancel</a>
				<a href="#" class="btn btn-primary" id="reset-hash">Reset Hash</a>
			</div>
		</div>
        <?php
        if (empty($_SESSION["auth"]["method"]) || $_SESSION["auth"]["method"] == "local") {
        ?>
		<div class="modal hide fade" id="password-change-modal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3>Update Password</h3>
			</div>
			<div class="modal-body">
				<div id="pw-change-msg"></div>
				<form action="" method="POST" class="form-horizontal" id="update-pw-form">
					<div class="control-group">
						<label for="current_password" class="control-label">Current Password:</label>
						<div class="controls">
							<input type="password" name="current_password" id="current_password" placeholder="Please enter your current password." />
						</div>
					</div>
					<div class="control-group">
						<label for="new_password" class="control-label">New Password:</label>
						<div class="controls">
							<input type="password" name="new_password" id="new_password" placeholder="Please enter your new password." />
						</div>
					</div>
					<div class="control-group">
						<label for="new_password_confirm" class="control-label">Confirm Password:</label>
						<div class="controls">
							<input type="password" name="new_password_confirm" id="new_password_confirm" placeholder="Please repeat your new password." />
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn pull-left" data-dismiss="modal">Cancel</a>
				<a href="#" class="btn btn-primary" id="update-pw">Update</a>
			</div>
		</div>
        <?php
        }
        ?>
		<?php
		if (((bool) $GOOGLE_APPS["active"]) && $user_data["google_id"]) {
			?>
			<div id="reset-google-password-box" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-header">
					<h1>Reset <strong><?php echo ucwords($GOOGLE_APPS["domain"]); ?></strong> Password</h1>
				</div>
				<div class="modal-body">
					<div id="reset-google-password-form">
						<div id="reset-google-password-form-status">To reset your <?php echo ucwords($GOOGLE_APPS["domain"]); ?> account password at Google, please enter your new password below and click the <strong>Submit</strong> button.</div>
						<form action="#" method="post">
							<table style="width: 100%; margin-top: 15px" cellspacing="2" cellpadding="0">
								<colgroup>
									<col style="width: 35%" />
									<col style="width: 65%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label for="google_password_1" class="form-required">New Password</label></td>
										<td><input type="password" id="google_password_1" name="password1" value="" style="width: 175px" maxlength="24" /></td>
									</tr>
									<tr>
										<td><label for="google_password_2" class="form-required">Re-Enter Password</label></td>
										<td><input type="password" id="google_password_2" name="password2" value="" style="width: 175px" maxlength="24" /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div id="reset-google-password-waiting" class="display-generic" style="display: none">
						<img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please wait" border="0" style="margin-right: 2px; vertical-align: middle" /> <span class="content-small">Please wait while your password is being changed ...</span>
					</div>
					<div id="reset-google-password-success" class="display-success" style="display: none">
						We have successfully reset your <?php echo $GOOGLE_APPS["domain"]; ?> account password at Google.<br /><br />If you would like to log into your webmail account, please do so via <a href="http://webmail.qmed.ca" target="_blank">http://webmail.qmed.ca</a>.
					</div>
				</div>
				<div class="modal-footer">
					<button id="reset-google-password-close" class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
					<button id="reset-google-password-submit" class="btn btn-primary">Submit</button>
				</div>
			</div>
			<script type="text/javascript" defer="defer">

			</script>
			<?php
        }
	} else {
		add_notice("Unfortunately your ".APPLICATION_NAME." profile is not accessible at this time, please try again later.");

		echo display_notice();

		application_log("error", "A user profile was not available in the database");
	}
}