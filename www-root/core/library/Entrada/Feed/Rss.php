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
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 * Entrada_Feed_Rss
 *
 * Fetches RSS feeds and returns the results.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
*/
class Entrada_Feed_Rss {
    /**
     * @var bool Whether or not this is passed through the clean_input() striptags rule.
     */
    private $strip_tags = true;

	public function __construct($strip_tags = true) {
		if ($strip_tags !== true) {
		    $this->strip_tags = false;
        } else {
		    $this->strip_tags = true;
        }
	}

	public function fetch($feed_url = "", $items_limit = 5) {
	    global $ENTRADA_CACHE;

		$items_limit = (int) $items_limit;

        $output = [];

		if ($feed_url) {
            Zend_Feed_Reader::setCache($ENTRADA_CACHE);

            $feed = Zend_Feed_Reader::import($feed_url);

            if ($this->strip_tags) {
                $filters = ["striptags", "trim"];
            } else {
                $filters = ["trim"];
            }

            $output = [
                "title" => clean_input($feed->getTitle(), $filters),
                "link" => clean_input($feed->getLink(), $filters),
                "dateModified" => clean_input($feed->getDateModified(), $filters),
                "description" => clean_input($feed->getDescription(), $filters),
                "language" => clean_input($feed->getLanguage(), $filters),
                "items" => [],
            ];

            $items = 0;

            foreach ($feed as $entry) {
                $items++;

                if ($items <= $items_limit) {
                    $output["items"][] = [
                        "title" => clean_input($entry->getTitle(), $filters),
                        "description" => clean_input($entry->getDescription(), $filters),
                        "dateModified" => $entry->getDateModified(),
                        "authors" => $entry->getAuthors(),
                        "link" => clean_input($entry->getLink(), $filters),
                        "content" => clean_input($entry->getContent(), $filters),
                    ];
                } else {
                    break;
                }
            }
        }

		return $output;
	}
}