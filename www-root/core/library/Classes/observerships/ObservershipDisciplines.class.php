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
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version 
 */

require_once("ObservershipDiscipline.class.php");
require_once("Classes/utility/Collection.class.php");

/**
 * Utility Class for getting a list of Observership Disciplines
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class ObservershipDisciplines extends Collection {
	
	static public function get() {
		
		global $db;
		$query = "SELECT * from observership_disciplines";
				
		$results	= $db->GetAll($query);
		$discs = array();
		if ($results) {
			foreach ($results as $result) {
				$disc = new ObservershipDiscipline( $result['id'], $result['discipline_title']);
				$discs[] = $disc;
			}
		}
		return new self($discs);
	}
}			
		