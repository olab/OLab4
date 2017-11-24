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

namespace App\Modules\Olab\Models;
use App\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property integer $question_id
 * @property integer $session_id
 * @property string $response
 * @property integer $node_id
 */

class UserResponsesExt extends BaseModel {

    protected $table = 'user_responses_ext';
    protected $fillable = ['question_id','session_id','response','node_id'];
    protected $validations = ['question_id' => 'exists:map_questions,id|integer|min:0|required',
                            'session_id' => 'exists:user_sessions,id|integer|min:0|required',
                            'response' => 'max:1000|string',
                            'node_id' => 'integer|min:0|required'];

    public function MapQuestions() {
        return $this->belongsTo('App\Modules\Olab\Models\MapQuestions');
    }
    public function UserSessions() {
        return $this->belongsTo('App\Modules\Olab\Models\UserSessions');
    }

}