<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $app = require(__DIR__.'/../bootstrap/app.php');

        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        return $app;
    }
}
