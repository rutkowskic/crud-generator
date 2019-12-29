<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

trait Helpers {
 
    function getStub($path)
    {
        return File::get($path);
    }

    function render($args, $stub)
    {
        [$keys, $values] = Arr::divide($args);
        return str_replace($keys, $values, $stub);
    }

    function getWhenKeyIsBool($query, $keyQuery)
    {
        return Arr::where($query, function ($value, $key) use($keyQuery){
            return Arr::get($value, $keyQuery, false);
        });
    }

}
