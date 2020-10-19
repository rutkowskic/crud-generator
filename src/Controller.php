<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Stubs;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;
use Rcoder\CrudGenerator\ControllerStubs;

class Controller {

    use ControllerStubs;

    static public function createRelations($singular, $plural, $singularUCFirst, $pluralUCFirst, $json)
    {
       $relations = '';
       if(array_key_exists("relations", $json)){
           foreach($json['relations'] as $relation){
            ['model' => $model, 'type' => $type] = $relation;
                $relationSingular = Str::singular($model);
                $relationSingularUCFirst = Str::singular(ucfirst($model));
                $relationPlural = Str::plural(strtolower($model));
                $relations .= self::{$type}($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationSingularUCFirst, $relationPlural, $relation);
                $relations .= "\n";
           }
       }
       return $relations;
    }

    static public function oneToOneRelations($json)
    {
        $relations = '';
        $relationHasWhereKey = collect($json['relations'])->filter(function ($value, $key) use ($relations){
            return $value['type'] === 'onetoone' && array_key_exists("where", $value['select']);
        })->groupBy(function ($item) use ($relations){
            return $item['select']['where'];
        })->all();
        $relationHasNotWhereKey = collect($json['relations'])->filter(function ($value, $key) use ($relations){
            return $value['type'] === 'onetoone' && !array_key_exists("where", $value['select']);
        })->all();
        foreach($relationHasWhereKey as $key => $value){
            $with = explode("|", $key);
            $models = collect($value)->implode('model', ', ');
            $relations .= "\$oneToOne".ucfirst($with[0]).ucfirst($with[1]).ucfirst($with[2])." = ".Str::singular(ucfirst($with[0]))."::with(['".$models."'])->where('".$with[1]."', '".$with[2]."')->first();\n";
        }
        foreach($relationHasNotWhereKey as $value){
            $relations .= "$".Str::plural(strtolower($value['model']))." = ".Str::singular(ucfirst($value['model']))."::all();\n";
        }
        return $relations;
    }

    static public function manyToManyRelations($json)
    {
        $relations = '';
        $models = collect($json['relations'])->filter(function ($value, $key) {
            return $value['type'] === 'manytomany';
        });
        foreach($models->all() as $relation){
            //change many to many relations where is where key
            if(array_key_exists("where", $relation['select'])){
                $with = explode("|", $relation['select']['where']);
                $relations .= "$".Str::plural(strtolower($relation['model']))." = ".Str::singular(ucfirst($relation['model']))."::with('". Str::plural(strtolower($with[0])) ."')->get();\n";
            }
            $relations .= "$".Str::plural(strtolower($relation['model']))." = ".Str::singular(ucfirst($relation['model']))."::all();\n";
        }
        return rtrim($relations, "\n");
    }
    
    static public function createImportModels($json)
    {
        $imports = collect($json['relations'])->reduce(function ($start, $relation) {
            return $start . "use App\\".Str::singular(ucfirst($relation['model'])).";\n";
        }, '');
        return rtrim($imports, "\n");
    }

    static public function createEditCompact($json)
    {
        $variables = '';

        $oneToOne = collect($json['relations'])->filter(function ($value, $key) {
            return $value['type'] === 'onetoone' && array_key_exists("where", $value['select']);
        })->groupBy(function ($item){
            return $item['select']['where'];
        })->keys();
   
        foreach($oneToOne as $key => $value){
            $with = explode("|", $value);
            $variables .= "'oneToOne".ucfirst($with[0]).ucfirst($with[1]).ucfirst($with[2])."', ";
        }

        $variables .= collect($json['relations'])->filter(function ($value, $key){
            return $value['type'] === 'onetoone' && !array_key_exists("where", $value['select']);
        })->reduce(function ($start, $item) {
            return $start ."'".$item['model']."', ";
        }, '');

        $variables .= collect($json['relations'])->filter(function ($value, $key) {
            return $value['type'] === 'manytomany' && !array_key_exists("where", $value['select']);
        })->reduce(function ($start, $item) {
            return $start ."'".$item['model']."', ";
        }, '');

        return rtrim($variables, ', ');
    }

    static function createEditModel($singular, $plural, $singularUCFirst, $json)
    {
        if(!empty($json['relations'])){
            $models = collect($json['relations'])->filter(function ($value, $key) {
                return $value['type'] === 'manytomany';
            })->reduce(function ($start, $item) {
                return $start ."'".$item['model']."',";
            }, '');
            return "$".$singular." = ".$singularUCFirst."::with([". rtrim($models, ',') ."])->where('id', $".$singular.")->first();";
        };
        return "$" .$singular. " = " .$singularUCFirst. "::find(" .$singular. ");";
    }

    static private function createFileTemplates($singular, $plural, $json)
    {
        $createFiles = '';
        $updateFiles = '';
        $deleteFiles = '';

        foreach(Helpers::getFromFields($json['fields'], 'type', 'file') as ['name' => $name]){
            $createFiles .= <<<EOD
            if (\$request->hasFile('{$name}')) {
                \$data['{$name}'] = \$request->file('{$name}')->store('{$plural}');
            }
            EOD;
            $updateFiles .= <<<EOD
            if (\$request->hasFile('{$name}')) {
                \$data['{$name}'] = \$request->file('{$name}')->store('{$plural}');
                Storage::delete(\${$singular}->{$name});
            }
            EOD;
            $deleteFiles .= <<<EOD
            Storage::delete(\${$singular}->{$name});
            EOD;
        }

        return [$createFiles, $updateFiles, $deleteFiles];
    }

    static private function createIndexModel($singular, $plural, $singularUCFirst, $json)
    {
        if(array_key_exists('index', $json)){
            $where = explode("|", $json['index']['where']);
            return "\$".$plural." = ".$singularUCFirst."::with('".Str::plural(strtolower($where[0]))."')->latest()->paginate(25);";
        }
        return "\$".$plural." = ".$singularUCFirst."::latest()->paginate(25);";
    }

    static public function init($json)
    {
        $singular = Str::singular(strtolower($json['model']));
        $plural = Str::plural(strtolower($json['model']));
        $singularUCFirst = Str::singular(ucfirst($json['model']));
        $pluralUCFirst = Str::plural(ucfirst($json['model']));
        $indexModel = self::createIndexModel($singular, $plural, $singularUCFirst, $json);
        [$createFiles, $updateFiles, $deleteFiles] = self::createFileTemplates($singular, $plural, $json);

        $editModel = self::createEditModel($singular, $plural, $singularUCFirst, $json);
        $importModels = self::createImportModels($json);
        $oneToOneRelations = self::oneToOneRelations($json);
        $manyToManyRelations = self::manyToManyRelations($json);
        $editCompact = self::createEditCompact($json);
        $relations = self::createRelations($singular, $plural, $singularUCFirst, $pluralUCFirst, $json);

        $controllerTemplate = <<<EOD
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\\{$singularUCFirst};
{$importModels}

class {$singularUCFirst}Controller extends Controller
{   
    public function index()
    {
        {$indexModel}
        return view('admin.{$plural}.index', compact('{$plural}'));
    }

    public function create()
    {
        return view('admin.{$plural}.create');
    }

    public function store(Request \$request)
    {
        \$data = \$request->all();
        {$createFiles}
        {$singularUCFirst}::create(\$data);
        return redirect('admin/{$plural}')->with('flash_message', '{$singularUCFirst} added!');
    }

    public function show({$singularUCFirst} \${$singular})
    {
        return view('admin.{$plural}.show', compact('{$singular}'));
    }

    public function edit(\${$singular})
    {
        {$editModel}
        {$oneToOneRelations}
        {$manyToManyRelations}
        return view('admin.{$plural}.edit', compact('{$singular}', {$editCompact}));
    }

    public function update(Request \$request, {$singularUCFirst} \${$singular})
    {
        \$data = \$request->all();
        {$updateFiles}
        \${$singular}->update(\$data);
        return redirect('admin/{$plural}')->with('flash_message', '{$singularUCFirst} updated!');
    }

    public function destroy({$singularUCFirst} \${$singular})
    {
        {$deleteFiles}
        \${$singular}->delete();
        return redirect('admin/{$plural}')->with('flash_message', '{$singularUCFirst} deleted!');
    }
    
    {$relations}
}
EOD;
        Helpers::makeDirectory(app_path('Http/Controllers/Admin'));
        File::put(app_path('Http/Controllers/Admin/' . $singularUCFirst . 'Controller.php'), $controllerTemplate );
    }
}

