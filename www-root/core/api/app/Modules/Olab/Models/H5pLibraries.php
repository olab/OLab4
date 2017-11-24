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
 * @property string $name
 * @property string $title
 * @property integer $major_version
 * @property integer $minor_version
 * @property integer $patch_version
 * @property integer $runnable
 * @property integer $restricted
 * @property integer $fullscreen
 * @property string $embed_types
 * @property string $preloaded_js
 * @property string $preloaded_css
 * @property string $drop_library_css
 * @property string $semantics
 * @property string $tutorial_url
 */

class H5pLibraries extends BaseModel {

    protected $table = 'h5p_libraries';
    protected $fillable = ['name','title','major_version','minor_version','patch_version','runnable','restricted','fullscreen','embed_types','preloaded_js','preloaded_css','drop_library_css','semantics','tutorial_url'];
    protected $validations = ['name' => 'max:127|string',
                            'title' => 'max:255|string',
                            'major_version' => 'integer|min:0|required',
                            'minor_version' => 'integer|min:0|required',
                            'patch_version' => 'integer|min:0|required',
                            'runnable' => 'integer|min:0|required',
                            'restricted' => 'integer|min:0|required',
                            'fullscreen' => 'integer|min:0|required',
                            'embed_types' => 'max:255|string',
                            'preloaded_js' => 'string',
                            'preloaded_css' => 'string',
                            'drop_library_css' => 'string',
                            'semantics' => 'string',
                            'tutorial_url' => 'max:1023|string'];


}