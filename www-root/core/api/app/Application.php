<?php

namespace App;

use RuntimeException;
use Illuminate\Foundation\Application as LaravelApplication;
use Zend_Config;

class Application extends LaravelApplication
{
    /**
     * Instance of Zend_Config used globally by Entrada
     *
     * @var object
     */
    protected $entrada_config;

    /**
     * Create a new Illuminate application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        $this->entrada_config = new Zend_Config(require ENTRADA_CORE . "/config/config.inc.php");

        parent::__construct($basePath);
    }

    /**
     * Set storage path to use Entrada's global storage folder
     * @param null $path
     * @return string
     */
    public function storagePath($path = null)
    {
        return $this->storagePath ?: $this->entrada_config->entrada_storage . ($path ? '/' . $path : $path);
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (is_null($this->namespace)) {
            $this->namespace = config('app.namespace');
        }

        return $this->namespace;
    }

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return CACHE_DIRECTORY.'/services.php';
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return CACHE_DIRECTORY.'/config.php';
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return CACHE_DIRECTORY.'/routes.php';
    }
}
