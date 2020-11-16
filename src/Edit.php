<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;
use Rcoder\CrudGenerator\Stubs\Stubs;

class Edit {

    use Stubs;

    static function createRelations($singular, $plural, $json)
    {
        $relations = '';
        $relationsModel = [
            'onetoone' => 'OneToOne',
            'onetomany' => 'OneToMany',
            'manytomany' => 'ManyToMany'
        ];
        if(array_key_exists("relations", $json)){
            foreach($json['relations'] as $relation) {
                $relationSingular = Str::singular(strtolower($relation['model']));
                $relationPlural = Str::plural(strtolower($relation['model']));
                $relationSingularUCFirst = Str::singular(ucfirst($relation['model']));
                $relationPluralUCFirst = Str::plural(ucfirst($relation['model']));
                $relations .= call_user_func("Rcoder\\CrudGenerator\\". $relationsModel[strtolower($relation['type'])] . "::init", $singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation, $json);
                $relations .= "\n";
            } 
        }
        return rtrim($relations, "\n");
    }

    static function createWyswigScripts($fields)
    {
        $scripts = '';

        if($fields->contains('wyswig', true))
        {
            $scripts .= '<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
        }

        $scripts .= $fields->filter(fn($value, $key) => isset($value['wyswig']) && $value['wyswig'] === true && $value['type'] === 'textarea')
        ->reduce(fn($start, $item) => $start .= "\n<script>tinymce.init({selector:'#{$item['name']}'});</script>\n");

        return rtrim($scripts, "\n");
    }

    static function createForm($singular, $fields)
    {
        $form = '';

        foreach ($fields as $field) {
            $values = [
                'number' => "value={{\$". $singular ."->". Str::singular(strtolower($field['name'])) . " ?? 0}}",
                'textarea' => "{{\$". $singular ."->". Str::singular(strtolower($field['name'])) . " ?? \"\"}}"
            ];
            $componentSingular = Str::singular(strtolower($field['name']));
            $componentSingularUCFirst = Str::singular(ucfirst($field['name']));
            $componentValue = $values[$field['type']] ?? "{{\$". $singular ."->". Str::singular(strtolower($field['name'])) . " ?? \"\"}}";
            $required = Arr::get($field, 'required', false) ? 'required' : '';

            $form .= self::{$field['type']}($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required);
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
        $scripts = self::createWyswigScripts(collect($json['fields']));
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