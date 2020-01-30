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
 * A model for OLab maps.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Models;
use Entrada\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property integer $question_id
 * @property string $validator
 * @property string $second_parameter
 * @property string $error_message
 */

class MapQuestionValidation extends BaseModel {

    protected $table = 'map_question_validation';
    protected $fillable = ['question_id','validator','second_parameter','error_message'];
    protected $validations = ['question_id' => 'exists:map_questions,id|integer|min:0|required',
                            'validator' => 'string',
                            'second_parameter' => 'string',
                            'error_message' => 'string'];

    public function MapQuestions() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapQuestions');
    }

}