<?php
return array (
  'entrada_url' => 'https://dev.olab.ca',
  'entrada_relative' => '',
  'entrada_absolute' => '/var/www/vhosts/OLab.dev/OLab4/www-root',
   // CMW: added
  'entrada_api_absolute' => '/var/www/vhosts/OLab.dev/OLab4-api',
  'entrada_storage' => '/var/www/vhosts/OLab.dev/OLab4/www-root/core/storage',
  'database' => array (
    'adapter' => 'mysqli',
    'host' => '127.0.0.1',
    'username' => 'entrada_dev',
    'password' => 'b012db9d53',
    'entrada_database' => 'entrada_dev',
    'auth_database' => 'entrada_auth_dev',
    'clerkship_database' => 'entrada_clerkship_dev',
    // CMW: added
    'openlabyrinth_database' => 'openlabyrinth_dev'
  ),
  'admin' =>
  array (
    'firstname' => 'System',
    'lastname' => 'Administrator',
    'email' => 'corey@cardinalcreek.ca',
  ),
  'auth_username' => '63a9de5adfe453481679d421f490222e',
  'auth_password' => '4a7f94f8938f6e5549d9a1b65a591205',
);
