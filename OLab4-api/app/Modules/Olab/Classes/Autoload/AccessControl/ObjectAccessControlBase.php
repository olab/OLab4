<?php

namespace Entrada\Modules\Olab\Classes\Autoload\AccessControl;

use Illuminate\Support\Facades\Log;
use \Exception;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Models\SecurityBase;
use Entrada\Modules\Olab\Models\UserSecurity;
use Entrada\Modules\Olab\Models\RoleSecurity;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use \Ds\Map;

/**
 * CollectionAccessControlBase short summary.
 *
 * CollectionAccessControlBase description.
 *
 * @version 1.0
 * @author wirunc
 */
abstract class ObjectAccessControlBase extends AccessControlBase
{
    // the securable object
    protected $oObject;
    protected $oAccessControlParentObject;

    public function __construct( $type, $oObject, $oParentObject ) {

        $this->oObject = $oObject;
        if ( $oParentObject != null ) {
            $this->oAccessControlParentObject = AccessControlBase::classFactory( $oParentObject );
        }
        
        parent::__construct( $type );
    }
          
    protected function getSecurableObjects() {
        $items = array();
        $items[] = $this->oObject;
        return $items;
    }

    /**
     * Apply user-specific ACL's to object list
     */
    protected function applyUserAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $name = HostSystemApi::getUserLogin();

        $acls = $this->AclNamedUserNamedTypeNamedId( $name, $this->object_type, $this->oObject->id );
        foreach ( $acls as $acl ) {
            $this->mapAcl[self::ObjectId($acl)] = self::Acl( $acl );
            Log::debug( "$name user, type = $this->object_type, id = " . self::ObjectId( $acl ) . ", acl = " . self::Acl( $acl ) );
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        return parent::applyUserAcls();
    }

    /**
     * Apply role-specific ACL's to object list
     */
    protected function applyRoleAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $name = HostSystemApi::getUserRole();

        $acls = $this->AclNamedRoleNamedTypeNamedId( $name, $this->object_type, $this->oObject->id );
        foreach ( $acls as $acl ) {
            $this->mapAcl[self::ObjectId($acl)] = self::Acl( $acl );
            Log::debug( "$name role, type = $this->object_type, id = " . self::ObjectId( $acl ) . ", acl = " . self::Acl( $acl ) );
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        return parent::applyRoleAcls();

    }

    /**
     * Test if allowed to list specified object
     * @param mixed $id
     */
    public function isListable( $id ) {
        $id = (int)$id;
        if ( $this->mapAcl->hasKey( $id ) ) {
            $acl = $this->mapAcl->get( $id );
            return self::containsACLFlag( $acl, AccessControlBase::ACL_READ_ACCESS );
        }
        return false;
    }

    /**
     * Tests if allowed to play specified object
     * @param mixed $id
     */
    public function isExecutable( $id ) {
        $id = (int)$id;
        if ( $this->mapAcl->hasKey( $id ) ) {
            $acl = $this->mapAcl->get( $id );
            return self::containsACLFlag( $acl, AccessControlBase::ACL_EXECUTE_ACCESS );
        }
        return false;
    }

    /**
     * Tests if allowed to write/author specified object
     * @param mixed $id
     */
    public function isDeletable( $id ) {
        $id = (int)$id;
        if ( $this->mapAcl->hasKey( $id ) ) {
            $acl = $this->mapAcl->get( $id );
            return self::containsACLFlag( $acl, AccessControlBase::ACL_DELETE_ACCESS );
        }
        return false;
    }

    /**
     * Tests if allowed to write/author specified object
     * @param mixed $id
     */
    public function isWriteable( $id ) {
        $id = (int)$id;
        if ( $this->mapAcl->hasKey( $id ) ) {
            $acl = $this->mapAcl->get( $id );
            return self::containsACLFlag( $acl, AccessControlBase::ACL_AUTHORABLE_ACCESS );
        }
        return false;
    }


}