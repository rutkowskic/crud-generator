<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Create {

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

    function createWyswigScripts()
    {
        $scripts = '';

        $objectsWhichNeedScript = $this->getWhenKeyIsBool($this->fields, "wyswig");

        if(!empty($objectsWhichNeedScript))
        {
            $scripts .= '<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
        }

        foreach($objectsWhichNeedScript as ['name' => $name, 'type' => $type])
        {
            if($type === 'textarea')
            {
                $scripts .= "\n<script>tinymce.init({selector:'#{$name}'});</script>";
            }
        }

        return $scripts;
    }

    function createForm()
    {
        $form = '';

        foreach ($this->fields as $field) {
            $stub = $this->getStub(__DIR__ .'/stubs/views/components/'.$field['type'].'.stub');
            $form .= $this->render(array(
                '##singular##' => $this->singular,
                '##component|singular##' => Str::singular(strtolower($field['name'])),
                '##component|singular|ucfirst##' => Str::singular(ucfirst($field['name'])),
                '##component|value##' =>  "",
                '##required##' => Arr::get($field, 'required', false),
                '##checked##' => ""
            ), $stub);

            $form .= "\n";
        }

        return $form;
    }

    function createTemplate()
    {
        return $this->render([
            "##singular##" => $this->singular,
            "##plural##" => $this->plural,
            '##singular|ucfirst##' => $this->singularUppercase,
            "##form##" => $this->createForm(),
            "##scripts##" => $this->createWyswigScripts()
        ],  $this->getStub(__DIR__ .'/stubs/views/create.stub'));
    }
    
    function saveTemplate(){
        File::put(resource_path('views/admin/'.$this->plural.'/create.blade.php'), $this->createTemplate());
    }
    
    public function init()
    {
        $this->saveTemplate();
    }
}