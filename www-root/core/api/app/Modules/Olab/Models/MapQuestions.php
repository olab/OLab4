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
 * @property integer $parent_id
 * @property integer $map_id
 * @property string $stem
 * @property integer $entry_type_id
 * @property integer $width
 * @property integer $height
 * @property string $feedback
 * @property string $prompt
 * @property string $show_answer
 * @property integer $counter_id
 * @property integer $num_tries
 * @property string $show_submit
 * @property integer $redirect_node_id
 * @property string $submit_text
 * @property integer $type_display
 * @property string $settings
 * @property integer $is_private
 * @property integer $order
 * @property string $external_source_id
 */

class MapQuestions extends BaseModel {

    protected $table = 'map_questions';
    protected $fillable = ['parent_id','map_id','stem','entry_type_id','width','height','feedback','prompt','show_answer','counter_id','num_tries','show_submit','redirect_node_id','submit_text','type_display','settings','is_private','order','external_source_id'];
    protected $validations = ['parent_id' => 'exists:map_questions,id|integer|min:0',
                            'map_id' => 'exists:maps,id|integer|min:0',
                            'stem' => 'max:500|string',
                            'entry_type_id' => 'integer|required',
                            'width' => 'integer|required',
                            'height' => 'integer|required',
                            'feedback' => 'max:1000|string',
                            'prompt' => 'string',
                            'show_answer' => 'integer|required',
                            'counter_id' => 'integer',
                            'num_tries' => 'integer|required',
                            'show_submit' => 'integer|required',
                            'redirect_node_id' => 'integer|min:0',
                            'submit_text' => 'max:200|string',
                            'type_display' => 'integer|required',
                            'settings' => 'string',
                            'is_private' => 'integer|required',
                            'order' => 'integer',
                            'external_source_id' => 'max:255|string'];

    public function scopeType( $query )  {
        $query->with( [ 'MapQuestionType' ] );
    }

    public function MapQuestionType() {
        return $this->belongsTo('App\Modules\Olab\Models\MapQuestionTypes', 'entry_type_id');
    }
    public function MapQuestionResponses() {
        return $this->hasMany('App\Modules\Olab\Models\MapQuestionResponses', 'question_id');
    }
    public function MapQuestionValidation() {
        return $this->hasMany('App\Modules\Olab\Models\MapQuestionValidation');
    }
    public function QCumulative() {
        return $this->hasMany('App\Modules\Olab\Models\QCumulative');
    }
    public function UserResponses() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponses');
    }
    public function UserResponsesCBRef() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesCBRef');
    }
    public function UserResponsesC3st() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesC3st');
    }
    public function UserResponsesCopyExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesCopyExt');
    }
    public function UserResponsesExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesExt');
    }
    public function UserResponsesPart() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesPart');
    }
    public function Maps() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }

}