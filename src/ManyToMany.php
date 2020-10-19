<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Stubs;

class ManyToMany
{
    use Stubs;

    static function updateComponentValue($relationSingular, $componentSingular, $field)
    {
        return $field['type'] == 'textarea' ? 
        "{{ isset($" . $relationSingular . "->pivot->" . $componentSingular . ") ? $". $relationSingular . "->pivot->" . $componentSingular . ": '' }}" 
        : "{{\$". $relationSingular . "->pivot->". $componentSingular . "}}";
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

    static function createPivotTableElements($relation)
    {
        ['model' => $model, 'fields' => $fields] = $relation;
        $theads = "";
        $tbodys = "";

        foreach(Helpers::getWhenKeyIsTrue($fields, 'active') as $active){
            $theads .= "<th scope='col'>" . Str::singular(ucfirst($active['name'])) . "</th>\n";
            $tbodys .= "<td scope='row'>{{\$". Str::singular(strtolower($model)) ."->pivot->". Str::singular(strtolower($active['name'])) ."}}</td>\n";
        } 

        return ['theads' => $theads, 'tbodys' => $tbodys];
    }
    
    static function createSelectProporties($relationSingular, $relation)
    {
        if(array_key_exists("where" , $relation['select'])){
            $where = explode("|", $relation['select']['where']);
            return "\$".$relationSingular."->".$where[0]."->where('".$where[1]."', '".$where[2]."')->first()->pivot->".$relation['select']['name'];
        }
        return "\$".$relationSingular . "->" .  $relation['select']['name'];
    }
    
    static function createRelationTd($relationSingular, $relation)
    {
        if(array_key_exists("where" , $relation['select'])){
            $where = explode("|", $relation['select']['where']);
            return "<td scope='col'>{{\$".$relationSingular."->".$where[0]."->where('".$where[1]."', '".$where[2]."')->first()->pivot->".$select['name'] ."}}</td>\n";
        }
        return "<td scope='col'>{{\$". $relationSingular ."->". Str::singular(strtolower($relation['select']['name'])) ."}}</td>\n"; 
    }
    
    static function init($singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation)
    {
        $relationTh = Str::singular(ucfirst($relation['select']['name']));
        $relationTd = self::createRelationTd($relationSingular, $relation);
        $relationSelectProporties = self::createSelectProporties($relationSingular, $relation);
        $createRelationForm = self::createRelationForm($singular, $plural, $relationSingular, $relation, 'create');
        $updateRelationForm = self::createRelationForm($singular, $plural, $relationSingular, $relation, 'update');;
        return <<<EOD
<div class="card mt-3">
    <div class="card-header">
        {$relationPluralUCFirst}
    </div>
    <div class="card-body">
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#{$relationPlural}-attach">Attach</button>
        <br>
        <div class="modal fade" id="{$relationPlural}-attach" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Attach {$relationPluralUCFirst}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="{{ route('{$plural}.attach-{$relationSingular}', ['{$singular}' => \${$singular}->id]) }}">
                            @csrf
                            @method('POST')
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <label class="input-group-text" for="{$relationPlural}-select">{$relationSingularUCFirst}</label>
                                </div>
                                <select class="custom-select" id="{$relationPlural}-select" name="{$relationSingular}">
                                    @foreach(\${$relationPlural} as \${$relationSingular})
                                    @if(!in_array(\${$relationSingular}->id, \${$singular}->{$relationPlural}->modelKeys()))
                                        <option value="{{\${$relationSingular}->id}}">{{{$relationSelectProporties}}}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                            {$createRelationForm}
                            <button type="submit" class="btn btn-primary">Attach</button>
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
                    <th scope="col">Edit/Detach</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\${$singular}->{$relationPlural} as \${$relationSingular})
                <tr>
                    <th scope="row">{{\$loop->index + 1}}</th>
                    {$relationTd}
                    <td>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target=#{$relationSingular}-{{\${$relationSingular}->id}}>Edit</button>
                    <form style="display: inline;" action="{{ route('{$plural}.detach-{$relationSingular}', ['{$singular}' => \${$singular}->id, '{$relationSingular}' => \${$relationSingular}->id])  }}" method="post">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">Detach</button>
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
                        <form method="post" action="{{ route('{$plural}.update-{$relationSingular}', ['{$singular}' => \${$singular}->id, '{$relationSingular}' => \${$relationSingular}->id]) }}">
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