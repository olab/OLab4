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
 * @property integer $content_id
 * @property integer $library_id
 * @property string $dependency_type
 * @property string $weight
 * @property string $drop_css
 */

class H5pContentsLibraries extends BaseModel {

    protected $table = 'h5p_contents_libraries';
    protected $fillable = ['content_id','library_id','dependency_type','weight','drop_css'];
    protected $validations = ['content_id' => 'integer|min:0|required',
                            'library_id' => 'integer|min:0|required',
                            'dependency_type' => 'max:31|string',
                            'weight' => 'integer|min:0|required',
                            'drop_css' => 'integer|min:0|required'];


}