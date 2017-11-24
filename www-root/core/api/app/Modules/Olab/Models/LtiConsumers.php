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
 * @property string $consumer_key
 * @property string $name
 * @property string $secret
 * @property string $lti_version
 * @property string $consumer_name
 * @property string $consumer_version
 * @property string $consumer_guid
 * @property string $css_path
 * @property string $protected
 * @property string $enabled
 * @property string $enable_from
 * @property string $enable_until
 * @property string $without_end_date
 * @property string $last_access
 * @property string $created
 * @property string $updated
 * @property string $role
 */

class LtiConsumers extends BaseModel {

    protected $table = 'lti_consumers';
    protected $fillable = ['consumer_key','name','secret','lti_version','consumer_name','consumer_version','consumer_guid','css_path','protected','enabled','enable_from','enable_until','without_end_date','last_access','created','updated','role'];
    protected $validations = ['consumer_key' => 'max:255|string',
                            'name' => 'max:45|string',
                            'secret' => 'max:32|string',
                            'lti_version' => 'max:12|string',
                            'consumer_name' => 'max:255|string',
                            'consumer_version' => 'max:255|string',
                            'consumer_guid' => 'max:255|string',
                            'css_path' => 'max:255|string',
                            'protected' => 'integer|required',
                            'enabled' => 'integer|required',
                            'enable_from' => 'date',
                            'enable_until' => 'date',
                            'without_end_date' => 'integer',
                            'last_access' => 'date',
                            'created' => 'required|date',
                            'updated' => 'required|date',
                            'role' => 'integer'];

    public function LtiContexts() {
        return $this->hasMany('App\Modules\Olab\Models\LtiContexts');
    }
    public function LtiNonces() {
        return $this->hasMany('App\Modules\Olab\Models\LtiNonces');
    }

}