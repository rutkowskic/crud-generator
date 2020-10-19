<?php

namespace Rcoder\CrudGenerator;

use Rcoder\CrudGenerator\Stubs;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;

class Create {

    use Stubs;

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
            $componentSingular = Str::singular(strtolower($field['name']));
            $componentSingularUCFirst = Str::singular(ucfirst($field['name']));
            $componentValue = '';
            $required = Arr::get($field, 'required', false) ? 'required' : '';
            $form .= self::{$field['type']}($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required);
            $form .= "\n";
        }

        return rtrim($form, "\n");
    }
    
    static public function init($json)
    {
        $singular = Str::singular(strtolower($json['model'])); //post
        $plural = Str::plural(strtolower($json['model'])); //posts
        $singularUCFirst = Str::singular(ucfirst($json['model'])); //Post
        $pluralUCFirst = Str::plural(ucfirst($json['model'])); //Posts
        $form = self::createForm($singular, $json['fields']);
        $scripts = self::createWyswigScripts($json['fields']);

        $createTemplate = <<<EOD
@extends('layouts.admin')

@section('content')
<div>
    <div class="card">
        <div class="card-header">
            New {$singularUCFirst}
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('{$plural}.store') }}" enctype="multipart/form-data">
                @csrf
                $form
                <button class="btn btn-primary btn-block mt-4" type="submit">Create</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{$scripts}
@endsection
EOD;
        File::put(resource_path('views/admin/'.$plural.'/create.blade.php'), $createTemplate );        
    }
}