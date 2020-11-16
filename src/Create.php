<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;
use Rcoder\CrudGenerator\Stubs\Stubs;

class Create {

    use Stubs;

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
            $componentSingular = Str::singular(strtolower($field['name']));
            $componentSingularUCFirst = Str::singular(ucfirst($field['name']));
            $componentValue = '';
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