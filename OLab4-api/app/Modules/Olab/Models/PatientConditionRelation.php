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
 * @property integer $id_condition
 */

class PatientConditionRelation extends BaseModel {

    protected $table = 'patient_condition_relation';
    protected $fillable = ['id_patient','id_condition'];
    protected $validations = ['id_patient' => 'exists:patient,id|integer|required',
                            'id_condition' => 'exists:patient_condition,id|integer|required'];

    public function Patient() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Patient');
    }
    public function PatientCondition() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\PatientCondition');
    }

}