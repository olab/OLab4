<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

require_once("Classes/utility/Collection.class.php");
require_once("Classes/utility/SimpleCache.class.php");

require_once("Classes/users/User.class.php");
require_once("Classes/users/Users.class.php");

require_once("Classes/users/metadata/MetaDataRelation.class.php");
require_once("Classes/users/metadata/MetaDataRelations.class.php");
require_once("Classes/users/metadata/MetaDataType.class.php");
require_once("Classes/users/metadata/MetaDataTypes.class.php");
require_once("Classes/users/metadata/MetaDataValue.class.php");
require_once("Classes/users/metadata/MetaDataValues.class.php");

/**
 * @param MetaDataType $type
 * @return array
 */
function getParentArray(MetaDataType $type) {
	$parent = $type->getParent();
	if (is_null($parent)) {
		return array($type);	
	}
	else {
		$arr = getParentArray($parent);
		array_push($arr, $type);
		return $arr;
	} 
}

/**
 * @param MetaDataType $type
 * @return array
 */
function getTopParentType(MetaDataType $type) {
	$chain = getParentArray($type);
	return array_shift($chain);
}

/**
 * returns an array of arrays. each entry in the outer array is an array of steps *down* in the hierarchy from the provided MetaDataType to the relevant types in MetaDataTypes. $types in the hierarchy may include types which are not directly accessible for this user/group/etc 
 * @param MetaDataTypes $source_types
 * @param MetaDataType $type
 * @return array
 */
function getDescendentTypesArray(MetaDataTypes $source_types, MetaDataType $type) {
	//the easiest way to do this is to get the parent arrays, 
	//if the provided type is in the arrays, splice that and anything before out
	$child_types = array();
	foreach ($source_types as $source_type) {
		$parent_array = getParentArray($source_type);
		$pos = array_search($type, $parent_array);
		if ($pos !== false) {
			array_splice($parent_array, 0, $pos+1);
			if ($parent_array) { //the parent might have been the only element and we just removed it.
				$child_types[] = $parent_array;
			}
		}
	}
	return $child_types;
}

/**
 * returns an array of the types in the provided MetaDataTypes collection which refer to the provided MetaDataType as a parent. This is fundamentally different from the descendent types method.
 * @param MetaDataTypes $source_types
 * @param MetaDataType $type
 * @return array
 */
function getChildTypes(MetaDataTypes $source_types, MetaDataType $type) {
	$children = array();
	foreach ($source_types as $source_type) {
		if ($source_type->getParent() === $type) {
			$children[] = $source_type;
		}
	}	
	return $children;
}

function getUniqueDescendantTypeIDs(MetaDataTypes $source_types, MetaDataType $type) {
	$desc_type_sets = getDescendentTypesArray($source_types, $type);
	//var_dump($desc_type_sets); 
	$type_ids = array();
	foreach($desc_type_sets as $desc_type_set) {
		foreach ($desc_type_set as $desc_type) {
			$id = $desc_type->getID();
			if (!in_array($id, $type_ids)) {
				$type_ids[] = $id;
			}
		}
	}
	return $type_ids;
}

function displayMetaDataRow_User(MetaDataValue $value, MetaDataType $category) {
	$chain = getParentArray($value->getType());
	array_shift($chain);//toss the top
	if ($chain) {
		$sub_type = implode(" > ", $chain); 
	} else {
		$sub_type = "N/A";	
	}
	ob_start();
	?>
	<tr class="value_display" id="value_display_<?php echo $value->getID(); ?>">
		<td><?php echo html_encode($sub_type); ?></td>
		<td><?php echo html_encode($value->getValue()); ?></td>
		<td class="flex"><?php echo nl2br(html_encode($value->getNotes)); ?></td>
		<td><?php echo ($eff_date = $value->getEffectiveDate()) ? date("Y-m-d", $eff_date) : "" ; ?></td>
		<td><?php echo ($exp_date = $value->getExpiryDate()) ? date("Y-m-d", $exp_date) : "" ; ?></td>
	</tr>
	<?php
	return ob_get_clean();
}

function editMetaDataRow(MetaDataValue $value, MetaDataType $category, array $descendant_type_sets = array()) {
	$vid = $value->getID();
	ob_start();	
	?>
	<tr class="value_edit" id="value_edit_<?php echo $vid; ?>">
		<td class="control"><input type="checkbox" title="Delete record" class="delete_btn" id="delete_<?php echo $vid; ?>" name="value[<?php echo $vid; ?>][delete]" value="1" /></td>
		<td><?php if ($descendant_type_sets) { ?>
			<select name="value[<?php echo $vid; ?>][type]">
				<?php 
				foreach ($descendant_type_sets as $type_set){
					$type = end($type_set);
					$selected = $type === $value->getType();
					echo build_option($type->getID(), html_encode(implode(" > ", $type_set)), $selected);
				} 
				?>
			</select>
			<?php } else { ?>
			<input type="hidden" name="value[<?php echo $vid; ?>][type]" value="<?php echo $category->getID(); ?>" />
			<?php } ?>
		</td>
		<td><input type="text" name="value[<?php echo $vid; ?>][value]" value="<?php echo html_encode($value->getValue()); ?>" /></td>
		<td><input type="text" name="value[<?php echo $vid; ?>][notes]" value="<?php echo nl2br(html_encode($value->getNotes())); ?>" /></td>
		<td><input type="text" class="date" id="value_<?php echo $vid; ?>_effective_date" name="value[<?php echo $vid; ?>][effective_date]" value="<?php echo ($eff_date = $value->getEffectiveDate()) ? date("Y-m-d", $eff_date) : "" ; ?>" /></td>
		<td><input type="text" class="date" id="value_<?php echo $vid; ?>_expiry_date" name="value[<?php echo $vid; ?>][expiry_date]" value="<?php echo ($exp_date = $value->getExpiryDate()) ? date("Y-m-d", $exp_date) : "" ; ?>" /></td>
	</tr>
	<?php
	return ob_get_clean();
}

function getCategories(MetaDataTypes $available_types) {
	$categories = array();
	//For each of the applicable types without a parent (top-level types), create a section to help organize    
	foreach ($available_types as $type) {
		$top_p = getTopParentType($type);
		if (!in_array($top_p, $categories, true)) {
			$categories[] = $top_p;
		}
	}
	return $categories;
}

function getTypes_User(User $user) {
	$org_id = $user->getOrganisationId();
	$group = $user->getGroup();
	$role = $user->getRole();
	$proxy_id = $user->getID();
	
	return MetaDataTypes::get($org_id, $group, $role, $proxy_id);
}

function getUserCategoryValues(User $eUser, MetaDataType $category) {
	$org_id = $eUser->getOrganisationId();
	$group = $eUser->getGroup();
	$role = $eUser->getRole();
	$proxy_id = $eUser->getID();

	return MetaDataValues::get($org_id, $group, $role,$proxy_id, $category, true, array("order by"=>array(array("meta_value_id", "desc"))));
}

function errNoCats_MetaDataTable() {
	return display_notice("There are currently no Meta Data Categories applicable to this user.");
}


function editMetaDataTable($contents, $prepend=null) {
	ob_start();
	echo $prepend;
	?>
	<input type="hidden" name="request" value="update" />
	<table class="DataTable" callpadding="0" cellspacing="0">
		<colgroup>
			<col width="4%" />
			<col width="18%" />
			<col width="15%" />
			<col width="33%" />
			<col width="15%" />
			<col width="15%" />
		</colgroup>
		<thead>
			<tr>
				<th>Remove</th>
				<th>Sub-type</th>
				<th>Value</th>
				<th>Notes</th>
				<th>Effective Date</th>
				<th>Expiry Date</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td></td>
				<td colspan="5" class="control">
					<input class="btn btn-primary" type="submit" value="Save" id="save_btn" />
				</td>
			</tr>
		</tfoot>
	<?php echo $contents; ?>
	</table>	
	<?php 
	return ob_get_clean();
}

function mkHiddenInput($name, $value, $id=null) {
	
	return "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\"".(($id)?"id=\"".$id."\"":"")." />";
}

function getHiddenMetaInputs($organisation_id, $group, $role, $category_id) {
	$inputs = array();
	$inputs[] = mkHiddenInput("associated_organisation_id", $organisation_id);
	$inputs[] = mkHiddenInput("associated_group", $group);
	$inputs[] = mkHiddenInput("associated_role", $role);
	$inputs[] = mkHiddenInput("associated_cat_id", $category_id, "cat_id");
	return implode("\n", $inputs);
}

function editMetaDataTable_Category($organisation_id=null, $group=null, $role=null, $proxy_id=null, MetaDataType $category) {
	//for this case we have to get the users which are members of the relevant org, group, role, and where relevant? proxy_id
	$users = Users::get($organisation_id, $group, $role, $proxy_id);
	$types = MetaDataTypes::get($organisation_id, $group, $role, $proxy_id);
	$category_id = $category->getID(); 
	ob_start();
	foreach ($users as $user) {
		$values = getUserCategoryValues($user, $category);
		//var_dump($values);
		$descendant_type_sets = getDescendentTypesArray($types, $category); 
		$label = html_encode($user->getFullname());
		
	?>
	<tbody id="user_<?php echo $user->getID(); ?>">
		<tr class="user_head" id="user_head_<?php echo $user->getID(); ?>">
			<td></td>
			<th colspan="2"><?php echo $label; ?></th>
			<td class="control" colspan="3"><ul class="page-action"><li class="last"><a href="#" class="add_btn" id="add_btn_<?php echo $category_id; ?>">Add record for <?php echo $label; ?></a></li></ul></td>
		</tr>
		<?php
			foreach ($values as $value) {
				echo editMetaDataRow($value, $category, $descendant_type_sets);
			} ?>
	</tbody>
	<?php 
	}
	$prepend = getHiddenMetaInputs($organisation_id, $group, $role, $category_id);
	return editMetaDataTable(ob_get_clean(), $prepend);
}

function editMetaDataTable_User(User $eUser) {
	
	$types = getTypes_User($eUser);
	$categories = getCategories($types);
	if (count($categories) == 0) {
		return errNoCats_MetaDataTable();
	}
	ob_start();
	if ($categories) {
		foreach ($categories as $category) { 
			$values = getUserCategoryValues($eUser, $category);
			//var_dump($values);
			$descendant_type_sets = getDescendentTypesArray($types, $category); 
			$label = html_encode($category->getLabel());
	?>
	<tbody id="cat_<?php echo $category->getID(); ?>">
		<tr class="cat_head" id="cat_head_<?php echo $category->getID(); ?>">
			<td></td>
			<th colspan="2"><?php echo $label; ?></th>
			<td class="control" colspan="3"><ul class="page-action"><li class="last"><a href="#" class="add_btn" id="add_btn_<?php echo $category->getID(); ?>">Add <?php echo $label; ?></a></li></ul></td>
		</tr>
		<?php		
			if ($values) {
				foreach ($values as $value) {
					echo editMetaDataRow($value, $category, $descendant_type_sets);
				} 
			}?>

	</tbody>
	<?php 
		}
	}
	return editMetaDataTable(ob_get_clean());
}

function array_any(array $arr) {
	return array_search(true, $arr);
}

function array_all(array $arr) {
	return !array_search(false, $arr, true);
}

function validate_value_update($value) {
	$value = filter_var_array($value,array(
		'type' => array("filter" => FILTER_VALIDATE_INT, "options" => array('min_range' => 0)),
		'value' => FILTER_UNSAFE_RAW,
		'notes' => FILTER_UNSAFE_RAW,
		'effective_date' => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => "/\d{4}-\d{1,2}-\d{1,2}/")),
		'expiry_date' => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => "/\d{4}-\d{1,2}-\d{1,2}/"))
	));

	if (!array_any($value)){
		$value = false;
	}
	return $value;
}

function validate_value_delete($value) {
	$value = filter_var_array($value,array(
			'delete' => array("filter" => FILTER_VALIDATE_INT, "options" => array('min_range' => 0, 'max_range'=>1))
	));

	if (!array_any($value)){
		$value = false;
	}
	return $value;
}

function fmt_date($value) {
	if (false === $value) {
		return null;
	} else {
		return strtotime($value);
	}
}

function display_category_select($organisation_id=null, $group=null, $role=null, $proxy_id=null, $cat_id=null) {
	$types = MetaDataTypes::get($organisation_id, $group, $role, $proxy_id);
	$categories = getCategories($types);
	if (count($categories) == 0) {
		return "None";
	}
	ob_start();
	?>
		<select id="associated_cat_id" name="associated_cat_id">
			<?php
				foreach ($categories as $category) {
					echo build_option($category->getID(), $category->getLabel(), $cat_id == $category->getID());
				} 
			?>
		</select>
	<?php 
	return ob_get_clean();	
}
