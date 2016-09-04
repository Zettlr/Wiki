<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

use App\Http\Requests;

use App\PageIndex;

use Validator;

class Backend extends Controller
{
    /**
    * Displays an initial "dashboard"
    * @return View The dashboard view
    */
    public function index()
    {
        $page = new \stdClass();
        $stats = new \stdClass();
        $page->title = "Dashboard";

        // Let's populate the dashboard with some statistics!
        $stats->indexedPages = number_format(PageIndex::all()->count());
        $stats->indexedWords = number_format($this->countIndexedWords());
        $stats->dbSize = $this->format_size(File::size(database_path() . '/' . env('DB_DATABASE', '')));
        $stats->cacheSize = $this->format_size($this->getCacheSize());

        return view('admin.dashboard', compact('page', 'stats'));
    }

    /**
    * Displays a general settings pane
    * @return View   The settings pane
    */
    public function settings()
    {
        $page = new \stdClass();
        $page->title = "Settings";
        // Our view expects a settings collection with all informations
        $settings = new \stdClass();

        $settings->indexedPages = number_format(PageIndex::all()->count());
        $settings->indexedWords = number_format($this->countIndexedWords());

        return view('admin.settings', compact('settings', 'page'));
    }

    public function advancedSettings()
    {
        $page = new \stdClass();
        $page->title = "Advanced Settings";

        return view('admin.expert-settings');
    }

    /**
    * Saves the settings to an environment file
    * @param  Request $request The request object
    * @return Redirect           Redirection to settings page
    */
    public function saveSettings(Request $request)
    {
        $page = new \stdClass();
        $page->title = "Settings";

        // First evaluate what we got.
        // Create a validator
        $validator = Validator::make($request->all(), [

            'APP_TITLE' => 'max:255|string',
            'DB_DATABASE' => 'string',
            'LOG_FILES' => 'integer'
        ]);

        if ($validator->fails()) {
            return redirect('/admin/settings')
            ->withErrors($validator)
            ->withInput();
        }

        // Now we have to check whether or not APP_URL, DB_CONNECTION or
        // anything else is either IP or URL
        if($request->has('APP_URL') && $request->has('DB_CONNECTION')) {
            $appurl_correct = $this->validateIPorURL($request->APP_URL);
            $dbconn_correct = ($request->DB_CONNECTION == 'sqlite' || $request->DB_CONNECTION == 'mysql');

            if(!($appurl_correct && $dbconn_correct)) {
                return redirect('/admin/settings')
                ->withErrors(['APP_URL' => 'The App URL must either be IP or URL',
                'DB_CONNECTION' => 'The Database connection must either be SQLite or MySQL'])
                ->withInput();
            }
        }

        // Now create the new contents

        // First read in the current .env-file
        $oldEnv = File::get(base_path() . '/.env');
        // Arrayfy the file
        $env = [];
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $oldEnv) as $line){
            $i = 0;
            $fieldname = '';
            $value = '';

            for(; $i < strlen($line); $i++)  {
                if($line[$i] !== '=') {
                    $fieldname .= $line[$i];
                } else {
                    break;
                }
            }
            $value = substr($line, $i+1);

            if(strlen($fieldname) > 0) {
                $env[$fieldname] = $value;
            }
        }

        // Now write all fields with the new values
        foreach($request->all() as $name => $field) {
            if(array_key_exists($name, $env)) {
                if($field == "") {
                    // Empty fields must be zero
                    $field = "null";
                }
                if( preg_match('/\s/',$field)) {
                    // Whitespace-containing fields must be escaped
                    $field = '"' . $field . '"';
                }
                // Also any potential special chars MUST be escaped as well
                $env[$name] = $field;
            }
        }

        // Re-stringify the file
        $newEnv = "";
        foreach($env as $key => $value) {
            $newEnv .= $key . '=' . $value . "\n";
        }

        // And write it!
        $written = File::put(base_path() . '/.env', $newEnv);
        if($written) {
            return redirect('/admin/settings');
        } else {
            return redirect('/admin/settings')->withErrors(['save' => 'Something went wrong writing the .env-file!']);
        }
    }

    public function backupDatabase()
    {
        $database = File::get(database_path() . '/' . env('DB_DATABASE', ''));

        return response($database, 200)
        ->header('Content-Type', 'application/octet-stream')
        ->header('Content-Disposition', 'attachment; filename="' . env('DB_DATABASE', '') . '"');
    }

    public function flushViews()
    {
        // Run the artisan command
        Artisan::call('view:clear');

        // And return
        return redirect('/admin');
    }

    public function logs($flags = "tail")
    {
        $page = new \stdClass();
        $page->title = 'Logs';

        // This function displays all logs.
        $logfiles = File::allFiles(storage_path() . '/logs');

        $lastmod = 0;
        $latest = "";

        // Get the logfile with the newest information
        foreach($logfiles as $file) {
            // Exclude any non-log files
            if(substr($file, -3) !== "log") {
                continue;
            }

            if(File::lastModified($file) > $lastmod) {
                $lastmod = File::lastModified($file);
                $latest = $file;
            }
        }

        // Now extract the tail of the latest logfile (if $flags != "all")
        if($flags == "tail") {
            $logcontent = File::get($latest);
            $tail = preg_split("/((\r?\n)|(\r\n?))/", $logcontent);
            // Get only the last 40 lines
            $firstline = sizeof($tail) - 40;
            $tail = array_slice($tail, $firstline);

            for($i = 0; $i < sizeof($tail); $i++) {
                $lines[$i] = new \stdClass();
                $lines[$i]->number = $firstline + $i;
                $lines[$i]->contents = $tail[$i];
            }
        }

        return view('admin.logs', compact('logfiles', 'lines', 'page'));
    }

    /************************************
    * After here only helper functions
    ************************************/

    /**
    * Custom validation function, returns true if $string is either IP or URL
    * @param  string $string The string to be checked
    * @return bool         Whether or not the string is either an IP or an URL
    */
    public function validateIPorURL($string)
    {
        return (filter_var($string, FILTER_VALIDATE_IP) xor filter_var($string, FILTER_VALIDATE_URL));
    }

    /**
    * Counts all page indices on word basis
    * @return int Number of indexed words
    */
    public function countIndexedWords()
    {
        // This function should return a count of all words
        $count = 0;

        $indices = PageIndex::all();

        foreach($indices as $index) {
            $wordlist = json_decode($index->wordlist, true);

            $count += sizeof($wordlist);
        }

        return $count;
    }

    /**
    * Returns the cache size by summing up all file sizes in the directories
    * @return int The actual size of all files
    */
    public function getCacheSize()
    {
        $files = File::allFiles(storage_path() . '/framework/views');

        $size = 0;
        foreach($files as $file) {
            $size += File::size($file);
        }

        return $size;
    }

    // Credits go to this post https://stackoverflow.com/a/8348396
    function format_size($size) {
        $units = explode(' ', 'B KB MB GB TB PB');

        $mod = 1024;

        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        $endIndex = strpos($size, ".")+3;

        return substr( $size, 0, $endIndex).' '.$units[$i];
    }
}
