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
 * @author Developer: Aidin Niavarani <aidin.niavarani@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 *
 */
if (!isset($this)) {
    throw new Exception("You cannot visit this file directly because it is an include.");
}
?>
<div class="weekly-objectives">
    <?php foreach ($this->week_and_event_objectives as $week_objective_id => $week_objective): ?>
        <div class="weekly-objective <?php if( count( $week_objective['events'] ) ) echo 'expandable'; ?>">
            <a name="<?php echo $week_objective['objective_name'] ?>"></a>
            <h3><?php echo $week_objective['index'].': '.$week_objective['objective_description'] ?></h3>
            <?php if( count( $week_objective['events'] ) ) :?>
                <div class="weekly-objectives-wrapper force-print-display"<?php if( $this->hide_objectives_by_default ) { ?> style="display: none;"<?php } ?>>
                    <?php foreach ($week_objective['events'] as $event_id => $events): ?>
                        <div class="weekly-objective-wrapper">
                            <div class="weekly-objective-header">
                                <a class="title" href="<?php echo ENTRADA_URL; ?>/events/?rid=<?php echo $event_id; ?>"><?php echo $events[0]['event_title']; ?></a>
                                <span><?php echo $events[0]['eventtype_title'] ?></span>
                            </div>
                            <div class="weekly-objective-details">
                                <ul>
                                    <?php foreach ($events as $event): ?>
                                        <li><?php echo $event['objective_description'] ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>