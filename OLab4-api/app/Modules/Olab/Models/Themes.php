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

use \Exception;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\Maps;
use Illuminate\Support\Facades\Log;

/**
* @property integer $id
* @property integer $map_id
* @property string header_text
* @property string footer_text
*/
class Themes extends PolymorphicModel {

    protected $table = 'system_themes';
    protected $fillable = ['map_id','header_text','footer_text','left_text','right_text',
                           'imageable_id','imageable_type'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                            'header_text' => 'string',
                            'left_text' => 'string',
                            'right_text' => 'string',
                            'footer_text' => 'string',
                            'imageable_id' => 'integer|required',
                            'imageable_type' => 'max:45|string'];

    public static function Create( $oSourceObj ) {

        // get class name to ensure the source is supported
        $sClassName = get_class( $oSourceObj );
        $parts = explode('\\', $sClassName );
        $sClassName = array_pop( $parts );

        // we can only create an object from Maps
        // return if it's not what we expect
        if ( $sClassName != Maps::IMAGEABLE_TYPE )
            throw new Exception("Unknown source type '" . $sClassName . ".");

        $instance = new self();

        $instance->map_id             = $oSourceObj->id                ;
        $instance->header_text        = "<b>[[CONST:\"MapName\"]]/[[CONST:\"NodeName\"]]</b><hr/>";
        $instance->footer_text        = "[[LINKS]]<hr/><small><b>MapId:</b> [[CONST:\"MapId\"]]; <b>NodeID:</b> [[CONST:\"NodeId\"]]; <b>Time:</b> [[CONST:\"SystemTime\"]]</small>";

        return $instance;

    }



}
