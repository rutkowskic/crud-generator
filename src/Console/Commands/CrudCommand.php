<?php

namespace Rcoder\CrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use Rcoder\CrudGenerator\Controller;
use Rcoder\CrudGenerator\Folders;
use Rcoder\CrudGenerator\Index;
use Rcoder\CrudGenerator\Create;
use Rcoder\CrudGenerator\Edit;
use Rcoder\CrudGenerator\Layout;
use Rcoder\CrudGenerator\Router;

class CrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates CRUD for model';

    private $file;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $jsons = [];

        if(!File::exists( resource_path('views/admin') )){
            File::makeDirectory(resource_path('views/admin'));
        }

        if(!File::exists(app_path('Http/Controllers/Admin'))){
            File::makeDirectory(app_path('Http/Controllers/Admin'));
        }

        $folderContents = scandir( config('crud.jsons') );
        $files = array_diff($folderContents, array('.', '..'));
        foreach( $files as $file) {
            $model = File::get( config('crud.jsons') . $file);
            array_push($jsons, json_decode($model, true) ); 
        };
        foreach( $jsons as $json) {
            $controller = new Controller($json);
            $controller->init();
            $index = new Index($json);
            $index->init();
            $create = new Create($json);
            $create->init();
            $edit = new Edit($json);
            $edit->init();
        };
        
        $Layout = new Layout($jsons);
        $Layout->init();
        $router = new Router($jsons);
        $router->init();
    }
}
