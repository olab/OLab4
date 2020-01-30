<?php

use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Example:

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->describe('Display an inspiring quote');

foreach(Module::enabled() as $active_module) {
	if (file_exists(dirname(__DIR__) . '/app/Modules/'.$active_module['name'].'/Routes/console.php')) {
		require dirname(__DIR__) . '/app/Modules/'.$active_module['name'].'/Routes/console.php';
	}
}
