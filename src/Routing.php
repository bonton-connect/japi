<?php

namespace Bonton\Japi;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V2\Main;
use Bonton\Japi\Controller;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
class Routing
{
    public function routes($requests, $class_name)
    {

        $needle = "App\Http\Controllers\\";
        $replace_with = "";
        $class = str_replace($needle, $replace_with, $class_name);
        foreach ($requests as $key => $values) {
            $type = $key;
            foreach ($values as $key => $val) {
                if (is_int($key)) {
                    if ($val == "GET") {
                        Route::get('/' . $type, $class.'@handle')->where('all', '(.*)');
                        $path = '/' . $type;
                        //echo $path. "  , ";
                    } elseif ($val == "POST") {
                        Route::post('/' . $type, $class.'@handle')->where('all', '(.*)');
                    } elseif ($val == "PATCH") {
                        Route::patch('/' . $type . '/{id}', $class.'@handle')->where('all', '(.*)');
                    } elseif ($val == "DELETE") {
                        Route::delete('/' . $type . '/{id}', $class.'@handle')->where('all', '(.*)');
                    }
                } elseif (is_string($key)) {
                    $relationships = $key;
                    $type = $type;
                    foreach ($val as $value) {
                        if ($value == "GET") {
                            $path = '/' . $type . '/{id}/relationships/' . $relationships;
                            echo $path . "   ";
                            Route::get('/' . $type . '/{id}/relationships/' . $relationships, $class.'@handle')->where('all', '(.*)');
                        } elseif ($value == "POST") {
                            Route::post('/' . $type . '/{id}/relationships/' . $relationships, $class.'@handle')->where('all', '(.*)');
                        } elseif ($value == "PATCH") {
                            Route::patch('/' . $type . '/{id}/relationships/' . $relationships, $class.'@handle')->where('all', '(.*)');
                        } elseif ($value == "DELETE") {
                            Route::delete('/' . $type . '/{id}/relationships/' . $relationships, $class.'@handle')->where('all', '(.*)');
                        }
                    }
                }
            }
        }
    }
}
