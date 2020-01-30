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
 * @property integer $user_id
 * @property integer $created_at
 * @property string $type
 * @property string $sub_type
 * @property integer $content_id
 * @property string $content_title
 * @property string $library_name
 * @property string $library_version
 */

class H5pEvents extends BaseModel {

    protected $table = 'h5p_events';
    protected $fillable = ['user_id','type','sub_type','content_id','content_title','library_name','library_version'];
    protected $validations = ['user_id' => 'integer|min:0|required',
                            'type' => 'max:63|string',
                            'sub_type' => 'max:63|string',
                            'content_id' => 'integer|min:0|required',
                            'content_title' => 'max:255|string',
                            'library_name' => 'max:127|string',
                            'library_version' => 'max:31|string'];


}