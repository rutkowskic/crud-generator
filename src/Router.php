<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Router {

    use Helpers;
    
    private $jsons;
    private $file;

    function __construct($jsons) 
    {
        $this->jsons = $jsons;
    }

    public function createRoutes(){
        $resources = '';
        foreach ($this->jsons as ['model' => $model]) {
            $resources .= "Route::resource('".Str::plural(strtolower($model))."', '".Str::singular(ucfirst($model))."Controller');\n";
        }
        return $resources;
    }

    public function createRelationRoutes(){
        $relationRoutes = '';

        foreach($this->jsons as $json){
            if(array_key_exists("relations", $json)){
                foreach($json['relations'] as ['model' => $relationModel, 'type' => $type]){
                    $stub = $this->getStub(__DIR__ .'/stubs/routes/'.$type.'.stub');
                    $relationRoutes .= $this->render(array(
                        "##singular##" => Str::singular(strtolower($json['model'])),
                        "##plural##" => Str::plural(strtolower($json['model'])),
                        '##singular|ucfirst##' => Str::singular(ucfirst($json['model'])),
                        '##plural|ucfirst##' => Str::plural(ucfirst($json['model'])),
                        '##relation|singular##' => Str::singular(strtolower($relationModel)),
                        '##relation|plural##' => Str::plural(strtolower($relationModel)),
                        '##relation|singular|ucfirst##' => Str::singular(ucfirst($relationModel)),
                        '##relation|plural|ucfirst##' => Str::plural(ucfirst($relationModel)),
                    ), $stub);
                    $relationRoutes .= "\n";
                }
            }
        };

        return $relationRoutes;
    }

    public function getRoutes(){
        $stub = $this->getStub(__DIR__ .'/stubs/routes/routes.stub');
        
        return $this->render(array(
            "##routes##" => $this->createRoutes(),
            "##relationRoutes##" => $this->createRelationRoutes()
        ), $stub);
    }

    public function saveRoutes(){
        file_put_contents(base_path('routes/web.php'), PHP_EOL . $this->getRoutes(), FILE_APPEND);
    }

    public function init()
    {
        $this->saveRoutes();
    }
}