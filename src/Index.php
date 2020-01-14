<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Index {

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

    function createTbodys(){
        $tbodys = "";

        foreach($this->getWhenKeyIsBool($this->fields, 'active') as $active){
            $tbodys .= "<td scope='row'>{{\$". $this->singular ."->". Str::singular(strtolower($active['name'])) ."}}</td>\n";
        }
 
        return $tbodys;
    }

    function createTheads(){
        $theads = "";

        foreach($this->getWhenKeyIsBool($this->fields, 'active') as $active){
            $theads .= "<th scope='col'>" . Str::singular(ucfirst($active['name'])) . "</th>\n";
        }
 
        return $theads;
    }
    
    function createIndexTemplate(){
        $stub = $this->getStub(__DIR__ .'/stubs/views/index.stub');
                
        return $this->render(array(
            '##plural##' => $this->plural,
            '##singular##' => $this->singular,
            '##plural|ucfirst##' => $this->pluralUppercase,
            "##thead##" => $this->createTheads(),
            "##tbody##" => $this->createTbodys(),
        ), $stub); 
    }

    function saveIndexTemplate(){
        if(!File::exists(resource_path('views/admin'))){
            File::makeDirectory(resource_path('views/admin'));
        }
        if(!File::exists(resource_path('views/admin/' . $this->plural))){
            File::makeDirectory(resource_path('views/admin/' . $this->plural));
        }
        File::put( resource_path('views/admin/'.$this->plural.'/index.blade.php'), $this->createIndexTemplate());
    }
    
    public function init()
    {
        $this->saveIndexTemplate();
    }
}