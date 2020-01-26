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
 * @property integer $id_patient
 * @property string $path
 * @property string $patient_condition
 * @property string $deactivateNode
 * @property integer $whose_id
 * @property string $whose
 * @property integer $scenario_id
 */

class PatientSessions extends BaseModel {

    protected $table = 'patient_sessions';
    protected $fillable = ['id_patient','path','patient_condition','deactivateNode','whose_id','whose','scenario_id'];
    protected $validations = ['id_patient' => 'exists:patient,id|integer|required',
                            'path' => 'string',
                            'patient_condition' => 'string',
                            'deactivateNode' => 'string',
                            'whose_id' => 'integer',
                            'whose' => '',
                            'scenario_id' => 'exists:webinars,id|integer|required'];

    public function Patient() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Patient');
    }
    public function Webinars() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Webinars');
    }

}