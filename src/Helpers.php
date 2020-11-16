<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class Helpers {
    static function makeDirectory($path)
    {
        if(!File::exists($path)){
            File::makeDirectory($path);
        }
    }

    static function getFromFields($table, $key, $value)
    {
        return array_filter($table, function  ($item) use($key, $value){
            return $item[$key] == $value;
        });
    }
    
}
