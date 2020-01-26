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
 * @property integer $content_id
 * @property integer $user_id
 * @property integer $sub_content_id
 * @property string $data_id
 * @property string $data
 * @property string $preload
 * @property string $invalidate
 * @property string $updated_at
 */

class H5pContentsUserData extends BaseModel {

    protected $table = 'h5p_contents_user_data';
    protected $fillable = ['content_id','user_id','sub_content_id','data_id','data','preload','invalidate'];
    protected $validations = ['content_id' => 'integer|min:0|required',
                            'user_id' => 'integer|min:0|required',
                            'sub_content_id' => 'integer|min:0|required',
                            'data_id' => 'max:127|string',
                            'data' => 'string',
                            'preload' => 'integer|min:0|required',
                            'invalidate' => 'integer|min:0|required'];

    public function scopePreLoadedData( $query, $user_id, $content_id ) {
      return $query->where( [[ 'user_id', '=', $user_id ],
                             [ 'content_id', '=', $content_id ],
                             [ 'preload', '=', 1 ] ])
                   ->select('sub_content_id', 'data_id', 'data')
                   ->get();
    }
}