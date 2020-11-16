<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;
use Rcoder\CrudGenerator\Stubs\RouteStubs;

class Router {

    use RouteStubs;

    public static function createRoutes($jsons){
        $routes = $jsons->reduce(fn($start, $item) => "Route::resource('".Str::plural(strtolower($item['model']))."', '".Str::singular(ucfirst($item['model']))."Controller');\n");
        return rtrim($routes, "\n");
    }

    public static function createRelationRoutes($jsons){
        $relationRoutes = '';
        foreach($jsons as $json){
            if(array_key_exists("relations", $json)){
                foreach($json['relations'] as $relation){
                    $singular = Str::singular(strtolower($json['model']));
                    $plural = Str::plural(strtolower($json['model']));
                    $singularUCFirst = Str::singular(ucfirst($json['model']));
                    $pluralUCFirst = Str::plural(ucfirst($json['model']));
                    $relationSingular = Str::singular(strtolower($relation['model']));
                    $relationPlural = Str::plural(strtolower($relation['model']));
                    $relationSingularUCFirst = Str::singular(ucfirst($relation['model']));
                    $relationPluralUCFirst = Str::plural(ucfirst($relation['model']));
                    $relationRoutes .= self::{$relation['type']}($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationPlural, $relationSingularUCFirst, $relationPluralUCFirst);
                    $relationRoutes .= "\n";
                }
            }
        };
        return rtrim($relationRoutes, "\n");
    }

    public static function init($jsons)
    {
        $routes = self::createRoutes(collect($jsons));
        $relationRoutes = self::createRelationRoutes(collect($jsons));
        
        $stubRoutes = <<<EOT
Route::prefix('admin')->group(function () {
    Route::group(['namespace' => 'Admin'], function () {
        Route::get('/', function(){
            return view('admin.dashboard');
        })->name('dashboard');
        {$routes}
        {$relationRoutes}
    });
    Auth::routes();
});
EOT;
        file_put_contents(base_path('routes/web.php'), PHP_EOL . $stubRoutes, FILE_APPEND);
    }
}