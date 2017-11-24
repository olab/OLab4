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
 * @property integer $map_id
 * @property integer $node_id_1
 * @property integer $node_id_2
 * @property integer $image_id
 * @property string $text
 * @property integer $order
 * @property integer $probability
 * @property string $hidden
 */

class MapNodeLinks extends BaseModel {

    protected $table = 'map_node_links';
    protected $fillable = ['map_id','node_id_1','node_id_2','image_id','text','order','probability','hidden'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                            'node_id_1' => 'exists:map_nodes,id|integer|min:0|required',
                            'node_id_2' => 'exists:map_nodes,id|integer|min:0|required',
                            'image_id' => 'integer',
                            'text' => 'max:500|string',
                            'order' => 'integer',
                            'probability' => 'integer',
                            'hidden' => 'integer'];

    public function Maps() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }
    public function MapNodes() {
        return $this->belongsTo('App\Modules\Olab\Models\MapNodes');
    }
    public function SourceNode() {
        return $this->belongsTo('App\Modules\Olab\Models\MapNodes', 'node_id_1' );
    }
    public function DestinationNode() {
        return $this->belongsTo('App\Modules\Olab\Models\MapNodes', 'node_id_2' );
    }

}