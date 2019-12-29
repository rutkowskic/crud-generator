<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Folders {

    use Helpers;
    
    private $plural;
    
    function __construct($json) 
    {
        ['model' => $model] = $json;
        $this->plural = Str::plural(strtolower($model));
    }

    function createModelFolder(){
        if(!File::exists( resource_path('views/admin/'.$this->plural) )){
            File::makeDirectory(resource_path('views/admin/'.$this->plural));
        }
    }
    
    function createAdminFolder(){
        if(!File::exists( resource_path('views/admin') )){
            File::makeDirectory(resource_path('views/admin'));
        }
    }

    public function init()
    {
        $this->createAdminFolder();
        $this->createModelFolder();
    }
}