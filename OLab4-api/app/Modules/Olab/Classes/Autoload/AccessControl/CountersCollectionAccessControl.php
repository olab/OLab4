<?php

namespace Entrada\Modules\Olab\Classes\Autoload\AccessControl;

use Illuminate\Support\Facades\Log;
use \Exception;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Models\SystemSettings;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\SecurityBase;
use Entrada\Modules\Olab\Models\UserSecurity;
use Entrada\Modules\Olab\Models\RoleSecurity;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\OlabConstants;
use \Ds\Map;


/**
 * Maps Collection Access Control
 *
 * @version 1.0
 * @author wirunc
 */
class CountersCollectionAccessControl extends CollectionAccessControlBase
{
    public function __construct( $oObjects ) {
        parent::__construct("Counters", $oObjects);
    }

    /**
     * Initialize map ACL's from 'default' ACL
     */
    protected function initializeAclObjectCache() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // get list of all counters
        $items = Counters::get();

        // build default acl array for items found
        $this->buildAclArray( $items );
    }

}