<?php

return array (
  'entrada_url' => 'http://olab4.localhost/player',
  'entrada_relative' => '/player',
  'entrada_absolute' => '/var/www/vhosts/OLab4-site/www-root',
   // CMW: added
  'entrada_api_absolute' => '/var/www/vhosts/OLab4-api',
  'entrada_storage' =>      '/var/www/vhosts/OLab4-site/www-root/core/storage',
  'database' =>
    array (
      'adapter' => 'mysqli',
      'host' => 'database',
      'username' => 'entrada',
      'password' => 'password',
      'entrada_database' => 'entrada',
      'auth_database' => 'entrada_auth',
      'clerkship_database' => 'entrada_clerkship',
      // CMW: added
      'openlabyrinth_database' => 'openlabyrinth'
    ),
  'service_account' => 
    array( 
      'username' => 'service', 
      'password' => 'account001'
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
