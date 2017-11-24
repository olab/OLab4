<?php
/**
 * OpenLabyrinth [ http://www.openlabyrinth.ca ]
 *
 * OpenLabyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenLabyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenLabyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A class to expose information from a hosting system
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace App\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\Olab\Models\MapNodeTypes;
use App\Modules\Olab\Models\QuestionTypes;
use App\Modules\Olab\Models\Constants;
use App\Modules\Olab\Models\Counters;
use App\Modules\Olab\Models\UserState;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use App\Http\Controllers\Controller;

/**
* CounterManager counter manager.
*
 * CounterManager description.
 *
 * @version 1.0
* @author wirunc
*/
class HostSystemApi
{
    public function __construct() {
    }

    /**
     * Updates any host system breadcrumb
     * @param mixed $url
     * @param mixed $title
     */
    public static function UpdateBreadCrumb( $url, $title ) {
        global $BREADCRUMB;
        $BREADCRUMB[]	= array("url" => $url, "title" => $title );
    }

    /**
     * Get physical site file path
     */
    public static function GetFileRoot() {
        return ENTRADA_ABSOLUTE;
    }

    /**
     * Get web root url
     */
    public static function GetRootUrl() {
      return ENTRADA_URL;
    }

    /**
    * Get web relative path
    */
    public static function GetRelativePath() {
      return ENTRADA_RELATIVE;
    }

    /**
     * Get hosting system user id
     * @return integer
     */
    public static function GetUserId() {
      global $ENTRADA_USER;
      return $ENTRADA_USER->getID();
    }

    /**
     * Get the javascript autoloaded root path
     * @return string
     */
    public static function GetScriptAutoloadRootPath() {
      return HostSystemApi::GetFileRoot() . "/javascript/olab/autoload";
    }

    /**
     * Get hosting system login name
     * @return string
     */
    public static function GetUserLogin() {
        global $ENTRADA_USER;
        return $ENTRADA_USER->getUsername();
    }

}
