<?php

namespace Rcoder\CrudGenerator\Console\Commands;

use Rcoder\CrudGenerator\Edit;
use Illuminate\Console\Command;

use Rcoder\CrudGenerator\Index;
use Rcoder\CrudGenerator\Stubs;
use Rcoder\CrudGenerator\Create;
use Rcoder\CrudGenerator\Helpers;
use Rcoder\CrudGenerator\Layout;
use Rcoder\CrudGenerator\Router;
use Rcoder\CrudGenerator\Folders;
use Illuminate\Support\Facades\File;
use Rcoder\CrudGenerator\Controller;

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

        Helpers::makeDirectory(resource_path('views/admin'));
        Helpers::makeDirectory(app_path('Http/Controllers/Admin'));

        $folderContents = scandir( config('crud.jsons') );
        $files = array_diff($folderContents, array('.', '..'));
        foreach( $files as $file) {
            $model = File::get( config('crud.jsons') . $file);
            array_push($jsons, json_decode($model, true) ); 
        };
        foreach( $jsons as $json) {
            Index::init($json);
            Controller::init($json);
            Create::init($json);
            Edit::init($json);
        };
        Layout::init($jsons);
        Router::init($jsons);
    }
}
