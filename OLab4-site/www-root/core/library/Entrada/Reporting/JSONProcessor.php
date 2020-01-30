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
* Class to process JSON sent and received in api-student.inc.php.
*
* @author Organisation: University of Ottawa
* @author Unit: Faculty of Medicine
* @author Developer: Geoff Harvey <gharvey@uottawa.ca>
* @copyright Copyright 2016 University of Ottawa. All Rights Reserved.
*
*/
class Entrada_Reporting_JSONProcessor {
	public static function processData($data) {
		global $translate;
		
		$decoded_html = html_decode($data);
		$decoded_json = json_decode($decoded_html);
		
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				break;
			default:
				throw new InvalidArgumentException("Invalid data");
				break;
		}

	    return $decoded_json;

	}

}