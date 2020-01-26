<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/
$factory->define(Entrada\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
    ];
});

 
/* 
|-------------------------------------------------------------------------- 
| Module Model Factories 
|-------------------------------------------------------------------------- 
| 
| This loads up the factories of every enabled module. 
| 
*/ 



foreach(Module::enabled() as $active_module) {

    $path = app_path('Modules/'.$active_module['name'].'/Database/Factories');

    $factory->load($path);
}