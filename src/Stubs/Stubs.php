<?php

namespace Rcoder\CrudGenerator\Stubs;

trait Stubs{

    static function number($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required)
    {
        return <<<EOD
        <div class="form-group">
            <label for="{$componentSingular}">{$componentSingularUCFirst}</label>
            <input type="number" id="{$componentSingular}" class="form-control" name="{$componentSingular}" value="{$componentValue}" {$required}>
        </div>\n
        EOD;
    }

    static function file($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required)
    {
        return <<<EOD
        <div class="form-group">
            <label for="{$componentSingular}">{$componentSingularUCFirst}</label>
            <input type="file" id="{$componentSingular}" class="form-control-file" name="{$componentSingular}" value="{$componentValue}" {$required}>
        </div>\n
        EOD;
    }

    static function radio($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required)
    {
        return <<<EOD
        <fieldset class="form-group mt-3">
            <div class="row">
            <legend class="col-form-label col-md-auto pt-0">{$componentSingularUCFirst}</legend>
            <div class="col-md-auto">
                <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="{$componentSingular}" id="{$componentSingular}-1" value="1" {{ (isset(\${$singular}) && 1 == \${$singular}->{$componentSingular}) ? 'checked' : '' }}>
                <label class="form-check-label" for="{$componentSingular}-1">
                    Yes
                </label>
                </div>
                <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="{$componentSingular}" id="{$componentSingular}-2" value="0" @if (isset(\${$singular})) {{ (0 == \${$singular}->{$componentSingular}) ? 'checked' : '' }} @else {{ 'checked' }} @endif>
                <label class="form-check-label" for="{$componentSingular}-2">
                    No
                </label>
                </div>
            </div>
            </div>
        </fieldset>\n
        EOD;
    }

    static function text($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required)
    {
        return <<<EOD
        <div class="form-group">
            <label for="{$componentSingular}">{$componentSingularUCFirst}</label>
            <input type="text" id="{$componentSingular}" class="form-control" name="{$componentSingular}" value="{$componentValue}" {$required}>
        </div>\n
        EOD;
    }

    static function textarea($singular, $componentSingular, $componentSingularUCFirst, $componentValue, $required)
    {
        return <<<EOD
        <div class="form-group">
            <label for="{$componentSingular}">{$componentSingularUCFirst}</label>
            <textarea rows="9" id="{$componentSingular}" class="form-control" name="{$componentSingular}" {$required}>{$componentValue}</textarea>
        </div>\n
        EOD;
    }

}