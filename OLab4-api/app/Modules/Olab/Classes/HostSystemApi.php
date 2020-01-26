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

namespace Entrada\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Classes\OlabServerException;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Http\Controllers\Controller;
use Entrada\Models\Auth\User;

/**
* HostSystemApi counter manager.
*
 * Provides an interface to the host system.
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
   *   e.g. 'entrada_absolute' => '/var/www/vhosts/OLab4/www-root',
   */
  public static function getFileRoot() {
    if ( strlen( ENTRADA_ABSOLUTE ) == 0 )
      throw new OlabServerException( "ENTRADA_ABSOLUTE is not defined in config.ini.php" );
    return ENTRADA_ABSOLUTE;
  }

  /**
   * Get web root url
   *   e.g. 'entrada_url' => 'http://olab4.localhost/apidev',
   */
  public static function getRootUrl() {
    if ( strlen( ENTRADA_URL ) == 0 )
      throw new OlabServerException( "ENTRADA_URL is not defined in config.ini.php" );
    return ENTRADA_URL;
  }

  /**
   * Get web relative path
   *   e.g. 'entrada_relative' => '/apidev',
   */
  public static function getRelativePath() {
    return ENTRADA_RELATIVE;
  }

  /**
   * Get hosting system user id
   * @return integer
   */
  public static function getUserId() {
    global $ENTRADA_USER;
    return (int)$ENTRADA_USER->getID();
  }

  /**
   * Get the javascript autoloaded root path
   * @return string
   */
  public static function getScriptAutoloadRootPath() {
    return HostSystemApi::getFileRoot() . "/javascript/olab/autoload";
  }

  /**
   * Get the rest services autoloaded root path
   *  e.g. 'entrada_api_absolute' => '/var/www/vhosts/OLab4-api',
   * @return string
   */
  public static function getAPIFileRoot() {
    if ( strlen( ENTRADA_API_ABSOLUTE ) == 0 )
      throw new OlabServerException( "ENTRADA_API_ABSOLUTE is not defined in config.ini.php" );
    return ENTRADA_API_ABSOLUTE;
  }

  /**
   * Get import directory root
   * @return string
   */
  public static function getImportRoot() {
    return HostSystemApi::getFileRoot() . DIRECTORY_SEPARATOR . "core/storage/olab/import";
  }

  public static function getUserList() {
    
    // TODO: add role security here who can execute this.
    $users = DB::select("SELECT id, CONCAT(firstname, ' ' ,lastname ) AS name FROM " . AUTH_DATABASE . ".user_data;");
    return $users;
  }

  public static function getRoleList() {
    
    // TODO: add role security here who can execute this.
    $roles = DB::select("SELECT 0 as id, CONCAT( role_name, ':', group_name ) as name FROM " . AUTH_DATABASE . 
                        ".system_roles sr, " . AUTH_DATABASE . 
                        ".system_groups sg where groups_id = sg.id;");
    return $roles;
  }

  public static function getUserInfo( $user_id = null ) {

    global $ENTRADA_USER;
    //$tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $user_id . ")" );
    $user = [];

    try
    {

      if ( $user_id == null ) {

        $user['id'] = $ENTRADA_USER->getID();
        $user['username'] = $ENTRADA_USER->getUsername();
        $user['name'] = $ENTRADA_USER->getName();
        $user['email'] = $ENTRADA_USER->getEmail();

      }      
      else {
        
        $oUser = User::findOrFail($user_id);

        if ( $oUser != null ) {

          $user['id'] = $oUser->id;
          $user['username'] = $oUser->username;
          $user['name'] = $oUser->notes;
          $user['email'] = $ENTRADA_USER->getEmail();

        }
        
      }

      return $user;

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

  }

  /**
   * Get printable user info
   * @param $user_id|null 
   * @return string
   */
  public static function getUser( $user_id = null ) {

    global $ENTRADA_USER;
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $user_id . ")" );

    try
    {
      if ( $user_id == null ) {
        return $ENTRADA_USER->getUsername() . " (" . $ENTRADA_USER->getName() . ")";
      }      

      $oUser = User::findOrFail($user_id);

      if ( $oUser != null ) {
        return $oUser->username . " (" . $oUser->notes . ")";        
      }

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return "???";

  }

  /**
   * Get printable user info
   * @param $user_id|null 
   * @return string
   */
  public static function getUserEmail( $user_id = null ) {

    global $ENTRADA_USER;
    //$tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $user_id . ")" );

    try
    {
      if ( $user_id == null ) {
        return $ENTRADA_USER->getEmail();
      }      

      $oUser = User::findOrFail($user_id);

      if ( $oUser != null ) {
        return $oUser->email;        
      }

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return "???";

  }

  /**
   * Get hosting system login name for current or user via user_id
   * @param $user_id|null 
   * @return string
   */
  public static function getUserLogin( $user_id = null ) {

    global $ENTRADA_USER;
    //$tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $user_id . ")" );

    try
    {
      if ( $user_id == null ) {
        return $ENTRADA_USER->getUsername();
      }      

      $oUser = User::findOrFail($user_id);

      if ( $oUser != null ) {
        return $oUser->username;        
      }

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return "???";

  }

  /**
   * Get id of user's current role
   * @return string
   */
  public static function getUserRole() {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    global $ENTRADA_USER;

    try
    {
      $userOrganizations = $ENTRADA_USER->getAllOrganisations();
      $organizationId = 0;

      // get OLab-specific organization
      foreach( $userOrganizations as $key => $value ) {
        if ( $value == OlabConstants::OLAB_ORGANIZATION_NAME ) {
          $organizationId = $key;
          break;
        }
      }

      if ( $organizationId == 0 )
        throw new OlabServerException( "Unable to find user '" . $ENTRADA_USER->getUsername() . "' organization.");

      // get all the group/roles the user belongs to
      $organizationGroupRoles = $ENTRADA_USER->getOrganisationGroupRole();

      if ( sizeof( $organizationGroupRoles ) == 0 )
        throw new OlabServerException( "Unable to find user '" . $ENTRADA_USER->getUsername() . "' organization group/roles.");

      // get OLab-organization specific group/role
      $oGroupRoles = $organizationGroupRoles[ $organizationId ];

      // loop through and get the OLab-specific group/role (group = OLAB_GROUP_NAME)
      foreach( $oGroupRoles as $key => $value ) {
          $group = $value["group"];
          $role = $value["role"];
          return $group . ":" . $role;
        }

      throw new OlabServerException( "Unable to find user '" . $ENTRADA_USER->getUsername() . "' OLab group/role.");
      
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
    }

    return "";
  }

  /**
   * Test if user is an admin
   */
  public static function isAdmin() {
    
    $parts = explode( ":", self::getUserRole() );
    if ( count( $parts ) == 2 ) {
      if ( ( strcasecmp( $parts[0], OlabConstants::OLAB_GROUP_NAME ) == 0 )&&
           ( ( strcasecmp( $parts[1], "Administrator" ) ) || ( strcasecmp( $parts[1], "Superuser" ) == 0 ) ) )
        return true;
    }

    return false;
  }

}
