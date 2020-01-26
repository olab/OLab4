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
 * @property string $t
 * @property string $s
 * @property string $p
 * @property string $o
 * @property string $o_lang_dt
 * @property string $o_comp
 * @property string $s_type
 * @property string $o_type
 * @property string $misc
 */

class SparqlTriple extends BaseModel {

    protected $table = 'sparql_triple';
    protected $fillable = ['t','s','p','o','o_lang_dt','o_comp','s_type','o_type','misc'];
    protected $validations = ['t' => 'integer|min:0|required',
                            's' => 'integer|min:0|required',
                            'p' => 'integer|min:0|required',
                            'o' => 'integer|min:0|required',
                            'o_lang_dt' => 'integer|min:0|required',
                            'o_comp' => 'required',
                            's_type' => 'integer|required',
                            'o_type' => 'integer|required',
                            'misc' => 'integer|required'];


}