<?php

if (defined('ENTRADA_CORE')) {
    $standard_entrada_config_location = ENTRADA_CORE . '/config/config.inc.php';
    $entrada_config = require $standard_entrada_config_location;
}
else {
    $entrada_config = require resource_path('stubs/config/database.php');
}

/**
 * Character set
 *
 * @var string
 */
$charset = 'utf8';

/**
 * Collation
 *
 * @var string
 */
$collation = 'utf8_unicode_ci';

/**
 * Database driver. Accepts mysql, sqlite, etc.
 *
 * @var string
 */
$driver = $entrada_config['database']['adapter'] == 'mysqli' ? 'mysql' : $entrada_config['database']['adapter'];

/**
 * Global table name prefix, if any
 *
 * @var string
 */
$prefix = '';

/**
 * Database strict mode.
 *
 * @var string
 */
$strict = false;

return [

    /**
     * Sets the default database among many.
     *
     * @var string
     */
    'default' => 'entrada_database',

    /**
     * PDO fetch method. Default is FETCH_BOTH.
     *
     * @var string
     */
    'fetch' => PDO::FETCH_CLASS,

    /**
     * Configure each database from global config settings
     *
     * @var string
     */
    'connections' => [
        'entrada_database' => [
            'driver' => $driver,
            'host' => $entrada_config['database']['host'],
            'database' => $entrada_config['database']['entrada_database'],
            'username' => $entrada_config['database']['username'],
            'password' => $entrada_config['database']['password'],
            'charset' => $charset,
            'collation' => $collation,
            'prefix' => $prefix,
        ],
        'auth_database' => [
            'driver' => $driver,
            'host' => $entrada_config['database']['host'],
            'database' => $entrada_config['database']['auth_database'],
            'username' => $entrada_config['database']['username'],
            'password' => $entrada_config['database']['password'],
            'charset' => $charset,
            'collation' => $collation,
            'prefix' => $prefix,
        ],
        'clerkship_database' => [
            'driver' => $driver,
            'host' => $entrada_config['database']['host'],
            'database' => $entrada_config['database']['clerkship_database'],
            'username' => $entrada_config['database']['username'],
            'password' => $entrada_config['database']['password'],
            'charset' => $charset,
            'collation' => $collation,
            'prefix' => $prefix,
        ],
        // CMW: added
        'olab_database'  => [
            'driver' => $driver,
            'host' => $entrada_config['database']['host'],
            'database' => $entrada_config['database']['openlabyrinth_database'],
            'username' => $entrada_config['database']['username'],
            'password' => $entrada_config['database']['password'],
            'charset' => $charset,
            'collation' => $collation,
            'prefix' => $prefix,
        ]
    ],
];
