<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Stubs;

class OneToMany
{
    use Stubs;

    static function updateComponentValue($relationSingular, $componentSingular, $field)
    {
        return $field['type'] == 'textarea' ? 
        "{{ isset($" . $relationSingular . "->pivot->" . $componentSingular . ") ? $". $relationSingular . "->" . $componentSingular . ": '' }}" 
        : "{{\$". $relationSingular . "->". $componentSingular . "}}";
    }

    static function createRelationForm($singular, $plural, $relationSingular, $relation, $type)
    {
        $relationForm = '';
        
        if(array_key_exists('fields', $relation)){
            foreach($relation['fields'] as $field){
                $componentSingular = Str::singular(strtolower($field['name']));
                $componentPluralUCFirst = Str::plural(ucfirst($field['name']));
                $componentSingularUCFirst = Str::singular(ucfirst($field['name']));
                $componentValue = $type == 'create' ? '' : self::updateComponentValue($relationSingular, $componentSingular, $field);
                $required = '';
            
                $relationForm .= self::{$field['type']}($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required);
                $relationForm .= "\n";
            }
        }

        return rtrim($relationForm, "\n");
    }

    static function init($singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation, $json)
    {
        $relationTh = Str::singular(ucfirst($relation['select']['name']));
        $relationTd = Str::singular(strtolower($relation['select']['name']));
        $createRelationForm = self::createRelationForm($singular, $plural, $relationSingular, $relation, 'create');
        $updateRelationForm = self::createRelationForm($singular, $plural, $relationSingular, $relation, 'update');
        return <<<EOD
<div class="card mt-3">
    <div class="card-header">
        {$relationPluralUCFirst}
    </div>
    <div class="card-body">
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#{$relationSingular}-create">Create</button>
        <br>
        <div class="modal fade" id="{$relationSingular}-create" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Create {$relationSingularUCFirst}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="{{ route('{$plural}.create-{$relationSingular}', ['{$singular}' => \${$singular}->id]) }}" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            {$createRelationForm}
                            <button type="submit" class="btn btn-primary">Create</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <table class="table mt-3">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope='row'>{$relationTh}</th>
                    <th scope="col">Edit/Remove</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\${$singular}->{$relationPlural} as \${$relationSingular})
                <tr>
                    <th scope="row">{{\$loop->index + 1}}</th>
                    <td scope='col'>{{\${$relationSingular}->{$relationTd}}}</td>
                    <td>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target=#{$relationSingular}-{{\${$relationSingular}->id}}>Edit</button>
                    <form style="display: inline;" action="{{ route('{$plural}.remove-{$relationSingular}', ['{$singular}' => \${$singular}->id, '{$relationSingular}' => \${$relationSingular}->id])  }}" method="post">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                    </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @foreach(\${$singular}->{$relationPlural} as \${$relationSingular})
        <div class="modal fade" id="{$relationSingular}-{{\${$relationSingular}->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{$relationSingularUCFirst}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="{{ route('{$plural}.update-{$relationSingular}', ['{$singular}' => \${$singular}->id, '{$relationSingular}' => \${$relationSingular}->id]) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            {$updateRelationForm}
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
<br/>
EOD;
    }
}