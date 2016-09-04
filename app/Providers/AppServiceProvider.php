<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

use Less_Parser;
use App;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Add code for compiling less files
        $appCss = public_path() . '/css/app.min.css';
        // Only compile if there is no app.min.css or we
        // are in debug
        if((!File::exists($appCss)) || App::environment('local'))
        {
            // Create a new minifying parser
            $parser = new Less_Parser([ 'compress' => true ]);

            // Parse the bootstrap.less-file
            $parser->parseFile(base_path() . '/resources/assets/less/main.less');
            // (over-)write app.css
            $bytes_written = File::put($appCss, $parser->getCss());

            if(!$bytes_written)
                throw new Exception('Could not write new CSS file!');

        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
