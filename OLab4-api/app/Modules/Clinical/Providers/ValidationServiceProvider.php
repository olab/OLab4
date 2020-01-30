<?php

namespace Entrada\Modules\Clinical\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Validator;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Custom validation rules
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('timestamp_greater_than', function($attribute, $value, $parameters, $validator) {
            $start_date_field = $parameters[0];
            $data = $validator->getData();
            $min_date = $data[$start_date_field];
            return $value > $min_date;
        });
    }
}
