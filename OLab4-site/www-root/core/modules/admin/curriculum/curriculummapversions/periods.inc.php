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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca> and friends.
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

$compare_curriculum_types = function ($t1, $t2) {
    $row1 = array($t1->getCurriculumTypeOrder(), $t1->getCurriculumTypeName());
    $row2 = array($t2->getCurriculumTypeOrder(), $t2->getCurriculumTypeName());
    return ($row1 < $row2) ? -1 : 1;
};
$active_curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ORGANISATION_ID);
$active_curriculum_types = $active_curriculum_types ? $active_curriculum_types : array();
uasort($active_curriculum_types, $compare_curriculum_types);
$inactive_curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ORGANISATION_ID, 0);
$inactive_curriculum_types = $inactive_curriculum_types ? $inactive_curriculum_types : array();
uasort($inactive_curriculum_types, $compare_curriculum_types);
$curriculum_types = array_merge($active_curriculum_types, $inactive_curriculum_types);

$curriculum_periods = array();
foreach ($curriculum_types as $curriculum_type) {
    $curriculum_periods[$curriculum_type->getID()] = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
    if (is_array($PROCESSED["curriculum_periods"])) {
        foreach ($curriculum_periods[$curriculum_type->getID()] as $curriculum_period) {
            if (in_array($curriculum_period->getCperiodID(), $PROCESSED["curriculum_periods"])) {
                $PROCESSED["curriculum_types"][] = $curriculum_type->getID();
            }
        }
    }
}

?>

<div class="control-group">
    <label class="control-label form-required" for="report_description"><?php echo $translate->_("Curriculum Layouts"); ?>:</label>
    <div class="controls">
        Please select the <a href="<?php echo ENTRADA_RELATIVE; ?>/admin/curriculum/curriculumtypes?org=<?php echo $ORGANISATION_ID; ?>" target="_blank"><?php echo $translate->_("Curriculum Types"); ?></a> that you wish to include in order to select the curriculum period for this <?php echo $translate->_("Curriculum Map Version"); ?>.
        <style>
            div.curriculum-type-periods {
                margin-left: 25px;
                display: none;
                position: relative;
                margin-bottom: 10px;
            }
        </style>

        <hr />

        <?php
        if ($curriculum_types) {
            foreach ($curriculum_types as $curriculum_type) {
                ?>
                <div class="curriculum-type-container">
                    <label class="checkbox"><input type="checkbox" name="curriculum_types[]" id="curriculum-type-<?php echo $curriculum_type->getID(); ?>" value="<?php echo $curriculum_type->getID(); ?>"<?php echo (((isset($PROCESSED["curriculum_types"]) && in_array($curriculum_type->getID(), $PROCESSED["curriculum_types"])) && (int) $curriculum_type->getCurriculumTypeActive()) ? " checked=\"checked\"" : ""); ?> onclick="jQuery('#curriculum-type-<?php echo $curriculum_type->getID(); ?>-periods').toggle('slow');" /> <?php echo html_encode($curriculum_type->getCurriculumTypeName()); ?></label>
                    <div class="curriculum-type-periods" id="curriculum-type-<?php echo $curriculum_type->getID(); ?>-periods">
                        <?php
                        foreach ($curriculum_periods[$curriculum_type->getID()] as $curriculum_period) {
                            ?>
                            <label class="checkbox"><input type="checkbox" name="curriculum_periods[]" id="curriculum-period-<?php echo $curriculum_period->getCperiodID(); ?>" value="<?php echo $curriculum_period->getCperiodID(); ?>"<?php echo (((isset($PROCESSED["curriculum_periods"]) && in_array($curriculum_period->getCperiodID(), $PROCESSED["curriculum_periods"])) && (int) $curriculum_period->getActive()) ? " checked=\"checked\"" : ""); ?> />
                            <?php if ($curriculum_period->getCurriculumPeriodTitle()): ?>
                                <?php echo html_encode($curriculum_period->getCurriculumPeriodTitle()); ?>
                                <br />
                            <?php endif; ?>
                            <strong><?php echo date("D M d/y", $curriculum_period->getStartDate()); ?></strong> to <strong><?php echo date("D M d/y", $curriculum_period->getFinishDate()); ?></strong>
                            </label>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
        } else {
            echo display_notice(array("This organisation does not have any <a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculumtypes?org=".$ORGANISATION_ID."\">" . $translate->_("Curriculum Types") . "</a> defined."));
        }
        ?>
    </div>
</div>
<script type="text/javascript">
jQuery(function($) {
    jQuery('div.curriculum-type-container input[type=checkbox]:checked').each(function(el) {
        jQuery('#' + this.id + '-periods').show();
    });
});
</script>
