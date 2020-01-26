<?php

namespace Entrada\Modules\User\Http\Middleware;

use Closure;
use Module;

class AddModulesToUserSummary
{

    private $static_function_name = 'appendToUserSummary';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response_collection = collect($response->original);

        foreach(Module::enabled() as $module) {

            // Get the controller `ModuleName`Controller

            $module_controller = module_class($module['slug'], 'Http\Controllers\\'.$module['name'].'Controller');

            // If the appendToUserSummary static method exists in that controller class, append it to the response

            if (method_exists($module_controller, $this->static_function_name)) {

                // Append function return to response collection

                $response_collection->put($module['slug'], call_user_func(array($module_controller, $this->static_function_name)));
            }
        }

        $response->setContent($response_collection);

        return $response;
    }
}   
