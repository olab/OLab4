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
 * 
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/


/**
 * 
 * Award is the abstract base class for @see{InternalAward} and @see{ExternalAward} It provides methods and protected properties common to both
 * @author Jonathan Fingland
 *
 */
abstract class Award {
	/**
	 * Award Title
	 * @var string
	 */
	protected $title;
	
	/**
	 * Award Terms of Reference
	 * @var string
	 */
	protected $terms;
	
	/**
	 * Awarding Body of the award (who issued the award)
	 * @var string
	 */
	protected $awarding_body;
	
	/**
	 * Non-instantiable constructor. Use only as aid in subclass constructor
	 * @param string $title
	 * @param string $terms
	 * @param string $awarding_body
	 */
	function __construct($title, $terms, $awarding_body) {
		$this->title = $title;
		$this->terms = $terms;
		$this->awarding_body = $awarding_body; 
	}
	
	/**
	 * Compares the target award against the provided award using the supplied property name.
	 * Currently only supports comparison by 'title'
	 * @param Award $award
	 * @param string $compare_by
	 * @return number
	 */
	function compare (Award $award, $compare_by="title") {
		switch($compare_by) {
			case 'title':
				return strcasecmp($this->title,  $award->title);
				break;
		}
	}
	
	/**
	 * Compares two awards by title. Alias for $award_1->compare($award_2); 
	 * @param Award $award_1
	 * @param Award $award_2
	 * @return Ambigous <number, number>
	 */
	static function compare_awards(Award $award_1, Award $award_2) {
		return $award_1->compare($award_2);
	}
	
	/**
	 * Returns the title of the award
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * Returns the terms of reference of the award
	 * @return string
	 */
	public function getTerms() {
		return $this->terms;
	}
	
	/**
	 * Returns the awarding body of the award
	 * @return string
	 */
	public function getAwardingBody() {
		return $this->awarding_body;
	}
}