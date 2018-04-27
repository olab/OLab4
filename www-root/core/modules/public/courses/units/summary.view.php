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
 * @author Developer: Ryan Sherrington <ryan.sherrington@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 *
 */
if (!isset($this)) {
    throw new Exception("You cannot visit this file directly because it is an include.");
}
?><div class="no-printing pull-right space-above medium">
    <a href="javascript:window.print()">
        <img    src="<?php echo ENTRADA_URL; ?>/images/page-print.gif"
                width="16"
                height="16"
                alt="<?php echo $this->translate->_("Print this page") ?>"
                title="<?php echo $this->translate->_("Print this page") ?>"
                border="0"/>
    </a>
    <a href="javascript: window.print()">
        <?php echo $this->translate->_("Print this page") ?>
    </a>
</div>
<h1 class="weekly-summary-title">
    <?php echo date("Y", html_encode($this->curriculum_period->getStartDate()))."/".date("Y", html_encode($this->curriculum_period->getFinishDate()))." - "; ?>
    <?php echo $this->course_code." - " ?>
    <?php echo $this->unit ?>
</h1>

<br><br>

<div class="print-only weekly-objectives-summary-wrapper">
    <h2><?php echo $this->unit->getUnitText() ?> <?php echo $this->translate->_("Objectives Summary") ?></h2>
    <div class="weekly-objectives">
        <?php foreach ($this->week_and_event_objectives as $week_objective_id => $week_objective): ?>
            <div class="weekly-objective" >
                <a href="#<?php echo $week_objective['objective_name'] ?>">
                    <?php echo $week_objective['index'].'. '.$week_objective['objective_description'] ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="weekly-objectives-container">

    <h2><?php echo $this->translate->_("Week Objectives") ?></h2>
    <div class="no-printing pull-right">
        <a href="#" class="btn-expand"><?php echo $this->translate->_("Expand All") ?></a>
        <a href="#" class="btn-collapse"><?php echo $this->translate->_("Collapse All") ?></a>
    </div>
    <p class="desc no-printing"><?php echo $this->translate->_("Click to expand each objective") ?></p>

    <?php echo call_user_func($this->view_event_objectives); ?>
</div>
<br>

<script type="text/javascript">
    /** Accordion functionality */
    jQuery(document).ready(function(){

        // Click to expand or collapse single objective
        jQuery('.weekly-objective.expandable h3').click(function(){
            jQuery(this).parent('.weekly-objective').find('.weekly-objectives-wrapper').slideToggle();
            return false;
        });

        // Expand all button
        jQuery('.btn-expand').click(function(){
            jQuery('.weekly-objectives-wrapper').slideDown();
            return false;
        });

        // Collapse all button
        jQuery('.btn-collapse').click(function(){
            jQuery('.weekly-objectives-wrapper').slideUp();
            return false;
        });

    });
</script>
