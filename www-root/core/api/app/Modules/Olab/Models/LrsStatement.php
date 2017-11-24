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
 * @property integer $lrs_id
 * @property integer $statement_id
 * @property string $status
 * @property integer $created_at
 * @property integer $updated_at
 */

class LrsStatement extends BaseModel {

    protected $table = 'lrs_statement';
    protected $fillable = ['lrs_id','statement_id','status'];
    protected $validations = ['lrs_id' => 'exists:lrs,id|integer|min:0|required',
                            'statement_id' => 'exists:statements,id|integer|min:0|required',
                            'status' => 'integer|min:0|required'];

    public function Lrs() {
        return $this->belongsTo('App\Modules\Olab\Models\Lrs');
    }
    public function Statements() {
        return $this->belongsTo('App\Modules\Olab\Models\Statements');
    }

}