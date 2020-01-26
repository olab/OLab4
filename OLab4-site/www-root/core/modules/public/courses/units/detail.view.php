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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

$translate = $this->translate;

?>
<h1><?php echo $this->unit->getUnitText(); ?></h1>
<h2><?php echo $translate->_("Unit Details"); ?></h2>
<div id="unit-details-section" class="row-fluid">
    <div class="span8">
        <?php echo $this->unit->getUnitDescription(); ?>
        <br>&nbsp;
        <div>
        <h2><?php echo $translate->_("Unit Resources"); ?></h2>
        <?php if ($this->hasEventFiles) :?>
            <a href="<?php echo ENTRADA_URL ?>/api/zipup.api.php?course_id=<?php echo $this->course_id ?>&cunit_id=<?php echo $this->cunit_id ?>"
               target="_blank"
               class="btn btn-default">
                <?php echo $translate->_("Download all resources for this unit") ?>
            </a>
        <?php else: ?>
            <a href="#"
               class="btn btn-default"
               onclick="return false;"
               disabled>
                <?php echo $translate->_("No resources to download") ?>
            </a>
        <?php endif; ?>
        </div>
    </div>
    <div class="span4">
        <table class="unit-details">
            <tbody>
                <tr>
                    <th><?php echo $translate->_("Curriculum Period"); ?>:</th>
                    <td>
                        <?php echo (($this->curriculum_period->getCurriculumPeriodTitle()) ? html_encode($this->curriculum_period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($this->curriculum_period->getStartDate()))." to ".date("F jS, Y", html_encode($this->curriculum_period->getFinishDate())); ?>
                    </td>
                </tr>
                <?php if (isset($this->week)): ?>
                    <tr class="spacer"><td colspan="2"><hr></hr></td></tr>
                    <tr>
                        <th><?php echo $translate->_("Week"); ?>:</th>
                        <td><?php echo $this->week->getWeekTitle(); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($this->associated_faculty): ?>
                    <tr class="spacer"><td colspan="2"><hr></hr></td></tr>
                    <tr>
                        <th><?php echo $translate->_("Week Chair"); ?>:</th>
                        <td>
                            <?php foreach ($this->associated_faculty as $contact_order => $faculty_user): ?>
                                <?php if ($contact_order == count($this->associated_faculty) - 1): ?>
                                    <?php echo $faculty_user->getName(); ?>
                                <?php else: ?>
                                    <?php echo $faculty_user->getName(); ?>,
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row-fluid" style="margin-top: 30px;">
    <h2>
        <span><?php echo $this->translate->_("Week Objectives") ?></span>
        <a href="<?php echo ENTRADA_URL ?>/courses/units/summary?id=<?php echo $this->course_id ?>&cunit_id=<?php echo $this->cunit_id ?>"
           target="_blank"
           class="btn btn-inverse pull-right">
           <i class="fa fa-external-link external-link-icon"></i>
            <?php echo $translate->_("Open in New Window"); ?>
        </a>
    </h2>
</div>


<?php echo call_user_func($this->view_event_objectives); ?>
<?php if ($this->events): ?>
    <h2><?php echo $translate->_("Learning Events"); ?></h2>
    <div>
        <table class="table">
            <colgroup>
                <col width="25%"/>
                <col width="15%"/>
                <col width="60%"/>
            </colgroup>
            <thead>
                <tr>
                    <th><?php echo $translate->_("Event Date"); ?></th>
                    <th><?php echo $translate->_("Event Duration"); ?></th>
                    <th><?php echo $translate->_("Event Title"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->events as $event): ?>
                    <tr>
                        <td>
                            <a href="<?php echo ENTRADA_URL."/events?id=".$event->getID(); ?>">
                                <?php echo date(DEFAULT_DATE_FORMAT, $event->getEventStart()); ?>
                            </a>
                        </td>
                        <td><?php echo $event->getEventDuration(); ?> minutes</td>
                        <td>
                            <a href="<?php echo ENTRADA_URL."/events?id=".$event->getID(); ?>">
                                <?php echo $event->getEventTitle(); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<h2><?php echo $translate->_("Unit Tags"); ?></h2>
<?php echo call_user_func($this->view_tags); ?>

<style>
#content > .inner-content > .content > h1:first-of-type {
    display: none;
}
ul.objective-list.mapped-list {
    margin-left: 0;
}
hr {
    margin: 10px 0;
}
.weekly-objectives {
    max-height: 90vh;
    overflow-y: scroll;
    margin: 0 0 20px;
    overflow-x: hidden;
}
.weekly-objective {
    width: calc(100% - 40px);
}
.btn-inverse {
    background: #eee;
}
.btn-inverse:hover {
    background-color: #006699;
    border: 1px solid #004e75;
}
</style>
