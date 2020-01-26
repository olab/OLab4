<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This trait adds data properties to an object that represent
 * the ENTRADA_USER. The actor proxy id and actor organisation id
 * should *always* reference the current logged in user (the
 * actor's active proxy and organisation IDs).
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

namespace Entrada\Traits;

trait EntradaActor
{
    protected $actor_proxy_id = null;         // The proxy ID of the user manipulating this functionality (the actor). Not necessarily a proxy ID.
    protected $actor_organisation_id = null;  // The actor's organisation ID.
    protected $actor_group = null;            // The actor's group name.
    protected $actor_scope = null;            // "internal" or "external
    protected $actor_type = null;             // e.g., "proxy_id" or "external_assessor_id"

    public function setActorOrganisationID($organisation_id)
    {
        $this->actor_organisation_id = $organisation_id;
    }

    public function setActorProxyID($proxy_id)
    {
        $this->actor_proxy_id = $proxy_id;
    }

    public function setActorGroup($group)
    {
        $this->actor_group = $group;
    }

    public function setActorScope($scope)
    {
        $this->actor_scope = $scope;
    }

    public function setActorType($type)
    {
        $this->actor_type = $type;
    }

    public function getActorOrganisationID()
    {
        return $this->actor_organisation_id;
    }

    public function getActorProxyID()
    {
        return $this->actor_proxy_id;
    }

    public function getActorGroup()
    {
        return $this->actor_group;
    }

    public function getActorScope()
    {
        return $this->actor_scope;
    }

    public function getActorType()
    {
        return $this->actor_type;
    }

    /**
     * Validate that the specified actor properties are set (they exist and are not null).
     * The default options are to validate that organisation and proxy_id are set.
     * This functionality should be called before any methods in classes that rely on specific actor properties being set.
     *
     * @param array $properties
     * @return bool
     */
    public function validateActor($properties = ['actor_proxy_id', 'actor_organisation_id'])
    {
        if (!is_array($properties) || !$properties) {
            // Supplied properties array is invalid
            return false;
        } else if (empty($properties)) {
            // We've been specifically told to validate nothing.
            return true;
        } else {
            foreach ($properties as $property) {
                if (!property_exists($this, $property)) {
                    // The given property does not exist in this object.
                    return false;
                }
                if ($this->$property === null
                    || $this->$property === false
                ) {
                    // The given property has not been set.
                    // Note that this logic allows for 0 to be returned as a valid property.
                    return false;
                }
            }
            // We passed all checks.
            return true;
        }
    }

    /**
     * Return an array containing the current actor for related abstraction layer constructors.
     * Optionally add more construction options to the array via $additional_properties.
     *
     * A reference to the calling object is also returned.
     *
     * @param array $additional_properties
     * @return array
     */
    public function buildActorArray($additional_properties = [])
    {
        $actor = [
            'parent_object' => &$this,              // This allows the child to reference its parent.
            'actor_proxy_id' => $this->actor_proxy_id,
            'actor_organisation_id' => $this->actor_organisation_id,
            'actor_scope' => $this->actor_scope,    // e.g. 'internal'
            'actor_type' => $this->actor_type,      // e.g. 'proxy_id'
            'actor_group' => $this->actor_group,    // (optional) e.g. 'faculty'
        ];
        return array_merge($actor, $additional_properties);
    }
}
