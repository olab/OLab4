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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

global $translate, $HEAD, $ENTRADA_USER;

$curriculum_type_id = !empty($this->curriculum_type_id) ? $this->curriculum_type_id : 0;
$periods = !empty($this->curriculum_periods) ? $this->curriculum_periods : array();
$cperiod_id = !empty($this->cperiod_id) ? $this->cperiod_id : 0;
$versions = !empty($this->curriculum_map_versions) ? $this->curriculum_map_versions : array();
$version_id = !empty($this->version_id) ? $this->version_id : 0;

$HEAD["call-api.js"] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/call-api.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
$HEAD["version-select.js"] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/version-select.js?release=".html_encode(APPLICATION_VERSION)."&revision=2\"></script>\n";
$HEAD["version-select.css"] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".ENTRADA_URL."/css/version-select.css?release=".html_encode(APPLICATION_VERSION)."\" />\n";
$HEAD["version-select"] = "<script type=\"text/javascript\">version_select();</script>\n";
?>

<div id="version-period-select" class="control-group"<?php echo !$periods ? " style=\"display: none\"" : ""; ?>>
    <label for="version_cperiod_id" class="control-label form-nrequired"><?php echo $translate->_("Curriculum Period"); ?></label>
    <div class="controls">
        <select id="version_cperiod_id" name="version_cperiod_id">
            <option value="0"<?php echo $cperiod_id == 0 ? " selected=\"selected\"" : ""; ?>>
                <?php echo $translate->_("No Period"); ?>
            </option>
            <?php foreach ($periods as $period): ?>
                <option value="<?php echo html_encode($period->getID()); ?>"<?php echo $cperiod_id == $period->getID() ? " selected=\"selected\"" : ""; ?>><?php echo html_encode(($period->getCurriculumPeriodTitle() ? $period->getCurriculumPeriodTitle()." - " : "").date("F jS, Y" , $period->getStartDate())." to ".date("F jS, Y" , $period->getFinishDate())); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="clearfix"></div>
<div id="version-select" class="control-group"<?php echo !$versions ? " style=\"display: none\"" : ""; ?>>
    <label for="version_id" class="control-label muted"><?php echo $translate->_("Curriculum Map Version"); ?>:</label>
    <div class="controls">
        <select id="version_id" name="version_id">
            <option value="0"<?php echo $version_id == 0 ? " selected=\"selected\"" : ""; ?>>
                <?php echo $translate->_("No Version"); ?>
            </option>
            <?php foreach ($versions as $version): ?>
                <option value="<?php echo html_encode($version->getID()); ?>"<?php echo $version_id == $version->getID() ? " selected=\"selected\"" : ""; ?>>
                    <?php echo html_encode($version->getTitle())." (".html_encode($version->getStatus()).")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="clearfix"></div>
