<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Controller {

    use Helpers;

    private $json;

    private $singular;
    
    private $singularUppercase;
    
    private $plural;
    
    private $pluralUppercase;

    private $fields;
    
    function __construct($json) 
    {
        ['model' => $model, 'fields' => $fields] = $json;
        $this->json = $json;
        $this->singular = Str::singular(strtolower($model));
        $this->singularUppercase = Str::singular(ucfirst($model));
        $this->plural = Str::plural(strtolower($model)); 
        $this->pluralUppercase = Str::plural(ucfirst($model));
        $this->fields = $fields;
    }

    public function createRelations()
    {
       $relations = '';
       if(array_key_exists("relations", $this->json)){
           foreach($this->json['relations'] as ['model' => $model, 'type' => $type]){
               $path = __DIR__.'/stubs/controller/'.$type.'.stub';
               $stub = $this->getStub($path); 
               $relations .= $this->render(array(
                   '##plural##' => $this->plural,
                   '##singular##' => $this->singular, 
                   '##plural|ucfirst##' => $this->pluralUppercase,
                   '##singular|ucfirst##' => $this->singularUppercase,
                   "##relation|singular##" => Str::singular($model),
                   "##relation|singular|ucfirst##" => Str::singular(ucfirst($model)),
                   "##relation|plural##" => Str::plural(strtolower($model))
               ), $stub);
               $relations .= "\n";
           }
       }
       return $relations;
    }

    public function createRelationModels()
    {
        $relations = '';
        if(array_key_exists("relations", $this->json)){
            foreach($this->json['relations'] as ['model' => $model]){
                $relations .= "$".Str::plural(strtolower($model))." = ".$this->singularUppercase."::all();\n";
            }
        }
        return $relations;
    }

    function createEditModel()
    {
        if(!empty($this->relations)){
            return "$".$this->singular." = ".$this->singularUppercase."::with(['". implode("', '", $this->relationsModel) ."'])->where('id', $".$this->singular.")->first();";
        };
        return "\${$this->singular} = {$this->singularUppercase}::find(\${$this->singular});";
    }

    public function getFromFields($table, $key, $value)
    {
        return array_filter($table, function  ($item) use($key, $value){
            return $item[$key] == $value;
        });
    }

    public function filesTemplate($path)
    {
        $template = '';
        foreach($this->getFromFields($this->fields, 'type', 'file') as ['name' => $name]){
            $template .= $this->render(array(
                '##plural##' => $this->plural,
                '##singular##' => $this->singular,
                '##name##' =>  Str::singular(strtolower($name))
            ), $this->getStub($path));
            $template .= "\n";
        }
        return $template;
    }

    public function createEditCompact()
    {
        if(array_key_exists("relations", $this->json)){
            $pluck = Arr::pluck($this->json['relations'], 'model');
            return ", '" . implode("', '", $pluck) . "'";
        }
        return '';
    }

    public function createCheckbox($path)
    {
        $template = '';
        foreach($this->getFromFields($this->fields, 'type', 'checkbox') as ['name' => $name]){
            $template .= $this->render(array(
                '##plural##' => $this->plural,
                '##singular##' => $this->singular,
                '##name##' => Str::singular(strtolower($name))
            ), $this->getStub($path));
            $template .= "\n";
        }
        return $template;
    }

    public function createController()
    {
        $controllerPath = __DIR__.'/stubs/controller/controller.stub';
        $controllerStub = File::get( $controllerPath );

        return $this->render(array(
            '##plural##' => $this->plural,
            '##singular##' => $this->singular,
            '##plural|ucfirst##' => $this->pluralUppercase,
            '##singular|ucfirst##' => $this->singularUppercase,
            '##createFiles##' => $this->filesTemplate(__DIR__.'/stubs/controller/createfile.stub'),
            '##updateFiles##' => $this->filesTemplate(__DIR__.'/stubs/controller/updatefile.stub'),
            '##deleteFiles##' => $this->filesTemplate(__DIR__.'/stubs/controller/deletefile.stub'),
            '##editModel##' => $this->createEditModel(),
            '##relationModels##' => $this->createRelationModels(),
            '##editCompact##' => $this->createEditCompact(),
            '##relations##' => $this->createRelations(),
            '##checkbox##' => $this->createCheckbox(__DIR__.'/stubs/controller/checkbox.stub')
        ), $controllerStub);
    }

    public function saveController()
    {
        File::put(app_path('Http/Controllers/Admin/' . $this->singularUppercase . 'Controller.php'), $this->createController());
    }

    public function init()
    {
        $this->saveController();
    }
}