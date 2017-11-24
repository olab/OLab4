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
 * @property string $name
 * @property string $description
 */

class Servers extends BaseModel {

  const LOCAL_SERVER_ID = 1;

  protected $table = 'servers';
  protected $fillable = ['name','description'];
  protected $validations = ['name' => 'max:45|string',
                          'description' => 'max:100|string'];

  public function scopeAt( $query, $id ) {

    // if no id passed in, assume local server
    if ( $id == null ) {
      $id = self::LOCAL_SERVER_ID;
    }

    return $query->where( 'id', '=', $id )
                 ->with( 'Constants' )
                 ->with( 'Files' )
                 ->with( 'Questions' )
                 ->with( 'Counters' )
                 ->first();
  }

  /**
   * Get constants
   * @return \Illuminate\Database\Eloquent\Relations\hasMany
   */
  public function Constants() {
    return $this->morphMany('App\Modules\Olab\Models\Constants', 'imageable');
  }

  /**
   * Get files
   * @return \Illuminate\Database\Eloquent\Relations\hasMany
   */
  public function Files() {
    return $this->morphMany('App\Modules\Olab\Models\Files', 'imageable');
  }

  /**
   * Get questions
   * @return \Illuminate\Database\Eloquent\Relations\hasMany
   */
  public function Questions() {
    return $this->morphMany('App\Modules\Olab\Models\Questions', 'imageable');
  }

  /**
   * Get counters
   * @return \Illuminate\Database\Eloquent\Relations\hasMany
   */
  public function Counters() {
    return $this->morphMany('App\Modules\Olab\Models\Counters', 'imageable');
  }

  /**
   * Get map counters actions
   * @return \Illuminate\Database\Eloquent\Relations\hasMany
   */
  public function CounterActions() {
    return $this->morphMany('App\Modules\Olab\Models\CounterActions', 'imageable');
  }

}