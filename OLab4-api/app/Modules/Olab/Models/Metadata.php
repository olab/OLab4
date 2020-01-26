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
 * @property string $name
 * @property string $model
 * @property string $type
 * @property string $label
 * @property string $comment
 * @property string $cardinality
 * @property string $options
 * @property string $guid
 * @property string $state
 */

class Metadata extends BaseModel {

    protected $table = 'metadata';
    protected $fillable = ['name','model','type','label','comment','cardinality','options','guid','state'];
    protected $validations = ['name' => 'max:50|string',
                            'model' => 'max:50|string',
                            'type' => 'max:50|string',
                            'label' => 'max:100|string',
                            'comment' => 'max:500|string',
                            'cardinality' => 'max:10|string',
                            'options' => 'max:5000|string',
                            'guid' => 'max:50|string',
                            'state' => 'integer'];


}