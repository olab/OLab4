<?php

namespace Entrada\Modules\Olab\Classes\Autoload\AccessControl;

use Illuminate\Support\Facades\Log;
use \Exception;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Models\UserSecurity;
use Entrada\Modules\Olab\Models\RoleSecurity;
use Entrada\Modules\Olab\Models\SecurityBase;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use \Ds\Map;

/**
 * CollectionAccessControlBase short summary.
 *
 * CollectionAccessControlBase description.
 *
 * @version 1.0
 * @author wirunc
 */
abstract class CollectionAccessControlBase extends AccessControlBase
{
    // the securable objects
    protected $aoObjects;

    public function __construct($type, $aoObjects) {

        $this->aoObjects = $aoObjects;
        parent::__construct($type);
    }

    protected function getSecurableObjects() {
        return $this->aoObjects;
    }

    /**
     * Test if allowed to list specified object
     * @param mixed $map_id
     */
    public function isListable( $id ) {

        if ( !$this->mapAcl->hasKey( $id ))
            return false;

        return self::containsACLFlag( $this->mapAcl->get( $id ),
                                        AccessControlBase::ACL_READ_ACCESS );
    }

     /**
     * Tests if allowed to play specified object
      * @param mixed $id
      */
     public function isExecutable( $id ) {

        if ( !$this->mapAcl->hasKey( $id ))
            return false;

         return self::containsACLFlag( $this->mapAcl->get( $id ),
                                         AccessControlBase::ACL_EXECUTE_ACCESS );
     }

     /**
     * Tests if allowed to write/author specified object
      * @param mixed $id
      */
     public function isWriteable( $id ) {

        if ( !$this->mapAcl->hasKey( $id ))
            return false;

         return self::containsACLFlag( $this->mapAcl->get( $id ),
                                         AccessControlBase::ACL_AUTHORABLE_ACCESS );
     }
}