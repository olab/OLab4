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
 * @property string $created_at
 * @property string $updated_at
 * @property integer $user_id
 * @property string $title
 * @property integer $library_id
 * @property string $parameters
 * @property string $filtered
 * @property string $slug
 * @property string $embed_type
 * @property integer $disable
 * @property string $content_type
 * @property string $author
 * @property string $license
 * @property string $keywords
 * @property string $description
 */

class H5pContents extends BaseModel {

    protected $table = 'h5p_contents';
    protected $fillable = ['user_id','title','library_id','parameters','filtered','slug','embed_type','disable','content_type','author','license','keywords','description'];
    protected $validations = ['user_id' => 'integer|min:0|required',
                            'title' => 'max:255|string',
                            'library_id' => 'integer|min:0|required',
                            'parameters' => 'string',
                            'filtered' => 'string',
                            'slug' => 'max:127|string',
                            'embed_type' => 'max:127|string',
                            'disable' => 'integer|min:0|required',
                            'content_type' => 'max:127|string',
                            'author' => 'max:127|string',
                            'license' => 'max:7|string',
                            'keywords' => 'string',
                            'description' => 'string'];


}