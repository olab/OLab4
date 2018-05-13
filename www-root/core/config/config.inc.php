<?php
return array (
  'entrada_url' => 'http://olab4.localhost/apidev',
  'entrada_relative' => '/apidev',
  'entrada_absolute' =>     '/var/www/vhosts/OLab/OLab4/www-root',
  // CMW: added
  'entrada_api_absolute' => '/var/www/vhosts/OLab/OLab4-api',
  'entrada_storage' =>      '/var/www/vhosts/OLab/OLab4/www-root/core/storage',
  'database' =>
  array (
    'adapter' => 'mysqli',
    'host' => '127.0.0.1',
    'username' => 'entrada',
    'password' => 'password',
    'entrada_database' => 'entrada',
    'auth_database' => 'entrada_auth',
    'clerkship_database' => 'entrada_clerkship',
    // CMW: added
    'openlabyrinth_database' => 'openlabyrinth'
  ),
  'admin' =>
  array (
    'firstname' => 'System',
    'lastname' => 'Administrator',
    'email' => 'corey@cardinalcreek.ca',
  ),
  'auth_username' => 'c8c4e3a858a62dd27b6b55564124294d',
  'auth_password' => '4a7f94f8938f6e5549d9a1b65a591205',
);
