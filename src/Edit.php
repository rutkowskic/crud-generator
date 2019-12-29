<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Rcoder\CrudGenerator\Helpers;

class Edit {

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
    
    function createSelectProporties($relation)
    {
        ['model' => $model, 'select' => $select] = $relation;

        if(array_key_exists("table" , $select) && array_key_exists("where" , $select)){
            return Str::singular(strtolower($model)) . "->pivot->" . $select['name'];
        }
        return Str::singular(strtolower($model)) . "->" .  $select['name'];
    }

    function createRelationForm($relation, $type)
    {
        ['model' => $relationModel, 'fields' => $relationFields] = $relation;

        $form = '';

        foreach($relationFields as ['name' => $fieldName, 'type' => $fieldType]){

            $stub = __DIR__ .'/stubs/views/components/'.$fieldType.'.stub';

            $form .= $this->render(array(
                "##component|singular##" => Str::singular(strtolower($fieldName)),
                "##component|plural|ucfirst##" => Str::plural(ucfirst($fieldName)),
                '##component|singular|ucfirst##' => Str::singular(ucfirst($fieldName)),
                "##component|value##" => $type === 'create' ? "''" : '$'. Str::singular($relationModel) . '->pivot->' . Str::singular($fieldName)
            ), $stub);

            $form .= "\n";
        }

        return $form;
    }

    function createPivotTableElements($relation)
    {
        ['model' => $model, 'fields' => $fields] = $relation;
        $theads = "";
        $tbodys = "";

        foreach($this->getWhenKeyIsBool($fields, 'active') as $active){
            $theads .= "<th scope='col'>" . Str::singular(ucfirst($active['name'])) . "</th>\n";
            $tbodys .= "<td scope='row'>{{\$". Str::singular(strtolower($model)) ."->pivot->". Str::singular(strtolower($active['name'])) ."}}</td>\n";
        } 

        return ['theads' => $theads, 'tbodys' => $tbodys];
    }

    function createRelations()
    {
        $relations = '';
        if(array_key_exists("relations", $this->json)){
            foreach ($this->json['relations'] as $relation) {

                ['theads' => $theads, 'tbodys' => $tbodys] = $this->createPivotTableElements($relation);

                $stub = __DIR__ .'/stubs/views/'.$relation['type'].'.stub';
                $relations .= $this->render(array(
                    '##singular##' => $this->singular,
                    "##plural##" => $this->plural,
                    '##relation|singular##' => Str::singular(strtolower($relation['model'])),
                    '##relation|plural##' => Str::plural(strtolower($relation['model'])),
                    '##relation|singular|ucfirst##' => Str::singular(ucfirst($relation['model'])),
                    '##relation|plural|ucfirst##' => Str::plural(ucfirst($relation['model'])),
                    '##relationtd##' => $tbodys,
                    '##relationth##' => $theads,
                    '##createRelationForm##' => $this->createRelationForm($relation, 'create'),
                    '##updateRelationForm##' => $this->createRelationForm($relation, 'update'),
                    '##relation|selectProporties##' => $this->createSelectProporties($relation)
                ), $stub);
            } 
        }
        return $relations;
    }

    function createCheckedCondition($field){
        
        ['name' => $name, 'type' => $type] = $field;
        
        if($type === 'checkbox')
        {
            return "{{\$".$this->singular."->" . Str::singular(strtolower($name)) . " ? 'checked' : ''}}";
        }

        return '';
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
            $path = __DIR__ .'/stubs/views/components/'.$field['type'].'.stub';

            $stub = $this->getStub($path);

            $form .= $this->render(array(
                '##singular##' => $this->singular,
                '##component|singular##' => Str::singular(strtolower($field['name'])),
                '##component|singular|ucfirst##' => Str::singular(ucfirst($field['name'])),
                '##component|value##' =>  '{{$' . $this->singular . '->' . Str::singular(strtolower($field['name'])) . '}}',
                '##required##' => "",
                "##checked##" => $this->createCheckedCondition($field)
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
            "##relations##" => $this->createRelations(),
            "##scripts##" => $this->createWyswigScripts()
        ],  $this->getStub(__DIR__ .'/stubs/views/edit.stub'));
    }

    function saveEditTemplate()
    {
        File::put( resource_path('views/admin/'.$this->plural.'/edit.blade.php'), $this->createTemplate());
    }
    
    public function init()
    {
        $this->saveEditTemplate();
    }
}