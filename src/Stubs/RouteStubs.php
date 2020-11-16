<?php

namespace Rcoder\CrudGenerator\Stubs;

trait RouteStubs{

    public static function onetoone($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst)
    {
        return <<<EOD
        Route::post('{$plural}/{{$singular}}/{$relationSingular}', '{$singularUCFirst}Controller@{$relationSingular}')->name('{$plural}.{$relationSingular}');
        EOD;
    } 

    public static function onetomany($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst)
    {
        return <<<EOD
        Route::post('{$plural}/{{$singular}}/{$relationSingular}', '{$singularUCFirst}Controller@create{$relationSingularUCFirst}')->name('{$plural}.create-{$relationSingular}');
        Route::put('{$plural}/{{$singular}}/{$relationSingular}/{{$relationSingular}}', '{$singularUCFirst}Controller@update{$relationSingularUCFirst}')->name('{$plural}.update-{$relationSingular}');
        Route::delete('{$plural}/{{$singular}}/{$relationSingular}/{{$relationSingular}}', '{$singularUCFirst}Controller@remove{$relationSingularUCFirst}')->name('{$plural}.remove-{$relationSingular}');
        EOD;
    }

    public static function manytomany($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst)
    {
        return <<<EOD
        Route::post('{$plural}/{{$singular}}/{$relationPlural}', '{$singularUCFirst}Controller@attach{$relationSingularUCFirst}')->name('{$plural}.attach-{$relationSingular}');
        Route::put('{$plural}/{{$singular}}/{$relationPlural}/{{$relationSingular}}', '{$singularUCFirst}Controller@update{$relationSingularUCFirst}')->name('{$plural}.update-{$relationSingular}');
        Route::delete('{$plural}/{{$singular}}/{$relationPlural}/{{$relationSingular}}', '{$singularUCFirst}Controller@detach{$relationSingularUCFirst}')->name('{$plural}.detach-{$relationSingular}');
        EOD;
    } 

}