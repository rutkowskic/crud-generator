<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Layout
{
    use Helpers;

    private $jsons;

    private $file;

    function __construct($jsons) 
    {
        $this->jsons = $jsons;
    }

    function createAdminMenu()
    {
        $list = '';

        foreach($this->jsons as ['model' => $model])
        {
            $pluralUppercase = ucfirst(Str::plural($model));
            $list .= "<li><a href=\"{{ route('{$model}.index') }}\">{$pluralUppercase}</a></li>\n";
        }

        return $list;

    }

    function createAdminLayout()
    {
        $stub = $this->getStub(__DIR__ .'/stubs/views/layouts/admin.stub');

        return $this->render(array(
            '##menu##' => $this->createAdminMenu()
        ), $stub);
    }
    
    function saveAdminLayout()
    {
        if(!File::exists(resource_path('views/layouts'))){
            File::makeDirectory(resource_path('views/layouts'));
        }
        File::put( resource_path('views/layouts/admin.blade.php'), $this->createAdminLayout() );
    }

    public function init()
    {
        $this->saveAdminLayout();
    }
    
}