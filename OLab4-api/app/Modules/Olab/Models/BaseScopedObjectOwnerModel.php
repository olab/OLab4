<?php
/**
 * OLab [ http://openlabyrinth.ca/ ]
 *
 * OLab is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OLab is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OLab.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A model for OLab maps.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Models;

use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Illuminate\Support\Facades\Log;

class BaseScopedObjectOwnerModel extends BaseModel
{
    public $timestamps = true;

    const RESERVED_ID = 1;

    protected $fillable = ['name','description'];
    protected $validations = ['name' => 'max:45|string',
                            'description' => 'max:45|string'];

  public function toArray() {
    
    $aObj = parent::toArray();

    OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_QUESTIONS, 'questions');
    OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_CONSTANTS, 'constants');
    OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_COUNTERS,  'counters');
    OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_FILES,     'files');
    OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_SCRIPTS,   'scripts');
    OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_THEMES,    'themes');

    OLabUtilities::safe_rename( $aObj, "created_at",    'createdAt');
    OLabUtilities::safe_rename( $aObj, "updated_At",    'updatedAt');

    return $aObj;

  }

  /* relationship eager-loading methods */

  public function scopeThemes( $query )  {
      return $query->with( 'Themes' );
  }

  public function scopeConstants( $query )  {
      return $query->with( 'Constants' );
  }

  public function scopeScripts( $query )  {
      return $query->with( [ 'Scripts' => function ( $query ) {
          $query->select( 'system_scripts.id',
                          'system_scripts.name',
                          'system_scripts.source',
                          'system_scripts.is_raw',
                          'system_scripts.order',
                          'system_scripts.imageable_id');
      } ] );
  }

  public function scopeCounters( $query ) {
      return $query->with( [ 'Counters' => function ( $query ) {
          $query->select( 'system_counters.id',
                          'system_counters.name',
                          'system_counters.description',
                          'system_counters.start_value as start_value',
                          'system_counters.value',
                          'system_counters.is_system',
                          'system_counters.imageable_id',
                          'system_counters.imageable_type' );
      } ] );
  }

  public function scopeCounterActions( $query )  {
      return $query->with('CounterActions');
  }

  public function scopeFiles( $query )  {
      return $query->with( 'Files' )
                    ->orderBy( 'id', 'desc' );
  }

  public function scopeQuestions( $query ) {
      return $query->with( [ ScopedObjectManager::RELATION_NAME_QUESTIONS => function( $query ) { $query->select('id', 'value'); } ] )
                    ->with( [ 'Questions.QuestionTypes' => function( $query ) { $query->select('id', 'value'); } ] )
                    ->with( [ 'Questions.QuestionResponses' => function( $query ) { $query->orderBy( 'order', 'ASC' ); } ] );
  }

  /* relationship definitions */

  public function Themes() {
    return $this->morphMany('Entrada\Modules\Olab\Models\Themes', 'imageable');
  }

  public function Constants() {
    return $this->morphMany('Entrada\Modules\Olab\Models\Constants', 'imageable');
  }

  public function Scripts() {
    return $this->morphMany('Entrada\Modules\Olab\Models\Scripts', 'imageable');
  }

  public function Files() {
    return $this->morphMany('Entrada\Modules\Olab\Models\Files', 'imageable');
  }

  public function Questions() {
    return $this->morphMany('Entrada\Modules\Olab\Models\Questions', 'imageable');
  }

  public function Counters() {
    return $this->morphMany('Entrada\Modules\Olab\Models\Counters', 'imageable');
  }

  public function CounterActions() {
    return $this->morphMany('Entrada\Modules\Olab\Models\CounterActions', 'imageable');
  }

}