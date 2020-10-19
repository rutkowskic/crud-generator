<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OneToOne
{
    static function createForEachVariable($relationPlural, $relation)
    {
        if( array_key_exists("where", $relation['select']) )
        {
            $with = explode("|", $relation['select']['where']);
            return "\$oneToOne".ucfirst($with[0]).ucfirst($with[1]).ucfirst($with[2])."->".$relationPlural;
        }
        return "\$".$relationPlural;
        
    }

    static function createNameOption($relationSingular, $relation)
    {
        if( array_key_exists("where", $relation['select']) )
        {
            return "\$".$relationSingular."->pivot->".$relation['select']['name'];
        }
        return "\$".$relationSingular."->".$relation['select']['name'];
    }

    static function init($singular, $plural, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst, $relation)
    {
        $forEachVariable = self::createForEachVariable($relationPlural, $relation);
        $nameOption = self::createNameOption($relationSingular, $relation);
        return <<<EOD
<div class="card">
    <div class="card-header">
    {$relationSingularUCFirst}
    </div>
    <div class="card-body">
    <form method="post" action="{{ route('{$plural}.{$relationSingular}', ['{$singular}' => \${$singular}->id]) }}">
        @csrf
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <label class="input-group-text" for="{$relationSingular}">{$relationSingularUCFirst}</label>
            </div>
            <select class="custom-select" id="{$relationSingular}" name="{$relationSingular}">
                @foreach({$forEachVariable} as \${$relationSingular})
                <option value="{{\${$relationSingular}->id}}" {{\${$singular}->{$relationSingular}_id === \${$relationSingular}->id ? 'selected' : ''}}>{{{$nameOption}}}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-success">Update</button>
    </form>
    </div>
</div>
</br>
EOD;
    }
}