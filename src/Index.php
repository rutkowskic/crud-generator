<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;

class Index {

    static public function createTbodys($singular, $json){
        $tbodys = "";

        if($json->has('index')){
            $where = explode("|", $json['index']['where']);
            $tbodys .= "<td scope=\"row\">{{isset(\$".$singular."->".Str::plural(strtolower($where[0]))."->where('".Str::singular(strtolower($where[1]))."', '".Str::singular(strtolower($where[2]))."')->first()->pivot) ? \$".$singular."->".Str::plural(strtolower($where[0]))."->where('".Str::singular(strtolower($where[1]))."', '".Str::singular(strtolower($where[2]))."')->first()->pivot->".$json['index']['name']." : ''}}</th>";
        }

        $tbodys .= collect($json['fields'])
        ->filter(fn($value, $key) => isset($value['active']) && $value['active'] === true)
        ->reduce(fn($start, $item) => $start .= "<td scope='row'>{{\$". $singular ."->". Str::singular(strtolower($item['name'])) ."}}</td>\n");
        
        return rtrim($tbodys, "\n");
    }

    static public function createTheads($json){
        $theads = "";

        if($json->has('index')){
            $theads .= "<th scope=\"col\">".ucfirst($json['index']['name'])."</th>";
        }
        
        $theads .= collect($json['fields'])
        ->filter(fn($value, $key) => isset($value['active']) && $value['active'] === true)
        ->reduce(fn($start, $item) => $start .= "<th scope='col'>" . Str::singular(ucfirst($item['name'])) . "</th>\n");

        return rtrim($theads, "\n");
    }
    
    static public function init($json)
    {
        $singular = Str::singular(strtolower($json['model'])); 
        $plural = Str::plural(strtolower($json['model']));
        $singularUCFirst = Str::singular(ucfirst($json['model']));
        $pluralUCFirst = Str::plural(ucfirst($json['model']));
        $thead = self::createTheads(collect($json));
        $tbody = self::createTbodys($singular, collect($json));

        $indexTemplate = <<<EOD
    @extends('layouts.admin')

    @section('content')
    <h2>{$singularUCFirst}</h2>
    <a href="{{ route( '{$plural}.create' ) }}" class="btn btn-success btn-lg btn-block">New</a>
    <br>
        <table class="table">
        <thead>
            <tr>
            <th scope="col">#</th>
            {$thead}
            <th scope="col">Created at</th>
            <th scope="col">Edit</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\${$plural} as \${$singular})
            <tr>
                <th scope="row">{{\$loop->index + 1}}</th>
                {$tbody}
                <td scope="row">{{\${$singular}->created_at}}</th>
                <td>
                    <a href="{{ route( '{$plural}.edit', ['{$singular}' => \${$singular}->id] ) }}" class="btn btn-success">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>
        {{\${$plural}->links()}}
    @endsection
EOD;
        Helpers::makeDirectory(resource_path('views/admin/'.$plural));
        File::put( resource_path('views/admin/'.$plural.'/index.blade.php'), $indexTemplate );
    }
}