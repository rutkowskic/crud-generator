<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rcoder\CrudGenerator\Stubs;
use Rcoder\CrudGenerator\Helpers;
use Illuminate\Support\Facades\File;
use Rcoder\CrudGenerator\Stubs\ControllerStubs;

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

    static public function oneToOneRelations($relationsCollection)
    {
        $relations = '';
        $relations .= $relationsCollection->filter(fn($value, $key) => $value['type'] === 'onetoone' && !array_key_exists("where", $value['select']))->reduce(fn($string, $value) => $string .= "$".Str::plural(strtolower($value['model']))." = ".Str::singular(ucfirst($value['model']))."::all();\n", '');
        $relationWithWhereKey = $relationsCollection->filter(fn($value, $key) => $value['type'] === 'onetoone' && array_key_exists("where", $value['select']))->groupBy(fn ($item) => $item['select']['where']);
        foreach($relationWithWhereKey as $key => $value){
            $with = explode("|", $key);
            $models = collect($value)->implode('model', ', ');
            $relations .= "\$oneToOne".ucfirst($with[0]).ucfirst($with[1]).ucfirst($with[2])." = ".Str::singular(ucfirst($with[0]))."::with(['".$models."'])->where('".$with[1]."', '".$with[2]."')->first();\n";
        }
        return $relations;
    }

    static public function manyToManyRelations($relationsCollection)
    {
        $relations = $relationsCollection->filter(fn($value, $key) => $value['type'] === 'manytomany')->reduce(function ($string, $value) {
            if(array_key_exists("where", $value['select'])){
                $with = explode("|", $value['select']['where']);
                return $string .= "$".Str::plural(strtolower($value['model']))." = ".Str::singular(ucfirst($value['model']))."::with('". Str::plural(strtolower($with[0])) ."')->get();\n";
            }
            return $string .= "$".Str::plural(strtolower($value['model']))." = ".Str::singular(ucfirst($value['model']))."::all();\n";
        }, '');
        return rtrim($relations, "\n");
    }
    
    static public function createImportModels($relationsCollection)
    {
        $imports = $relationsCollection->reduce(fn($string, $relation) => $string .= "use App\\". Str::singular(ucfirst($relation['model'])) . ";\n");
        return rtrim($imports, "\n");
    }

    static public function createEditCompact($relationsCollection)
    {
        $variables = '';
        $variables .= $relationsCollection->filter(fn($value, $key) => $value['type'] === 'manytomany' && !array_key_exists("where", $value['select']))->reduce(fn($start, $item) => $start ."'".$item['model']."', ");
        $variables .= $relationsCollection->filter(fn($value, $key) => $value['type'] === 'onetoone' && !array_key_exists("where", $value['select']))->reduce(fn($start, $value) => $start ."'".$value['model']."', ");
        $variables .= $relationsCollection->filter(fn($value, $key) => $value['type'] === 'onetoone' && array_key_exists("where", $value['select']))->groupBy(fn($item) => $item['select']['where'])->keys()->reduce(function($string, $value){
            $with = explode("|", $value);
            return $string .= "'oneToOne".ucfirst($with[0]).ucfirst($with[1]).ucfirst($with[2])."', ";
        });

        return rtrim($variables, ', ');
    }

    static function createEditModel($singular, $plural, $singularUCFirst, $json)
    {
        if(!empty($json['relations'])){
            $models = collect($json['relations'])->filter(fn($value, $key) => $value['type'] === 'manytomany')->reduce(fn($start, $item) => $start ."'".$item['model']."',");
            return "$".$singular." = ".$singularUCFirst."::with([". rtrim($models, ',') ."])->where('id', $".$singular.")->first();";
        };
        return "$" .$singular. " = " .$singularUCFirst. "::find(" .$singular. ");";
    }

    static private function createFileTemplates($singular, $plural, $json)
    {
        $fileFields = collect($json['fields'])->filter(fn($value, $key) => $value['type'] === 'file');
        $createFiles = $fileFields->reduce(function($start, $item) use ($plural){ 
            $start .= <<<EOD
            if (\$request->hasFile('{$item['name']}')) {
                \$data['{$item['name']}'] = \$request->file('{$item['name']}')->store('{$plural}');
            }
            EOD;
        });
        $updateFiles = $fileFields->reduce(function($start, $item) use ($singular, $plural){ 
            $start .= <<<EOD
            if (\$request->hasFile('{$item['name']}')) {
                \$data['{$item['name']}'] = \$request->file('{$item['name']}')->store('{$plural}');
                Storage::delete(\${$singular}->{$item['name']});
            }
            EOD;
        });
        $deleteFiles = $fileFields->reduce(function($start, $item) use ($singular, $plural){ 
            $start .= <<<EOD
            Storage::delete(\${$singular}->{$item['name']});
            EOD;
        });

        return [$createFiles, $updateFiles, $deleteFiles];
    }

    static private function createIndexModel($singular, $plural, $singularUCFirst, $json)
    {
        if(array_key_exists('index', $json)){
            $where = explode("|", $json['index']['where']);
            return "\$".$plural." = ".$singularUCFirst."::with('".Str::plural(strtolower($where[0]))."')->latest()->paginate(10);";
        }
        return "\$".$plural." = ".$singularUCFirst."::latest()->paginate(10);";
    }

    static public function init($json)
    {
        $singular = Str::singular(strtolower($json['model']));
        $plural = Str::plural(strtolower($json['model']));
        $singularUCFirst = Str::singular(ucfirst($json['model']));
        $pluralUCFirst = Str::plural(ucfirst($json['model']));
        $indexModel = self::createIndexModel($singular, $plural, $singularUCFirst, $json);
        [$createFiles, $updateFiles, $deleteFiles] = self::createFileTemplates($singular, $plural, $json);
        $relationsCollection = collect($json['relations']);
        $editModel = self::createEditModel($singular, $plural, $singularUCFirst, $json); 
        $importModels = self::createImportModels($relationsCollection);
        $oneToOneRelations = self::oneToOneRelations($relationsCollection); 
        $manyToManyRelations = self::manyToManyRelations($relationsCollection); 
        $editCompact = self::createEditCompact($relationsCollection); 
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

