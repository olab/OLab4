<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Http\Response;

require (dirname(__FILE__).'/TestCase.php');

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->get('/');

        $response = resolve(Response::class);
        $this->assertNotNull($response->getContent());
    }
}
