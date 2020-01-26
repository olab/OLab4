<?php

if (defined('ENTRADA_CORE')) {

    $standard_entrada_config_location = ENTRADA_CORE . '/config/config.inc.php';
    $entrada_config = require $standard_entrada_config_location;

    return $entrada_config;

} else {
    return [];
}