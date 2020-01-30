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
 * @property integer $section_id
 * @property integer $node_id
 * @property integer $order
 * @property string $node_type
 */

class MapNodeSectionNodes extends BaseModel {

    protected $table = 'map_node_section_nodes';
    protected $fillable = ['section_id','node_id','order','node_type'];
    protected $validations = ['section_id' => 'exists:map_node_sections,id|integer|min:0|required',
                            'node_id' => 'exists:map_nodes,id|integer|min:0|required',
                            'order' => 'integer|required',
                            'node_type' => 'required'];

    public function MapNodeSections() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodeSections');
    }
    public function MapNodes() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodes');
    }

}