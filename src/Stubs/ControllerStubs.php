<?php

namespace Rcoder\CrudGenerator\Stubs;
use Illuminate\Support\Str;

trait ControllerStubs{
    
    static public function relationFields($relation)
    {
        if(array_key_exists("fields", $relation)){
            return ", \$request->only('". collect($relation['fields'])->implode('name', ', ') ."')";
        }
        return "";
    }

    static public function makeFilesStubForOneToOne($relation)
    {
        $filesStub = '';
        $removeStub = '';
        $filteredFiles = collect($relation['fields'])->filter(function($field){
            return $field['type'] == 'file';
        });
        foreach($filteredFiles as $file){
            $modelNamePlural = Str::plural($relation['model']);
            $modelNameSingular = Str::singular($relation['model']);
            $fieldName = Str::singular($file['name']);
            $filesStub .= <<<EOD
            if (\$request->hasFile('{$fieldName}')) {
                \$data['{$fieldName}'] = \$request->file('{$fieldName}')->store('{$modelNamePlural}');
            }
            EOD;
            $removeStub .= <<<EOD
            Storage::delete(\${$modelNameSingular}->{$fieldName});
            EOD;
        }
        return [$filesStub, $removeStub];
    }

    static private function onetomany($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationSingularUCFirst, $relationPlural, $relation)
    {
        [$filesStub, $removeStub] = self::makeFilesStubForOneToOne($relation);
        return <<<EOD
            public function create{$relationSingularUCFirst}(Request \$request, {$singularUCFirst} \${$singular}){
                \$data = \$request->all();
                {$filesStub}
                \${$singular}->{$relationPlural}()->create(\$data);
                return back();
            }\n
            public function update{$relationSingularUCFirst}(Request \$request, {$singularUCFirst} \${$singular}, {$relationSingularUCFirst} \${$relationSingular}){
                \$data = \$request->all();
                {$filesStub}
                \${$relationSingular}->update(\$request->all());
                return back();
            }\n
            public function remove{$relationSingularUCFirst}(Request \$request, {$singularUCFirst} \${$singular}, {$relationSingularUCFirst} \${$relationSingular}){
                {$removeStub}
                \${$relationSingular}->delete();
                return back();
            }
        EOD;
    }
    
    static private function onetoone($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationSingularUCFirst, $relationPlural, $relation)
    {
        $file = '';
        return <<<EOD
            public function $relationSingular(Request \$request, {$singularUCFirst} \${$singular}){
                \${$singular}->update(['{$relationSingular}_id'=>\$request->input('{$relationSingular}')]);
                return back();
            }\n
        EOD;
    }

    static private function manytomany($singular, $plural, $singularUCFirst, $pluralUCFirst, $relationSingular, $relationSingularUCFirst, $relationPlural, $relation)
    {
        $relationFields = self::relationFields($relation);
        return <<<EOD
        public function attach{$relationSingularUCFirst}(Request \$request, {$singularUCFirst} \${$singular})
            {
                \${$singular}->{$relationPlural}()->attach(\$request->{$relationSingular}{$relationFields});
                return back()->with('flash_message', '{$relationSingularUCFirst} attached!');
            }\n
            public function update{$relationSingularUCFirst}(Request \$request, {$singularUCFirst} \${$singular}, \${$relationSingular})
            {
                \${$singular}->{$relationPlural}()->updateExistingPivot(\${$relationSingular}{$relationFields});
                return back()->with('flash_message', '{$relationSingularUCFirst} updated!');
            }\n
            public function detach{$relationSingularUCFirst}(Request \$request, {$singularUCFirst} \${$singular}, \${$relationSingular})
            {
                \${$singular}->{$relationPlural}()->detach(\${$relationSingular});
                return back()->with('flash_message', '{$relationSingularUCFirst} detached!');
            }\n
        EOD;
    }

}