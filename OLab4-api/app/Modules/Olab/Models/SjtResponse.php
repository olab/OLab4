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
 * @property integer $response_id
 * @property integer $position
 * @property integer $points
 */

class SjtResponse extends BaseModel {

    protected $table = 'sjt_response';
    protected $fillable = ['response_id','position','points'];
    protected $validations = ['response_id' => 'exists:map_question_responses,id|integer|min:0|required',
                            'position' => 'integer|required',
                            'points' => 'integer|required'];

    public function MapQuestionResponses() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapQuestionResponses');
    }

}