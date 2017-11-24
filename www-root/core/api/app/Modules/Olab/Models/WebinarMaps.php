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
 * @property integer $webinar_id
 * @property string $which
 * @property integer $reference_id
 * @property integer $step
 * @property string $cumulative
 */

class WebinarMaps extends BaseModel {

    protected $table = 'webinar_maps';
    protected $fillable = ['webinar_id','which','reference_id','step','cumulative'];
    protected $validations = ['webinar_id' => 'integer|required',
                            'which' => 'required',
                            'reference_id' => 'integer|required',
                            'step' => 'exists:webinar_steps,id|integer|required',
                            'cumulative' => 'integer|required'];

    public function WebinarSteps() {
        return $this->belongsTo('App\Modules\Olab\Models\WebinarSteps');
    }
    public function WebinarSteps() {
        return $this->belongsTo('App\Modules\Olab\Models\WebinarSteps');
    }

}