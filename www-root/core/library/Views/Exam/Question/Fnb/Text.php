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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Question_Fnb_Text extends Views_Deprecated_Base {
    protected $default_fieldset = array(
        "fnb_text_id", "qanswer_id", "updated_date", "updated_by", "deleted_date"
    );

    protected $table_name               = "exam_question_fnb_text";
    protected $primary_key              = "fnb_text_id";
    protected $default_sort_column      = "`exam_question_fnb_text`.`qanswer_id`";

    protected $fnb_text;

    public function __construct(Models_Exam_Question_Fnb_Text $fnb_text) {
        $this->fnb_text = $fnb_text;
    }

    public function renderSpan() {
        global $translate;

        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["questions"];

        $html = "<span class=\"correct-answer-fnb label label-info\">" . $this->fnb_text->getText() . "<a class=\"remove_correct_text_anchor\"><i data-answer-id=\"" . $this->fnb_text->getAnswerID() . "\" class=\"remove_correct_text fa fa-lg fa-times\"></i></a></span>";

        return $html;

    }

}