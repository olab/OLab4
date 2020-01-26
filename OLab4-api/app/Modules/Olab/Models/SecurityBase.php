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
use Entrada\Modules\Olab\Models\PolymorphicModel;

class SecurityBase extends PolymorphicModel {

    //const ACL_READ_ACCESS = 'R';
    //const ACL_AUTHORABLE_ACCESS = 'W';
    //const ACL_EXECUTE_ACCESS = 'X';
    //const ACL_DELETE_ACCESS = 'D';
    //const ACL_NO_ACCESS = '';
    //const ACL_ALL_ACCESS = 'RWXD';

    // disable automatic timestamps
    public $timestamps = false;
    protected $fillable = ['name','imageable_id','imageable_type','acl'];
    protected $validations = ['name' => 'max:45|string',
                            'imageable_id' => 'integer|required',
                            'imageable_type' => 'max:45|string',
                            'acl' => 'max:45|string'];

}