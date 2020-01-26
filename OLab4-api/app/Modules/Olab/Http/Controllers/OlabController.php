<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\SecurityContext;
use Entrada\Modules\Olab\Classes\OlabAccessDeniedException;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\Globals;
use Entrada\Modules\Olab\Models\Courses;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;
use Entrada\Modules\Olab\Models\SecurityBase;
use \Ds\Map;

class OlabController extends Controller
{
    protected $user_id = null;
    public $oPostData = null;
    protected $oAccessControl;

    /**
     * Common initialization routine
     * @param Request $request 
     */
    protected function initialize( Request $request = null ) {
        
        $user = HostSystemApi::getUserInfo();
        $this->user_id = $user['id'];

        if ( $request != null ) {
            $this->oPostData = new PostDataHandler( $request ); 
        }
    }

    protected function test_access( $oObj, $access_acl ) {
        
        $type = OLabUtilities::get_object_type( $oObj );

        // test access control on object
        $oAccessControl = AccessControlBase::classFactory( $oObj );

        if ( AccessControlBase::containsACLFlag( $access_acl, AccessControlBase::ACL_READ_ACCESS ) ) {
            if ( !$oAccessControl->isListable( $oObj->id )) {
                throw new OlabAccessDeniedException("$type = $oObj->id" );
            }
        }

        if ( AccessControlBase::containsACLFlag( $access_acl, AccessControlBase::ACL_DELETE_ACCESS ) ) {
            if ( !$oAccessControl->isDeletable( $oObj->id )) {
                throw new OlabAccessDeniedException("$type = $oObj->id" );
            }
        }

        if ( AccessControlBase::containsACLFlag( $access_acl, AccessControlBase::ACL_EXECUTE_ACCESS ) ) {
            if ( !$oAccessControl->isExecutable( $oObj->id )) {
                throw new OlabAccessDeniedException("$type = $oObj->id" );
            }
        }

        if ( AccessControlBase::containsACLFlag( $access_acl, AccessControlBase::ACL_AUTHORABLE_ACCESS ) ) {
            if ( !$oAccessControl->isWriteable( $oObj->id )) {
                throw new OlabAccessDeniedException("$type = $oObj->id" );
            }
        }

    }

    /**
     * Get map and test for access
     * @param mixed $id 
     */
    protected function get_map( $id, $acl = AccessControlBase::ACL_NO_ACCESS ) {

        // if not a model object, assume it's a id, so we get it from database
        if ( !OLabUtilities::is_of_type( $id, "BaseModel" ) )
        {
            // get the map, including related entities
            $oObj = Maps::active()->Abbreviated()->WithObjects()->At( intval( $id ) );
            if ( $oObj == null ) {
                throw new OlabObjectNotFoundException("Map", $id );
            }
        }

        // throws exception, if user/role has no access
        if ( $acl != AccessControlBase::ACL_NO_ACCESS ) {
            $this->test_access( $oObj, $acl );
        }

        return $oObj;
    }

    /**
     * Get node and test for access
     * @param mixed $id 
     */
    protected function get_node( Maps $oMap, &$id, $acl = AccessControlBase::ACL_NO_ACCESS ) {

        $oObj = null;

        // if not a model object, assume it's a id, so we get it from database
        if ( !OLabUtilities::is_of_type( $id, "BaseModel" ) )
        {
            $oObj = MapNodes::At( intval( $id ) );
            if ( $oObj == null ) {
                throw new OlabObjectNotFoundException("MapNode", $id );
            }
        }
        else {
            $oObj = $id;
        }

        // throws exception, if user/role has no access to owning map
        if ( $acl != AccessControlBase::ACL_NO_ACCESS ) {
            $this->test_access( $oMap, $acl );
        }

        return $oObj;
    }

    protected function get_counter( int $id, $acl = AccessControlBase::ACL_NO_ACCESS ) {
        
        $oObj = Counters::find( $id );

        if ( $oObj == null ) {
            throw new OlabObjectNotFoundException("Counter", $id );
        }

        // throws exception, if user/role has no access
        if ( $acl != AccessControlBase::ACL_NO_ACCESS ) {
            $this->test_access( $oObj, $acl );
        }

        return $oObj;
    }

    protected function write_object( &$oObj ) {
        
        $this->test_access( $oObj, AccessControlBase::ACL_AUTHORABLE_ACCESS );
        $oObj->save();

    }
}