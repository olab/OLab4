<?php

namespace Entrada\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Entrada\Libraries\Ldap;

class LdapUserProvider extends EloquentUserProvider
{
    /**
     * @var Ldap        $ldap               Ldap connection class
     * @var string      $ldap_user_dn       The distinguished name corresponding to the username that is provided in credentials
     * @var array       #ldap_options       Options for the LDAP connection
     */
    protected $ldap;
    protected $ldap_user_dn;
    protected $ldap_options = array(
                'host'              => LDAP_HOST,
                'username'          => LDAP_SEARCH_DN,
                'password'          => LDAP_SEARCH_DN_PASS,
                'bindRequiresDn'    => true,
                'baseDn'            => LDAP_PEOPLE_BASE_DN,
                );

    /**
     * LdapUserProvider constructor.
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
        /**
         * Setup connection to LDAP, if not already done
         */
        if (is_null($this->ldap)) {
            $this->ldap = new Ldap($this->ldap_options);
            if (!$this->ldap->connect()) {
                return null;
            }
        }

        /**
         * bind to the LDAP service account and get details of the user. This allows us to determine:
         * - the users DN, which may be different than the input username
         * - the LDAP entry that we use to lookup in the user data table to find the user record
         */
        if (!$this->ldap->bind()) {
            return null;
        }
        $search_query       = LDAP_USER_IDENTIFIER . "=" . $credentials["username"];
        $results = $this->ldap->searchEntries($search_query, LDAP_PEOPLE_BASE_DN);
        if ($results && is_array($results) && count($results) == 1) {
            $result = $results[0];
            /**
             * if we have both the member attribute and the query field, we continue, otherwise the attempt fails
             */
            if (isset($result[LDAP_MEMBER_ATTR]) && isset($result[LDAP_USER_QUERY_FIELD])) {

                /**
                 * set the DN for use later in validation
                 */
                $this->ldap_user_dn = LDAP_MEMBER_ATTR . "=" . $result[LDAP_MEMBER_ATTR][0] . "," . LDAP_PEOPLE_BASE_DN;

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
        if (is_null($this->ldap_user_dn)) {
            return false;
        }

        /**
         * Bind to LDAP with the users DN and given password. We already have the user record, this is LDAP telling us if they supplied a correct password
         */
        if (is_null($this->ldap)) {
            $this->ldap = new Ldap($this->ldap_options);
            if (!$this->ldap->connect()) {
                return false;
            }
        }

        if (!$this->ldap->bind($this->ldap_user_dn, $credentials["password"])) {
            return false;
        }
        return true;
    }
}
