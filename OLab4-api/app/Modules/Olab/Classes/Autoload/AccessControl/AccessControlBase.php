<?php

namespace Entrada\Modules\Olab\Classes\Autoload\AccessControl;

use Illuminate\Support\Facades\Log;
use \Exception;
use Entrada\Modules\Olab\Classes\Autoload\OlabAutoloadBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Models\SecurityBase;
use Entrada\Modules\Olab\Models\UserSecurity;
use Entrada\Modules\Olab\Models\RoleSecurity;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use \Ds\Map;

/**
 * Access control base class
 *
 * @version 1.0
 * @author wirunc
 */
abstract class AccessControlBase extends OlabAutoloadBase
{
    const ACL_READ_ACCESS = 'R';
    const ACL_AUTHORABLE_ACCESS = 'W';
    const ACL_EXECUTE_ACCESS = 'X';
    const ACL_DELETE_ACCESS = 'D';
    const ACL_NO_ACCESS = '';
    const ACL_ALL_ACCESS = 'RWXD';

    // note: ensure this namespace is correct
    const CLASS_NS = "\\Entrada\\Modules\\Olab\\Classes\\Autoload\\AccessControl\\";
    const PATH_TO_CLASSES = "AccessControl/";

    const ID_WILDCARD = 0;
    const NAME_WILDCARD = "*";

    // object type set by derived class
    protected $object_type;
    protected $object_id;
    protected $super_default_acl = AccessControlBase::ACL_NO_ACCESS;

    protected $mapAcl;

    protected static $role_acl_records = null;
    protected static $user_acl_records = null;

    protected $source_items;

    abstract protected function getSecurableObjects();

    public function __construct( $type ) {

        $this->object_type = $type;

        Log::debug( "creating ACL test for object $type." );

        // load role acl records, if there are none loaded
        if ( self::$role_acl_records == null ) {
            self::$role_acl_records = RoleSecurity::orderBy( 'imageable_id' )->get();
        }

        // load user acl records, if there are none loaded
        if ( self::$user_acl_records == null ) {
            self::$user_acl_records = UserSecurity::orderBy( 'imageable_id' )->get();
        }

        $this->mapAcl = new Map([]);
        $this->initialize();

    }

    protected function clearAcl() {
        $this->mapAcl->clear();
    }

    /**
     * Autoload wiki tag class by class name
     * @param mixed $class
     */
    private static function AutoLoadClass( $class ) {

        $filename = parent::GetAutoLoadBasePath() . "/" . self::PATH_TO_CLASSES . $class . ".php";
        Log::debug("looking to autoload class from file: '" . $filename . "'" );

        if (file_exists( $filename )) {
            require_once $filename;
            return true;
        }
        else {
            Log::debug("file: '" . $filename . "' does not exist" );
            return false;
        }
    }

    /**
     * Creates an instance of an access control class
     * @param mixed instance of object to evaluate
     * @return mixed Instance of the access control class
     * @exception Exception throws exception if can't find class
     */
    public static function classFactory( $object ) {

        if ( $object == null ) {
            return null;
        }

        $id = null;
        $sClassName = get_class( $object );

        $parts = explode('\\', $sClassName );
        $sClassName = array_pop( $parts );
        $object_type = $sClassName;

        // test if object is a collection
        if ( $sClassName === "Collection" ) {

            // test if any object in collection, meaning there's no access control to check
            if ( $object->count() === 0 ) {
                return null;
            }

            $sClassName = get_class( $object[0] );
            $parts = explode('\\', $sClassName );

            $sClassName = array_pop( $parts );
            $object_type = $object_type . "\\" . $sClassName;

            $sClassName = $sClassName . "Collection";
        }
        else {
            $id = $object->id;
        }

        $sClassName = $sClassName . "AccessControl";

        Log::debug( "Searching for autoload class " . $sClassName );

        // autoload the file containing the render class
        self::AutoLoadClass( $sClassName );

        // add namespace to class name
        $sClassName = self::CLASS_NS . $sClassName;

        // test if class exists (i.e. has been autoloaded)
        if ( class_exists( $sClassName, false )) {

            Log::debug( $sClassName . " class exists" );

            // instantiate class and pass back depending on if
            // an argument (an id) was passed in
            if ( $id == null ) {
                return new $sClassName( $object );
            }
            else {
                return new $sClassName( $object );
            }

        }

        throw new Exception( "Could not instantiate access control class for '" . $object_type  . "'" );
    }

    /**
     * loads the acls into cache
     */
    protected function initialize() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->clearAcl();

        // get list of securable objects and build acl map
        $this->buildAclMap( $this->getSecurableObjects() );

        $size = $this->mapAcl->count();
        Log::debug( "loaded $size acl's into cache for test against $this->object_type." );

        // apply role, user, default, super-default acls to securable object list.
        // (first acl 'hit' terminates evaluation
        if ( !$this->applyUserAcls() ) {
            if ( !$this->applyRoleAcls() ) {
                if ( !$this->applyUserDefaultAcls() ) {
                    if ( !$this->applyRoleDefaultAcls() ) {
                        $this->applySuperDefaultAcls( $this->super_default_acl );
                    }
                }
            }
        }
    }

    /* ACL record property accessors */

    /**
     * Get object type from record
     * @param mixed $acl
     * @return mixed
     */
    protected static function ObjectType ( $acl ) {
        return $acl->imageable_type;
    }

    /**
     * Get object id from record
     * @param mixed $acl
     * @return mixed
     */
    protected static function ObjectId ( $acl ) {
        return $acl->imageable_id;
    }

    /**
     * Get ACL field from record
     * @param mixed $acl
     * @return string
     */
    protected static function Acl( $acl ) {
        return $acl->acl;
    }

    /**
     * Get name field from record
     * @param mixed $acl
     * @return string
     */
    protected static function Name( $acl ) {
        return $acl->name;
    }

    /**
     * Test if ACL has requested access
     * @param mixed $acl
     * @param mixed $requested_acl
     * @return boolean
     */
    public static function containsACLFlag( $acl, $requested_acl ) {
        return ( strpos( $acl, $requested_acl ) !== false );
    }

    /* Named User/Role, Named Type, Wildcard Id methods */
    
    /**
     * Summary of AclNamedRoleNamedType
     * @param string $name Role name
     * @param string $object_type Object type name
     * @return array
     */
    protected function AclNamedRoleNamedTypeWildcardId( $name, $object_type ) {

        $items = $this->AclArrayNamedNameNamedTypeWildcardId( self::$role_acl_records, $name, $object_type );
        return $items;

    }

    /**
     * Summary of AclNamedUserNamedType
     * @param string $name User name
     * @param string $object_type Object type name
     * @return array
     */
    protected function AclNamedUserNamedTypeWildcardId( $name, $object_type ) {

        $items = $this->AclArrayNamedNameNamedTypeWildcardId( self::$user_acl_records, $name, $object_type );
        return $items;

    }

    private function AclArrayNamedNameNamedTypeWildcardId( $source, $name, $object_type ) {

        $items = array();

        foreach ($source as $record) {

            if ( ( strcasecmp( self::Name( $record ), $name ) == 0 ) &&
                 ( self::ObjectId( $record ) == self::ID_WILDCARD ) && 
                 ( self::ObjectType( $record ) == $object_type ) ) {
                $items[] = $record;
            }

        }

        return $items;

    }

    /* Named User/Role, Named Type, Named Id methods */

    /**
     * Summary of AclNamedRoleNamedTypeNamedId
     * @param string $name Role name
     * @param string $object_type Object type name
     * @param int $id Object id
     * @return array
     */
    protected function AclNamedRoleNamedTypeNamedId( $name, $object_type, $id = null ) {

        $items = $this->AclArrayNamedNameNamedTypeNamedId( self::$role_acl_records, $name, $object_type, $id );
        return $items;

    }

    /**
     * Summary of AclNamedUserNamedTypeNamedId
     * @param string $name User name
     * @param string $object_type Object type name
     * @param int $id Object id
     * @return array
     */
    protected function AclNamedUserNamedTypeNamedId( $name, $object_type, $id = null ) {

        $items = $this->AclArrayNamedNameNamedTypeNamedId( self::$user_acl_records, $name, $object_type, $id );
        return $items;

    }

    private function AclArrayNamedNameNamedTypeNamedId( $source, $name, $object_type, $id ) {

        $items = array();

        // if $id not null, look for explicit id, else look
        // for a non-wildcard id
        if ( $id != null ) {

            foreach ($source as $record) {

                if ( ( strcasecmp( self::Name( $record ), $name ) == 0 ) &&
                   ( self::ObjectId( $record ) == $id ) &&
                   ( self::ObjectType( $record ) == $object_type ) ) {
                    $items[] = $record;
                }

            }

        }
        else {
            
            foreach ($source as $record) {

                if ( ( strcasecmp( self::Name( $record ), $name ) == 0 ) &&
                   ( self::ObjectId( $record ) != self::ID_WILDCARD ) &&
                   ( self::ObjectType( $record ) == $object_type ) ) {
                    $items[] = $record;
                }

            }

        }

        return $items;

    }

    /* Named User/Role, Wildcard Type, Wildcard Id methods */

    /**
     * Summary of AclNamedUserWildcardTypeWildcardId
     * @param string $name User name
     * @return array
     */
    protected function AclNamedUserWildcardTypeWildcardId( $name ) {

        $items = $this->AclArrayWildcardTypeWildcardId( self::$user_acl_records, $name );
        return $items;
    }

    /**
     * Summary of AclNamedRoleWildcardTypeWildcardId
     * @param string $name Role name
     * @return array
     */
    protected function AclNamedRoleWildcardTypeWildcardId( $name ) {

        $items = $this->AclArrayWildcardTypeWildcardId( self::$role_acl_records, $name );
        return $items;
    }
    
    private function AclArrayWildcardTypeWildcardId( $source, $name ) {

        $items = array();

        foreach ($source as $record) {

            if ( ( strcasecmp( self::Name( $record ), $name ) == 0 ) &&
                ( self::ObjectId( $record ) == self::ID_WILDCARD ) &&
                ( self::ObjectType( $record ) == self::NAME_WILDCARD ) ) {
                $items[] = $record;
            }

        }

        return $items;

    }


    /**
     * Apply user-specific ACL's to object list
     */
    protected function applyUserAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $name = HostSystemApi::getUserLogin();

        $acls = $this->AclNamedUserNamedTypeNamedId( $name, $this->object_type );
        foreach ( $acls as $acl ) {
            $id = self::ObjectId($acl);
            Log::debug( "$name user, type = $this->object_type, id = $id, acl = " . self::Acl( $acl ) );
            $this->mapAcl[self::ObjectId($acl)] = self::Acl( $acl );
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        $acls = $this->AclNamedUserNamedTypeWildcardId( $name, $this->object_type );
        $keys = $this->mapAcl->keys();
        foreach ( $acls as $acl ) {
            Log::debug( "$name user, type = $this->object_type, id = *, acl = " . self::Acl( $acl ) );
            foreach ($keys as $key) {
                $this->mapAcl[$key] = self::Acl( $acl );
            }
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        $acls = $this->AclNamedUserWildcardTypeWildcardId( $name );
        $keys = $this->mapAcl->keys();
        foreach ( $acls as $acl ) {
            Log::debug( "$name user, type = *, acl = " . self::Acl( $acl ) );
            foreach ($keys as $key) {
                $this->mapAcl[$key] = self::Acl( $acl );
            }
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Apply role-specific ACL's to object list
     */
    protected function applyRoleAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $name = HostSystemApi::getUserRole();

        $acls = $this->AclNamedRoleNamedTypeNamedId( $name, $this->object_type );
        foreach ( $acls as $acl ) {
            $id = self::ObjectId($acl);
            Log::debug( "$name role, type = $this->object_type, id = $id, acl = " . self::Acl( $acl ) );
            $this->mapAcl[self::ObjectId($acl)] = self::Acl( $acl );
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        $acls = $this->AclNamedRoleNamedTypeWildcardId( $name, $this->object_type );
        $keys = $this->mapAcl->keys();
        foreach ( $acls as $acl ) {
            Log::debug( "$name role, type = $this->object_type, id = *, acl = " . self::Acl( $acl ) );
            foreach ($keys as $key) {
                $this->mapAcl[$key] = self::Acl( $acl );
            }
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        return false;

    }

    /**
     * Apply default ACL's to object list
     */
    protected function applyUserDefaultAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $name = HostSystemApi::getUserLogin();

        $acls = $this->AclNamedUserWildcardTypeWildcardId( $name );
        $keys = $this->mapAcl->keys();
        foreach ( $acls as $acl ) {
            Log::debug( "$name user default, type = *, id = *, acl = " . self::Acl( $acl ) );
            foreach ($keys as $key) {
                $this->mapAcl[$key] = self::Acl( $acl );
            }
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Apply default ACL's to object list
     */
    protected function applyRoleDefaultAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $name = HostSystemApi::getUserRole();

        $acls = $this->AclNamedRoleWildcardTypeWildcardId( $name );
        $keys = $this->mapAcl->keys();
        foreach ( $acls as $acl ) {
            Log::debug( "$name role default, type = *, id = *, acl = " . self::Acl( $acl ) );
            foreach ($keys as $key) {
                $this->mapAcl[$key] = self::Acl( $acl );
            }
        }
        if ( sizeof( $acls ) > 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Apply super-default ACL's to object list
     */
    protected function applySuperDefaultAcls( $acl ) {

        Log::debug( "super-default acl. type = *, id = *, acl = " . $acl );

        $keys = $this->mapAcl->keys();
        foreach ($keys as $key) {
            $this->mapAcl[$key] = $acl;
        }
    }

    protected function buildAclMap( $items ) {

        // default is no access
        $acl = OlabConstants::OLAB_DEFAULT_ACL;

        // build array with all ids and default acl
        foreach( $items as $item ) {
            $this->mapAcl->put( $item->id, $acl );
        }
    }
}