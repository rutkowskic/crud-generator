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
        $this->singular = Str::singular(strtolower($model)); //post
        $this->singularUppercase = Str::singular(ucfirst($model)); //Post
        $this->plural = Str::plural(strtolower($model)); //posts
        $this->pluralUppercase = Str::plural(ucfirst($model)); //Posts
        $this->fields = $fields;
    }

    public function relationFields($relation)
    {
        if(array_key_exists("fields", $relation)){
            $namesOfFields = array_column($relation['fields'], 'name');
            return ", \$request->only('". implode("', '", $namesOfFields) ."')";
        }
        return "";
    }

    public function createRelations()
    {
       $relations = '';
       if(array_key_exists("relations", $this->json)){
           foreach($this->json['relations'] as $relation){
            ['model' => $model, 'type' => $type] = $relation;
               $path = __DIR__.'/stubs/controller/'.$type.'.stub';
               $stub = $this->getStub($path); 
               $relations .= $this->render(array(
                   '##plural##' => $this->plural,
                   '##singular##' => $this->singular, 
                   '##plural|ucfirst##' => $this->pluralUppercase,
                   '##singular|ucfirst##' => $this->singularUppercase,
                   "##relation|singular##" => Str::singular($model),
                   "##relation|singular|ucfirst##" => Str::singular(ucfirst($model)),
                   "##relation|plural##" => Str::plural(strtolower($model)),
                   '##relation|fields##' => $this->relationFields($relation)
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
            foreach($this->json['relations'] as ['model' => $model, 'select' => $select]){
                if(array_key_exists("where", $select)){
                    $with = explode("|", $select['where']);
                    return $relations .= "$".Str::plural(strtolower($model))." = ".Str::singular(ucfirst($model))."::with('". Str::plural(strtolower($with[0])) ."')->get();\n";
                }
                $relations .= "$".Str::plural(strtolower($model))." = ".Str::singular(ucfirst($model))."::all();\n";
            }
        }
        return $relations;
    }

    public function createImportModels()
    {
        $imports = '';
        if(array_key_exists("relations", $this->json)){
            foreach($this->json['relations'] as ['model' => $model]){
                $imports .= "use App\\".Str::singular(ucfirst($model)).";\n";                
            }
        }
        return $imports;
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

    public function createIndexModel()
    {
        if(array_key_exists('index', $this->json)){
            $where = explode("|", $this->json['index']['where']);
            return "\$".$this->plural." = ".$this->singularUppercase."::with('".Str::plural(strtolower($where[0]))."')->latest()->paginate(25);";
        }
        return "\$".$this->plural." = ".$this->singularUppercase."::latest()->paginate(25);";
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
            '##indexModel##' => $this->createIndexModel(),
            '##createFiles##' => $this->filesTemplate(__DIR__.'/stubs/controller/createfile.stub'),
            '##updateFiles##' => $this->filesTemplate(__DIR__.'/stubs/controller/updatefile.stub'),
            '##deleteFiles##' => $this->filesTemplate(__DIR__.'/stubs/controller/deletefile.stub'),
            '##editModel##' => $this->createEditModel(),
            '##importModels##' => $this->createImportModels(),
            '##relationModels##' => $this->createRelationModels(),
            '##editCompact##' => $this->createEditCompact(),
            '##relations##' => $this->createRelations()
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