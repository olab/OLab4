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
?><style>
    .course-units-table {
        width: 100%;
    }
    .course-units-table tr > * {
        padding: 10px;
        text-align: left;
    }
    .course-units-table tr > *:first-child {
        padding: 10px;
        font-weight: normal;
        white-space: nowrap;
        width: 15%;
    }
    .course-units-table tbody tr > * {
        border-bottom: solid 1px #ddd;
    }
    .course-units-table tbody tr th {
        color: #666;
    }
    .course-units-table tbody tr:last-child > * {
        border-bottom: none;
    }
    .course-units-table tbody tr td {
        padding: 5px 10px;
        height: 3em;
        background: #f8f8f8;
    }
    .course-units-table tr > .mobile-only {
        display: none;
    }
    .course-units-table tbody tr td:nth-child(even) {
        background: #f2f2f2;
    }
    @media (max-width: 599px) {
        .course-units-table tr > .mobile-only {
            display: table-cell;
        }
        .course-units-table tr > *:not(.mobile-only) {
            display: none;
        }
    }
</style>

<h1><?php echo $this->translate->_("Weeks"); ?></h1>
<?php 
    if ($this->units): 
        $curriculum_type_count = 1;
?>
    <?php foreach ($this->units as $curriculum_type_name => $units_by_week): ?>
        <h2><?php echo $curriculum_type_name; ?></h2>
        <table class="course-units-table year-<?php echo $curriculum_type_count; ?>">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <?php foreach ($this->year_courses[$curriculum_type_name] as $course => $week_titles): ?>
                        <th><?php echo $course; ?></th>
                    <?php endforeach; ?>
                    <th class="mobile-only"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units_by_week as $week_title => $units_by_course): ?>
                    <tr>
                        <th><?php echo html_encode( $week_title ); ?></th>
                        <?php $mobile_units = ''; ?>
                        <?php foreach ($this->year_courses[$curriculum_type_name] as $course_code => $course_content): ?>
                            <td>
                                <?php if (array_key_exists($course_code, $units_by_course)): 
                                        $unit = $units_by_course[$course_code]; 
                                        ob_start();
                                ?>
                                    <a href="<?php echo ENTRADA_URL."/courses/units?id=".html_encode($unit->getCourseID())."&cunit_id=".html_encode($unit->getID()); ?>"><?php echo html_encode($unit->getUnitText()).(($unit->getUnitCode() == "") ? " (".html_encode($course_code).")" : ""); ?></a>
                                <?php 
                                    $week_link = ob_get_clean();
                                    echo $week_link;
                                    $mobile_units .= '<li>' . $week_link . '</li>';  ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="mobile-only">
                            <?php echo '<ul>' . $mobile_units . '</ul>'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <style>
            .course-units-table.year-<?php echo $curriculum_type_count; ?> tbody tr td {
                width: <?php echo (floor(8500 / count($this->year_courses[$curriculum_type_name]))/100); ?>%;
            }
        </style>
    <?php $curriculum_type_count++; ?>
    <?php endforeach; ?>
<?php else: ?>
    <?php echo display_notice("No weeks!"); ?>
<?php endif; ?>