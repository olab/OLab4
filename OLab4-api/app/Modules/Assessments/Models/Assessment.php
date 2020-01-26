<?php

namespace Entrada\Modules\Assessments\Models;

use Illuminate\Support\Facades\Auth;

/**
 * Class Assessment
 *
 * Someday, I'd like to convert this into an Eloquent model. But for now, it's a Facade for the
 *  ME Assessments Interface.
 *
 * @package Entrada\Modules\Assessments\Models
 */
class Assessment {

    private static $_assessment_api;

    /**
     * Returns an instant of the Entrada Assessments Interface object
     *
     * @param array $data an array containing actor_proxy_id and actor_organization_id, if empty - default ENTRADA_USER is used
     * @return \Entrada_Assessments_Assessment
     */
    public static function api($data = [], $fromCache = true) {
        if (empty(self::$_assessment_api) || !$fromCache) {
            if (empty($data)) {
                global $ENTRADA_USER;
                $data = array(
                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                    "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                );
            }

            self::$_assessment_api = new \Entrada_Assessments_Assessment($data);
        }

        return self::$_assessment_api;
    }

}