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
<a href="MapNodes.php">MapNodes.php</a>
*
* @author Organisation: Cumming School of Medicine, University of Calgary
* @author Developer: Corey Wirun (corey@cardinalcreek.ca)
* @copyright Copyright 2017 University of Calgary. All Rights Reserved.
*/
namespace Entrada\Modules\Olab\Models;

use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\Maps;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use XMLReader;
use DOMDocument;
use \Exception;

class MapTemplates extends Maps {

  public function scopeActive($query) {
    return $query->where('is_template', '1' )->version4();          
  }

  public function scopeById( $query, $id ) {
    return $query->where( 'id', '=', $id )->$this->Active();
  }

  public static function createFrom( $source ) {

    $instance = new self();        
    Maps::copyFrom( $source, $instance );
    $instance->is_template = 1;

    return $instance;
  }
}