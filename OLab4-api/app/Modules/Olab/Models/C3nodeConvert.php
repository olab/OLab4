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
use Illuminate\Support\Facades\Log;

/**
 * @property integer $node_id
 * @property string $title
 * @property integer $new_node_id
 */

class C3nodeConvert extends BaseModel {

    protected $table = 'C3nodeConvert';
    protected $fillable = ['node_id','title','new_node_id'];
    protected $validations = ['node_id' => 'integer',
                            'title' => 'max:255|string',
                            'new_node_id' => 'integer'];


}