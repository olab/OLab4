<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\ActivityLog;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;

class LogController extends Controller
{
    //

    public function index() {


        return [
            "logs" => ActivityLog::all()
        ];

    }

}
