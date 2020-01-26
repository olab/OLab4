<?php

namespace Entrada\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Entrada\Libraries\Ldap;

class AdUserProvider extends EloquentUserProvider
{
    /**
     * @var Ldap        $ldap               Ldap connection class
     * @var array       #ldap_options       Options for the LDAP connection
     */
    protected $ldap;
    protected $ldap_options = array(
        'host'              => LDAP_HOST,
        'bindRequiresDn'    => false,
        'baseDn'            => LDAP_PEOPLE_BASE_DN,
    );

    /**
     * AdUserProvider constructor.
     *
     * @param HasherContract $hasher    Note, this is not actually used in this class, but is required anyway
     * @param string         $model     The model class used to look up users in the database
     */
    public function __construct(HasherContract $hasher, $model)
    {
        parent::__construct($hasher, $model);
    }

    public function __destruct()
    {
        if (!is_null($this->ldap)) {
            $this->ldap->disconnect();
            $this->ldap = null;
        }
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  string  $column
     * @param  string  $value
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByColumn($column, $value)
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($column, $value)
            ->first();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (is_null($this->ldap)) {
            $this->ldap = new Ldap($this->ldap_options);
            if (!$this->ldap->connect()) {
                return null;
            }
        }

        /**
         * Attempt to bind with the given username and password.
         */
        if (!$this->ldap->bind($credentials["username"], $credentials["password"])) {
            return null;
        }

        $search_query       = LDAP_USER_IDENTIFIER . "=" . $credentials["username"];
        $result = $this->ldap->searchEntries($search_query, LDAP_PEOPLE_BASE_DN);

        if (isset($result[LDAP_USER_QUERY_FIELD]) && !empty($result[LDAP_USER_QUERY_FIELD][0])) {
            /**
             * fetch the user record based on the input field in LDAP that corresponds to the column in the user_data table
             */
            if (LDAP_LOCAL_USER_QUERY_FIELD == "number") {
                $user_query_field_value = clean_input($result[LDAP_USER_QUERY_FIELD][0], "numeric");
            } else {
                $user_query_field_value = clean_input($result[LDAP_USER_QUERY_FIELD][0], "credentials");
            }
            return $this->retrieveByColumn(LDAP_LOCAL_USER_QUERY_FIELD, $user_query_field_value);
        }
        return null;
    }
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        /**
         * Bind to LDAP with the users DN and given password. We already have the user record, this is LDAP telling us if they supplied a correct password
         */
        if (is_null($this->ldap)) {
            $this->ldap = new Ldap($this->ldap_options);
            if (!$this->ldap->connect()) {
                return false;
            }
        }

        if (!$this->ldap->bind($credentials["username"], $credentials["password"])) {
            return false;
        }
        return true;
    }
}
