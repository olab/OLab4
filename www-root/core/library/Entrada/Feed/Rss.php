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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

require_once("Entrada/lastrss/lastrss.class.php");

/**
 * Entrada_Feed_Rss
 *
 * Current fetches RSS feeds using LastRSS and returns the results.
 *
 * @todo This needs to be converted to Zend_Feed_Rss or Zend_Feed_Reader.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/
class Entrada_Feed_Rss extends lastRSS {
	public function __construct() {
		$this->cache_dir = RSS_CACHE_DIRECTORY;
		$this->cache_time = RSS_CACHE_TIMEOUT;
		$this->CDATA = "content";
		$this->stripHTML = true;
	}

	public function fetch($feed_url, $items_limit = 5) {
		$this->items_limit	= $items_limit;

		return $this->Get($feed_url);
	}
}