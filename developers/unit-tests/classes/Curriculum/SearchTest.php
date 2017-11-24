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
 * This class tests the functions in Models_Organisation.
 *
 * @author Organisation: The University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

require_once(dirname(__FILE__) . "/../../BaseTestCase.php");

class SearchTest extends BaseTestCase
{
    public function testParseBooleans()
    {
        $tests = array(
            array(
                "ehealth",
                "ehealth"
            ),
            array(
                "\"ehealth\"",
                "\"ehealth\""
            ),
            array(
                "ehealth and endocrine",
                "+ehealth +and +endocrine",
            ),
            array(
                "\"ehealth\" \"endocrine system\"",
                "+\"ehealth\" +\"endocrine system\""
            ),
            array(
                "\"ehealth\" OR \"endocrine system\"",
                "\"ehealth\" \"endocrine system\""
            ),
            array(
                "\"ehealth\" -\"endocrine system\"",
                "\"ehealth\" -\"endocrine system\""
            ),
            array(
                "asbestos OR environment OR lung",
                "asbestos environment lung"
            ),
            array(
                "asbestos environment OR lung",
                "+asbestos +environment lung"
            ),
            array(
                "asbestos OR (environment lung)",
                "asbestos ( +environment +lung )"
            ),
            array(
                "(asbestos environment) OR (lung health)",
                "( +asbestos +environment ) ( +lung +health )"
            ),
            array(
                "(behav* OR \"ehealth\") \"endocrine system\"",
                "+( behav* \"ehealth\" ) +\"endocrine system\""
            ),
            array(
                "(behav* \"ehealth\") OR \"endocrine system\"",
                "( +behav* +\"ehealth\" ) \"endocrine system\""
            ),
            array(
                "-(\"nervous system\" \"endocrine system\")",
                "-( +\"nervous system\" +\"endocrine system\" )"
            ),
            array(
                "(this that)",
                "( +this +that )"
            ),
            array(
                "(this +that)",
                "( +this +that )"
            ),
            array(
                "(+this +that) -those",
                "( +this +that ) -those"
            ),
            array(
                "this, that, those",
                "+this +that +those"
            ),
            array(
                "\"Briefly describe the normal anatomy of the thyroid gland, taking note of its blood supply and intimate anatomical relationships, particularly the recurrent laryngeal nerves.\"",
                "\"Briefly describe the normal anatomy of the thyroid gland, taking note of its blood supply and intimate anatomical relationships, particularly the recurrent laryngeal nerves.\""
            ),
        );
        $search = Phake::mock('Entrada_Curriculum_Search');
        Phake::whenStatic($search)->parseBooleans(Phake::anyParameters())->thenCallParent();
        foreach ($tests as $test) {
            list($search_term, $expected_new_search_term) = $test;
            $this->assertEquals($expected_new_search_term, Phake::makeVisible($search)->parseBooleans($search_term));
        }
    }
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    SearchTest::main();
}
