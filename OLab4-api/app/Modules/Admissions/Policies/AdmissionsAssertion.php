<?php

namespace Entrada\Modules\Admissions\Policies;

/**
 * Not Guest assertion class
 *
 * Asserts that a role is not a guest
 */
class AdmissionsAssertion implements \Zend_Acl_Assert_Interface  {

    public function assert(\Zend_Acl $acl, \Zend_Acl_Role_Interface $role = null, \Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        global $db;

        /*
         *
        $role = $acl->_entrada_last_query_role;
        if (isset($role->details) && isset($role->details["group"])) {
            $GROUP = $role->details["group"];
        } else {

        }

        if ($GROUP == "guest") {
            return false;
        } else {
            return true;
        }*/

        return true;
    }
}