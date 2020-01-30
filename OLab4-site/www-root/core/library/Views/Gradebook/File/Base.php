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
 * File Base
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_File_Base extends Views_Gradebook_Base {
	protected $data;

	/**
	 * Creates a signature that can be used to temporarily allow
	 * access to an otherwise locked-down file
	 * @return string URL-safe signature
	 */
	protected function getFileSignature() {
		return Entrada_Utilities_File::getSignature($this->data["afversion_id"].$this->data["proxy_id"].$this->data["organisation_id"].$this->getTimestamp(), $this->data['user_private_hash']);
	}

	/**
	 * Creates a URL that will provide temporary access to a file for viewing
	 * @return string URL
	 */
	protected function getFileUrl() {
		$params = array(
			"afversion_id" => $this->data["afversion_id"],
			"proxy_id" => $this->data["proxy_id"],
			"organisation_id" => $this->data["organisation_id"],
			"timestamp" => $this->getTimestamp(),
			"signature" => $this->getFileSignature(),
		);

		return ENTRADA_URL."/api/file.api.php?".http_build_query($params);
	}

	/**
	 * Creates a timestamp. Put into it's own method so it can be overrided
	 * @return int Unix timestamp
	 */
	protected function getTimestamp() {
		return Entrada_Utilities_File::getTimestamp();
	}
}