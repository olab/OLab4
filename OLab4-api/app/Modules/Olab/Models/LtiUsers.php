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
 * @property string $consumer_key
 * @property string $context_id
 * @property string $user_id
 * @property string $lti_result_sourcedid
 * @property string $created
 * @property string $updated
 */

class LtiUsers extends BaseModel {

    protected $table = 'lti_users';
    protected $fillable = ['consumer_key','context_id','user_id','lti_result_sourcedid','created','updated'];
    protected $validations = ['consumer_key' => 'exists:lti_contexts,consumer_key|max:255|string',
                            'context_id' => 'max:255|string',
                            'user_id' => 'max:255|string',
                            'lti_result_sourcedid' => 'max:255|string',
                            'created' => 'required|date',
                            'updated' => 'required|date'];

    public function LtiContexts() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\LtiContexts');
    }

}