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
 * @author Developer: Craig Parsons <craig.parsons@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if (!isset($this)) {
    throw new Exception("You cannot visit this file directly because it is an include.");
}

echo '<li class="course-unit-item">';
?>
<?php
?> <a href="<?php echo COMMUNITY_URL.$this->COMMUNITY_URL.':'.$this->PAGE_URL.'?cunit_id='.$this->unit_id; ?>">

<?php
echo $this->unit_text;
?>
</a>
</li>
