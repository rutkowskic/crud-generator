<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class Helpers {
    static function render($args, $stub)
    {
        [$keys, $values] = Arr::divide($args);
        return str_replace($keys, $values, $stub);
    }

    static function getWhenKeyIsTrue($query, $keyQuery)
    {
        return Arr::where($query, function ($value, $key) use($keyQuery){
            return Arr::get($value, $keyQuery, false);
        });
    }

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

    static function filterRelationsBy($json, $type)
    {
        $models = collect($json['relations'])->filter(function ($value, $key) {
            return $value['type'] === $type;
        });
    }
}
