<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

use App\Http\Requests;

use App\PageIndex;
use App\User;
use NathanLeSage\EnvEdit;

use Auth;

use Validator;

use Illuminate\Support\Facades\Hash;

class Backend extends Controller
{
    public function __construct()
    {
        if(env('AUTH_ACTIVE', true)) {
            $this->middleware('auth');
        }
    }

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
        //
        $env = new EnvEdit();
        $env->read();

        // Update the variables
        $env->setVars($request->all());

        if($env->write()) {
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

    public function flushCache()
    {
        // Run the artisan commands
        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        // And return
        return redirect('/admin');
    }

    public function logs($file = "current", $flags = "tail")
    {
        $page = new \stdClass();
        $page->title = 'Logs';

        // This function displays all logs.
        $logfiles = File::allFiles(storage_path() . '/logs');

        $lastmod = 0;
        $latest = "";

        // Get the logfile with the newest information
        foreach($logfiles as &$f) {
            // Exclude any non-log files
            if(substr($f, -3) !== "log") {
                continue;
            }

            if(File::lastModified($f) > $lastmod) {
                $lastmod = File::lastModified($f);
                // Replace the name for better readability in the view
                $f = File::name($f) . '.' . File::extension($f);
                $latest = $f;
            }
        }

        $theFile = storage_path() . '/logs/' . (($file == 'current') ? $latest : $file);

        $logcontent = File::get($theFile);
        $logcontent = preg_split("/((\r?\n)|(\r\n?))/", $logcontent);

        $logBegin = ($flags == 'tail') ? sizeof($logcontent) - 40 : 0;

        $firstline = $logBegin;
        $logcontent = array_slice($logcontent, $logBegin);
        $lines = [];

        for($i = 0; $i < sizeof($logcontent); $i++) {
            $lines[$i] = new \stdClass();
            $lines[$i]->number = $logBegin + $i;
            $lines[$i]->contents = $logcontent[$i];
        }

        $theFile = File::name($theFile) . '.' . File::extension($theFile);

        return view('admin.logs', compact('logfiles', 'lines', 'page', 'theFile'));
    }

    public function getToken()
    {
        $page = new \stdClass();
        $page->title = trans('ui.backend.settings.token_title');

        $tokenfile = storage_path() . '/app/token.txt';

        if(!File::exists($tokenfile)) {
            File::put($tokenfile, '');
        }

        $token = [];
        foreach(preg_split("/((\r?\n)|(\r\n?))/", File::get($tokenfile)) as $line) {
            if(strpos($line, '=') <= 0) {
                continue;
            }

            $line = explode("=", $line);

            $token[] = ['token' => $line[0], 'uses' => $line[1]];
        }

        return view('admin.token', compact('page', 'token'));
    }

    public function postToken(Request $request)
    {
        $tokenfile = storage_path() . '/app/token.txt';
        $token = [];

        if(!File::exists($tokenfile)) {
            File::put($tokenfile, '');
        }

        // First lets check whether we should create new token
        if($request->has('reg_token') && intval($request->reg_token) > 0) {
            $uses = ($request->reg_token_uses > 0) ? $request->reg_token_uses : 1;

            for($i = 0; $i < $request->reg_token; $i++) {
                $token[] = ['token' => md5(random_bytes(32)), 'uses' => $uses];
            }
        }

        // Now our other token with updated uses
        if($request->has('token')) {
            for($i = 0; $i < sizeof($request->token); $i++) { //foreach($request->token as $key => $t) {
                $token[] = ['token' => $request->token[$i], 'uses' => $request->uses[$i]];
            }
        }

        // Unset all token without uses
        foreach($token as $key => $t) {
            if($t['uses'] <= 0) {
                unset($token[$key]);
            }
        }

        // Now just save the token.
        foreach($token as $key => $t) {
            $token[$key] = $t['token'] . '=' . $t['uses'];
        }

        File::put($tokenfile, implode("\n", $token));

        return redirect('/admin/token');
    }

    public function getAccount()
    {
        $page = new \stdClass();
        $page->title = 'Account';

        if(!Auth::check()) {
            return redirect('/admin'); // Return non-logged-in users as they won't see anything here.
        }

        return view ('admin.account', compact('page'));
    }

    public function postAccount(Request $request)
    {
        if(!Auth::check()) {
            return redirect('/admin'); // Cannot update info for no user
        }

        // Check password
        if(!Auth::attempt(['email' => Auth::user()->email, 'password' => $request->old_password])) {
            return redirect('/admin/account')->withInput()->withErrors(['old_password' => 'Your old password was wrong']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:users,name,'. Auth::user()->id,
            'email' => 'required|email|max:255|unique:users,email,' . Auth::user()->id,
            'old_password' => 'required|min:6',
            'password' => 'min:6|confirmed',
        ]);

        if($validator->fails()) {
            return redirect('/admin/account')->withInput()->withErrors($validator);
        }

        $user = User::find(Auth::user()->id);

        $user->name = $request->name;
        $user->email = $request->email;

        $user->save();

        return redirect('/admin/account');
    }

    public function regenerateToken()
    {
        if(!Auth::check()) {
            return redirect('/admin'); // Cannot regenerate a token for no user
        }

        $user = User::find(Auth::user()->id);

        $user->api_token = Hash::make(random_bytes(32)); // For now only a simple token
        $user->save();

        return redirect('/admin/account');
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
        $size = 0;
        foreach(File::allFiles(storage_path() . '/framework/views') as $file) {
            $size += File::size($file);
        }

        foreach(File::allFiles(storage_path() . '/framework/cache') as $file) {
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
