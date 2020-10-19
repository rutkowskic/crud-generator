<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Stubs;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;

class Edit {

    use Stubs;

    static function createRelations($singular, $plural, $json)
    {
        $relations = '';
        if(array_key_exists("relations", $json)){
            foreach($json['relations'] as $relation) {
                $relationSingular = Str::singular(strtolower($relation['model']));
                $relationPlural = Str::plural(strtolower($relation['model']));
                $relationSingularUCFirst = Str::singular(ucfirst($relation['model']));
                $relationPluralUCFirst = Str::plural(ucfirst($relation['model']));

                switch($relation['type']) {
                    case "onetoone":
                        $relations .= OneToOne::init($singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation);
                        break;
                    case "onetomany":
                        $relations .= OneToMany::init($singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation, $json);
                        break;
                    case "manytomany":
                        $relations .= ManyToMany::init($singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation);
                        break;
                }
                $relations .= "\n";
            } 
        }
        return rtrim($relations, "\n");
    }

    static function createWyswigScripts($fields)
    {
        $scripts = '';

        $objectsWhichNeedScript = Helpers::getWhenKeyIsTrue($fields, "wyswig");

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

    static function createForm($singular, $fields)
    {
        $form = '';

        foreach ($fields as $field) {
            $value;

            switch ($field['type']) {
                case "number":
                    $value = "value={{\$". $singular ."->". Str::singular(strtolower($field['name'])) . " ?? 0}}";
                    break;
                case "textarea":
                    $value = "{{\$". $singular ."->". Str::singular(strtolower($field['name'])) . " ?? \"\"}}";
                    break;
                default:
                    $value = "{{\$". $singular ."->". Str::singular(strtolower($field['name'])) . " ?? \"\"}}";
            }

            $componentSingular = Str::singular(strtolower($field['name']));
            $componentSingularUCFirst = Str::singular(ucfirst($field['name']));
            $componentValue = $value;
            $required = Arr::get($field, 'required', false) ? 'required' : '';

            $form .= self::{$field['type']}($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required);

            $form .= "\n";
        }

        return rtrim($form, "\n");
    }

    static public function init($json)
    {
        $singular = Str::singular(strtolower($json['model']));
        $plural = Str::plural(strtolower($json['model']));
        $singularUCFirst = Str::singular(ucfirst($json['model']));
        $pluralUCFirst = Str::plural(ucfirst($json['model']));

        $form = self::createForm($singular, $json['fields']);
        $scripts = self::createWyswigScripts($json['fields']);
        $relations = self::createRelations($singular, $plural, $json);

        $editTemplate = <<<EOD
@extends('layouts.admin')

@section('content')
<div>
    <div class="card">
        <div class="card-header">
            Edit
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('{$plural}.update', ['{$singular}' => \${$singular}->id]) }}" accept-charset="UTF-8" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                {$form}
                <button class="btn btn-primary btn-block mt-4" type="submit">Update</button>
            </form>
        </div>
    </div>
    {$relations}
    <div>
    <form action="{{ route('{$plural}.destroy', ['{$singular}' => \${$singular}->id]) }}" method="post">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-block" type="submit">Delete</button>
    </form>
    </div>
</div>
@endsection

@section('scripts')
{$scripts}
@endsection
EOD;
        File::put(resource_path('views/admin/'.$plural.'/edit.blade.php'), $editTemplate );
    }
}