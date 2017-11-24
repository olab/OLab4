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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("PUBLIC_OBSERVERSHIP_FORM"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}


function makeSelection($val1,$val2){
	echo $val1==$val2?' selected="selected"':'';
}

function makeChecked($val1,$val2){
	echo $val1==$val2?' checked="checked"':'';
}

?>
<h1>
	<?php echo $ACTION; ?> Observership
</h1>


<?php 
	if ($ERROR) {
		echo display_error();
	}

	if ($NOTICE) {
		echo display_notice();		
	}

	if ($SUCCESS) {
		echo display_success();		
	}
$HEAD[] = "<script type=\"text/javascript\"> var SITE_URL = '".ENTRADA_URL."';</script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/users.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[]	= "<script type=\"text/javascript\">
			function provStateFunction(countries_id) {	
				var url='".webservice_url("clerkship_prov")."';
				url=url+'?countries_id='+countries_id+'&prov_state=".rawurlencode($OBSERVERSHIP->getProv() ? clean_input($OBSERVERSHIP->getProv(), array("notags", "trim")) : $PROCESSED["prov_state"])."';
				new Ajax.Updater($('prov_state_div'), url, 
					{ 
						method:'get',
						onComplete: function () {
							generateAutocomplete();
							if ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0) {
								$('prov_state_label').removeClassName('form-nrequired');
								$('prov_state_label').addClassName('form-required');
							} else {
								$('prov_state_label').removeClassName('form-required');
								$('prov_state_label').addClassName('form-nrequired');
							}
						}
					});
			}
			</script>\n";
$ONLOAD[]		= "provStateFunction(\$F($('observership_form')['countries_id']))";
if (!$OBSERVERSHIP){
	$OBSERVERSHIP = new Observership();
}
?>

	<script type="text/javascript">
		jQuery(document).ready(function(){		
			jQuery('#activity_type').change(function(){
				var type = jQuery(this).val();
				if (type == 'ipobservership') {
					jQuery('#observership_details').slideDown();
				} else {
					jQuery('#observership_details').slideUp();
				}
			});

			jQuery('#observership_details').hide();
			jQuery('select').each(function(){
				jQuery(this).val(jQuery(this).attr('data-init'));
				jQuery(this).trigger('change');
			});

			jQuery("#countries_id").live("change", function() {
				jQuery("#country").attr("value", jQuery(this).children("option[value="+jQuery(this).val()+"]").html());
			});
			jQuery("#prov_state").live("change", function() {
				jQuery("#prov").attr("value", jQuery(this).val());
			});
			
			jQuery("#preceptor_email").live("blur", function() {
				jQuery.ajax({
					url: "<?php echo ENTRADA_URL; ?>/api/personnel.api.php",
					data: "email=" + jQuery(this).val() + "",
					type: "POST",
					success: function(data) {
						if (data) {
							var jsonResponse = JSON.parse(data);
							if (jsonResponse.status == "success") {
								jQuery("#preceptor_proxy_id").attr("value", jsonResponse.data.proxy_id);
								jQuery("#preceptor_firstname").attr("value", jsonResponse.data.firstname);
								jQuery("#preceptor_lastname").attr("value", jsonResponse.data.lastname);
							}
						}
					}
				});
			});
		});
	</script>
	<style>
	#prov_state {width:170px!important;}
	
	form > table > tbody td input,form > table td select{
		width:100%;
	}
	#preceptor_director_name,#preceptor_director_list{
		margin-bottom:15px;
	}
	#preceptor_director_list > li{
		width:250px;
		position:absolute;
	}
	.user_add_btn{
		margin-left:10px;
		vertical-align: top!important;
	}
	#observership_form td {padding:4px 0px;}
	</style>
	<form action="<?php echo ENTRADA_URL; ?>/profile/observerships?<?php echo replace_query(array("step" => 2)); ?>" method="post" id="observership_form">
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Course Details">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 22%" />
				<col style="width: 75%" />
			</colgroup>
			<tbody>
				<tr>
					<td>&nbsp;</td>
					<td><label for="activity_type" class="form-required">Activity Type:</label></td>
					<td>
						<select id="activity_type" name="activity_type" data-init="<?php echo $OBSERVERSHIP->getActivityType();?>">
							<option value="observership">Observership</option>
							<option value="ipobservership">IP Observership</option>
						</select>
					</td>
				</tr>
				<tr id="observership_details">
					<td>&nbsp;</td>
					<td style="vertical-align:top;"><label for="activity_type" class="form-required">IP Observership Details:</label></td>
					<td>
						<textarea class="expandable" name="observership_details"><?php echo $OBSERVERSHIP->getObservershipDetails();?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><label for="clinical_discipline" class="form-required">Eligible Clinical Disciplines:</label></td>
					<td>
						<select name="clinical_discipline" id="clinical_discipline" data-init="<?php echo $OBSERVERSHIP->getClinicalDiscipline();?>">
						  <option value="">-- Select discipline --</option>
						  <option value="Adolescent Medicine">Adolescent Medicine</option>
						  <option value="Aerospace Medicine">Aerospace Medicine</option>
						  <option value="Allergy and Immunology">Allergy and Immunology</option>
						  <option value="Anatomical Pathology">Anatomical Pathology</option><option value="Anesthesiology">Anesthesiology</option>
						  <option value="Cardiac Surgery">Cardiac Surgery</option>
						  <option value="Cardiology">Cardiology</option>
						  <option value="Child &amp; Adolescent Psychiatry">Child &amp; Adolescent Psychiatry</option>
						  <option value="Clinical Immunology and Allergy">Clinical Immunology and Allergy</option>
						  <option value="Clinical Pharmacology">Clinical Pharmacology</option>
						  <option value="Colorectal Surgery">Colorectal Surgery</option>
						  <option value="Community Medicine">Community Medicine</option>
						  <option value="Critical Care Medicine">Critical Care Medicine</option>
						  <option value="Dermatology">Dermatology</option>
						  <option value="Developmental Pediatrics">Developmental Pediatrics</option>
						  <option value="Diagnostic Radiology">Diagnostic Radiology</option>
						  <option value="Emergency Medicine">Emergency Medicine</option>
						  <option value="Endocrinology and Metabolism">Endocrinology and Metabolism</option>
						  <option value="Family Medicine">Family Medicine</option>
						  <option value="Forensic Pathology">Forensic Pathology</option>
						  <option value="Forensic Psychiatry">Forensic Psychiatry</option>
						  <option value="Gastroenterology">Gastroenterology</option>
						  <option value="General Pathology">General Pathology</option>
						  <option value="General Pediatrics">General Pediatrics</option>
						  <option value="General Surgery">General Surgery</option>
						  <option value="General Surgical Oncology">General Surgical Oncology</option>
						  <option value="Geriatric Medicine">Geriatric Medicine</option>
						  <option value="Geriatric  Psychiatry">Geriatric  Psychiatry</option>
						  <option value="Gynecologic Oncology">Gynecologic Oncology</option>
						  <option value="Gynecologic Reproductive Endocrinology and Fertility">Gynecologic Reproductive Endocrinology and Fertility</option>
						  <option value="Hematological Pathology">Hematological Pathology</option>
						  <option value="Hematology">Hematology</option>
						  <option value="Infectious Disease">Infectious Disease</option>
						  <option value="Internal Medicine">Internal Medicine</option>
				          <option value="IP Observership">IP Observership</option>
						  <option value="Maternal-Fetal Medicine">Maternal-Fetal Medicine</option>
						  <option value="Medical Biochemistry">Medical Biochemistry</option>
						  <option value="Medical Genetics">Medical Genetics</option>
						  <option value="Medical Microbiology">Medical Microbiology</option>
						  <option value="Medical Oncology">Medical Oncology</option>
						  <option value="Neonatal-Perinatal Medicine">Neonatal-Perinatal Medicine</option>
						  <option value="Nephrology">Nephrology</option>
						  <option value="Neurology">Neurology</option>
						  <option value="Neuropathology">Neuropathology</option>
						  <option value="Neurosurgery">Neurosurgery</option>
						  <option value="Nuclear Medicine">Nuclear Medicine</option>
						  <option value="Obstetrics &amp; Gynecology">Obstetrics &amp; Gynecology</option>
						  <option value="Occupational Medicine">Occupational Medicine</option>
						  <option value="Oncology">Oncology</option>
						  <option value="Ophthalmology">Ophthalmology</option>
						  <option value="Orthopedic Surgery">Orthopedic Surgery</option>
						  <option value="Otolaryngology-Head and Neck Surgery">Otolaryngology-Head and Neck Surgery</option>
						  <option value="Other">Other</option>
						  <option value="Palliative Medicine">Palliative Medicine</option>
						  <option value="Pediatric Cardiology">Pediatric Cardiology</option>
						  <option value="Pediatric Emergency Medicine">Pediatric Emergency Medicine</option>
						  <option value="Pediatric Endocrinology">Pediatric Endocrinology</option>
                          <option value="Pediatric Gastroenterology">Pediatric Gastroenterology</option>
						  <option value="Pediatric General Surgery">Pediatric General Surgery</option>
						  <option value="Pediatric Hemotology/Oncology">Pediatric Hemotology/Oncology</option>
						  <option value="Pediatric Neurology">Pediatric Neurology</option>
						  <option value="Pediatric Ophthalmology">Pediatric Ophthalmology</option>
						  <option value="Pediatric Radiology">Pediatric Radiology</option>
						  <option value="Pediatric Respirology">Pediatric Respirology</option>
						  <option value="Physical Medicine and Rehabilitation">Physical Medicine and Rehabilitation</option>
						  <option value="Plastic Surgery">Plastic Surgery</option>
						  <option value="Psychiatry">Psychiatry</option>
						  <option value="Radiation Oncology">Radiation Oncology</option>
						  <option value="Respirology">Respirology</option>
						  <option value="Rheumatology">Rheumatology</option>
						  <option value="Thoracic Surgery">Thoracic Surgery</option>
						  <option value="Transfusion Medicine">Transfusion Medicine</option>
						  <option value="Urology">Urology</option>
					     </select>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><label for="organisation" class="form-required">Organisation:</label></td>
					<td>
						<select name="organisation" id="organisation" data-init="<?php echo $OBSERVERSHIP->getOrganisation();?>">
					      	<option value="">-- Select Organisation --</option>
					      	<option value="Kingston General Hospital"<?php makeSelection($OBSERVERSHIP->getActivityType(),"Kingston General Hospital");?>>Kingston General Hospital</option>
					      	<option value="Hotel Dieu Hospital"<?php makeSelection($OBSERVERSHIP->getActivityType(),"Hotel Dieu Hospital");?>>Hotel Dieu Hospital</option>
					      	<option value="Providence Continuing Care Centre"<?php makeSelection($OBSERVERSHIP->getActivityType(),"Providence Continuing Care Centre");?>>Providence Continuing Care Centre</option>
					      	<option value="St. Mary's of the Lake"<?php makeSelection($OBSERVERSHIP->getActivityType(),"St. Mary's of the Lake");?>>St. Mary's of the Lake</option>
					      	<option value="Family Medicine"<?php makeSelection($OBSERVERSHIP->getActivityType(),"Family Medicine");?>>Family Medicine</option>
					      	<option value="Other"<?php makeSelection($OBSERVERSHIP->getActivityType(),"Other");?>>Other</option>
					 	</select>
					</td>
				</tr>
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
			</tbody>
		</table>
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 23%" />
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 23%" />
			</colgroup>
			<tbody>
		       	<tr>
		       		<td>&nbsp;</td>
				    <td><label for="address_l1" class="form-required">Address Line 1:</label></td>
				    <td><input id="address_l1" name="address_l1" value="<?php echo $OBSERVERSHIP->getAddressLine1();?>"></td>
					<td>&nbsp;</td>
				   	<td><label for="phone">Phone:</label></td>
				    <td><input id="phone" name="phone" value="<?php echo $OBSERVERSHIP->getPhone();?>"></td>
		       	</tr>
		       	<tr>
		       		<td>&nbsp;</td>
				    <td><label for="address_l2" class="form-nrequired">Address Line 2:</label></td>
				    <td><input id="address_l2" name="address_l2" value="<?php echo $OBSERVERSHIP->getAddressLine2();?>"></td>
					<td>&nbsp;</td>
				    <td><label for="fax">Fax:</label></td>
				    <td><input id="fax" name="fax" value="<?php echo $OBSERVERSHIP->getFax();?>"></td>
		       	</tr>
		       	<tr>
					<td>&nbsp;</td>
					<td><label for="countries_id" class="form-required">Country:</label></td>
					<td>
						<?php
							if (@count($countries = fetch_countries()) > 0) {
								$country_options = "";
								foreach ($countries as $value) {
									$country_options .= "<option value=\"".(int) $value["countries_id"]."\"".($OBSERVERSHIP->getCountry() == $value["country"] ? " selected=\"true\"" : ($value["countries_id"] == DEFAULT_COUNTRY_ID ? " selected=\"true\"" : "")).">".html_encode($value["country"])."</option>\n";
									if ($OBSERVERSHIP->getCountry() == $value["country"]) {
										$data_init = $value["countries_id"];
									}
								}
								if (empty($data_init)) {
									$data_init = DEFAULT_COUNTRY_ID;
								}
								
								echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 90%\" onchange=\"provStateFunction(this.value);\" data-init=\"".$data_init."\">\n";
//								echo "<option value=\"0\"".((!$OBSERVERSHIP->getCountry()) ? " selected=\"selected\"" : "").">-- Country --</option>\n";
								echo $country_options;
								echo "</select>\n";
							} else {
								echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
								echo "Country Information Not Available\n";
							}
						?>
						<input id="country" name="country" type="hidden" value="<?php echo $OBSERVERSHIP->getCountry() ? $OBSERVERSHIP->getCountry() : "Canada";?>">
					</td>
					<td>&nbsp;</td>
				    <td><label for="city" class="form-required">City:</label></td>
				    <td><input id="city" name="city" value="<?php echo $OBSERVERSHIP->getCity();?>"></td>
		       	</tr>
		       	<tr>
		       		<td>&nbsp;</td>
					<td><label id="prov_state_label" for="prov_state_div" class="form-required">Prov / State:</label></td>
					<td>
						<div id="prov_state_div" style="display: inline">Select a Country above</div>
						<input type="hidden" name="prov" id="prov" value="<?php echo $OBSERVERSHIP->getProv(); ?>" />
					</td>
					<td>&nbsp;</td>
				    <td><label for="postal_code">Postal Code:</label></td>
				    <td><input id="postal_code" name="postal_code" value="<?php echo $OBSERVERSHIP->getPostalCode();?>"></td>
		       	</tr>
				<tr>
					<td colspan="6">&nbsp;</td>
				</tr>
			</tbody>
		</table>
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 22%" />
				<col style="width: 75%" />
			</colgroup>
			<tbody>
		       	<tr class="preceptor_manual">
		       		<td>&nbsp;</td>
				    <td><label for="preceptor_email" class="form-required">Preceptor Email:</label></td>
				    <td>
						<input id="preceptor_proxy_id" name="preceptor_proxy_id" type="hidden" value="" />
						<input id="preceptor_email" name="preceptor_email" value="<?php echo $OBSERVERSHIP->getPreceptorEmail();?>" style="width:50%;">
					</td>
		       	</tr>
				<tr class="preceptor_manual">
		       		<td>&nbsp;</td>
				    <td><label for="supervisor" class="form-required">Preceptor Firstname:</label></td>
				    <td><input id="preceptor_firstname" name="preceptor_firstname" value="<?php echo $OBSERVERSHIP->getPreceptorFirstname();?>" style="width:50%;"></td>
		       	</tr>
				<tr class="preceptor_manual">
		       		<td>&nbsp;</td>
				    <td><label for="supervisor" class="form-required">Preceptor Lastname:</label></td>
				    <td><input id="preceptor_lastname"  name="preceptor_lastname" value="<?php echo $OBSERVERSHIP->getPreceptorLastname();?>" style="width:50%;"></td>
		       	</tr>		       	
		       	<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
			<?php 
				echo generate_calendars("observership", "", true, true, (($OBSERVERSHIP->getStart()) ? $OBSERVERSHIP->getStart() : 0), true, true, (($OBSERVERSHIP->getEnd()) ? $OBSERVERSHIP->getEnd() : 0),false); 
			?>
			</tbody>
		</table>
		<div class="row-fluid">
			
			<div class="display-generic">By sending this approval form I certify that the above information has been completed to the best of my knowledge. I am aware that in order for this activity to be recognized by the UGME office as completed, I am responsible to ensure that my Supervisor electronically submits my confirmation of attendance within one week of the observership end date. I understand that if this is an International activity it is my responsibility to follow the International process and guidelines located on the UGME website.</div>

			<?php if ($ACTION == "Create") { ?>
			
			<label for="read" style="vertical-align:middle;font-weight:bold;"> <input name="read" type="checkbox" value="1" id="read"> Yes, I have read and agree to the procedures and regulations of the Student Observership policy.</label>
			
			<?php } ?>
			
		</div>
		<div class="row-fluid">
			<input class="btn btn-primary pull-right" type="submit" value="Submit" style="margin-top:15px;" />
		</div>
	</form>	