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
 * @property string $share_key_id
 * @property string $primary_consumer_key
 * @property string $primary_context_id
 * @property string $auto_approve
 * @property string $expires
 */

class LtiSharekeys extends BaseModel {

    protected $table = 'lti_sharekeys';
    protected $fillable = ['share_key_id','primary_consumer_key','primary_context_id','auto_approve','expires'];
    protected $validations = ['share_key_id' => 'max:32|string',
                            'primary_consumer_key' => 'max:255|string',
                            'primary_context_id' => 'max:255|string',
                            'auto_approve' => 'integer|required',
                            'expires' => 'required|date'];


}