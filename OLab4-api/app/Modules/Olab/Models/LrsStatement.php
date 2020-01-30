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
 * @property integer $lrs_id
 * @property integer $statement_id
 * @property string $status
 * @property integer $created_at
 * @property integer $updated_at
 */

class LrsStatement extends BaseModel {

    protected $table = 'lrs_statement';
    protected $fillable = ['lrs_id','statement_id','status'];
    protected $validations = ['lrs_id' => 'exists:lrs,id|integer|min:0|required',
                              'statement_id' => 'exists:statements,id|integer|min:0|required',
                              'status' => 'integer|min:0|required'];
    const STATUS_NEW = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    public static $statuses = array(
        self::STATUS_NEW => 'New',
        self::STATUS_SUCCESS => 'Successfully sent to LRS',
        self::STATUS_FAIL => 'Failed',
    );

    public $timestamps = true;

    /**
     * At scope (should be called last in query chain)
     * @param mixed $query
     * @return mixed
     */
    public function scopeAt( $query, $id ) {
        return $query->where( 'id', '=', $id )
                     ->first();
    }

    public function scopeNew( $query, $lrs_id ) { 
        return $query->where( 'status', '=', self::STATUS_NEW )
                     ->where( 'lrs_id', '=', $lrs_id );
    }

    public function scopeCount( $query ) {
        $value = $query->where( 'status', '=', self::STATUS_FAIL )
                       ->get()
                       ->count();
        return $value;
    }

    public function Lrs() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Lrs', 'lrs_id');
    }

    public function Statement() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Statements', 'statement_id' );
    }

}