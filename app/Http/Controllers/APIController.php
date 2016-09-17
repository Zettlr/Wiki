<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

// Composer dependencies to run from PHP
use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

use ZipArchive;

use NathanLeSage\EnvEdit;

class APIController extends Controller
{
    public function __construct()
    {
        if(!env('AUTH_GUEST_EDIT')) {
            $this->middleware('auth:api');
        }
    }

    public function downloadUpdate($version)
    {
        // This function gets the ZIP and extracts its contents to the correct dirs
        $archive = 'https://github.com/Zettlr/wiki/archive/' . $version . '.zip';
        $dest = base_path() . '/storage/app/tmp.zip';
        $foldername = false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $archive);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $outfile = fopen($dest, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $outfile);
        $ret = curl_exec($ch);

        // Abort on error
        if(!$ret) {
            curl_close($ch);
            return response()->json(['Could not download ZIP file!', 500]);
        }

        curl_close($ch);
        fclose($outfile);

        // Now we have to extract that thing
        $zip = new ZipArchive();
        $foldername = '';
        if ($zip->open($dest) === true) {
            $zip->extractTo(base_path() . '/storage/app/');
            // Now save the root folder name for copying
            $foldername = $zip->statIndex(0)['name'];
            $zip->close();
        } else {
            return response()->json(['Could not extract ZIP file!', 500]);
        }

        // Now delete the zip
        unlink($dest);

        // Remove trailing slash from foldername
        $foldername = substr($foldername, 0, strlen($foldername) - 1);

        // In case we already had a failed update without cleanup, we need to
        // remove a potential other directory first
        if(file_exists(base_path() . '/storage/app/update_files')) {
            File::deleteDirectory(base_path() . '/storage/app/update_files');
        }

        // Rename the directory to a unified name
        rename(base_path() . '/storage/app/' . $foldername, base_path() . '/storage/app/update_files');

        return response()->json(['Download successful!', 200]);
    }

    public function moveUpdate()
    {
        // Ignore the storage directory, node_modules and vendor, as these
        // will be updated separately (or not at all)
        $ignoreDirectories = array('storage', '.git', 'node_modules', 'vendor');

        // In case we are using an SQLite-Database file, we would want to ignore
        // that as well to prevent it being deleted. The same goes for .env and
        // .htaccess, as these are files that generally can be altered to the needs
        // of the user.
        $ignoreFiles = array(env('DB_DATABASE', 'database.sqlite'), '.env', '.htaccess');

        // Map old directory
        $oldDir = $this->flatten($this->dirToArray(base_path(),
        $ignoreDirectories,
        $ignoreFiles, base_path()));

        // Map new directory
        $newDir = $this->flatten($this->dirToArray(base_path() . '/storage/app/update_files',
        [],
        $ignoreFiles, base_path() . '/storage/app/update_files'));

        // Create a diff for both directories.
        $diff = $this->diff($oldDir, $newDir);

        // The diff-variable is an associative array containing three sub-trees
        // called add (for files that are new in this update), change (most
        // files, as we don't care whether or not they have changed. Has some
        // performance issues, but for now it's satisfying) and remove (delete
        // files that are not present anymore in our installation).

        // Add new files
        foreach($diff['add'] as $file) {
            File::put(base_path() . $file, File::get(base_path() . '/storage/app/update_files/' . $file));
        }

        // Change files
        foreach($diff['change'] as $file) {
            File::put(base_path() . $file, File::get(base_path() . '/storage/app/update_files/' . $file));
        }

        // Remove old files
        foreach($diff['remove'] as $file) {
            File::delete(base_path() . $file);
        }

        // Now the pure folder structure of this installation resembles the
        // update -> We are good to go!

        return response()->json(['File operations were successful', 200]);
    }

    public function migrateDatabase()
    {
        // With every update, there is a possibility for changed database structures.
        // The migration-files are extremely useful for this, as artisan will check
        // which ones we already migrated and only migrate the newest ones, containing
        // new tables, alterations and the like.

        // We need to --force artisan to prevent security questions.
        Artisan::call('migrate', ['--force' => true]);

        return response()->json(['Database migration successful!', 200]);
    }

    public function runComposer()
    {
        // Finally, do a composer run
        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . base_path() . '/vendor/bin/composer');

        chdir(base_path());
        // call `composer install` command programmatically
        $input = new ArrayInput(array('command' => 'install', '--no-dev' => true));
        $application = new Application();
        $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
        $application->run($input);

        return response()->json(['Composer run successful!', 200]);
    }

    public function finalize($version)
    {
        // Now we should be ready to rumble again -> update the env file
        $editor = new EnvEdit();
        $editor->read();
        $editor->setVars(['APP_VERSION' => $version]);
        $editor->write();

        // Cleanup
        File::deleteDirectory(base_path() . '/storage/app/update_files');

        // If everything went smoothely, redirect to the updates page
        return response()->json(['Cleanup successful!', 200]);
    }

    /**************************************************************************
    * HELPER FUNCTIONS
    **************************************************************************/

    /**
    * Maps dir recursively. Credits go to https://php.net/manual/en/function.scandir.php#110570
    * @param  string $dir The directory to be scanned
    * @param array       Directories to be ignored
    * @return array      The recursive array
    */
    public function dirToArray($dir, array $ignoreDirs = [], array $ignoreFiles = [], $root = '')
    {
        $result = array();

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value)
        {
            if (!in_array($value,array(".","..")) && !in_array($value, $ignoreDirs))
            {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                {
                    $result[$value] = $this->dirToArray(str_replace('//', '/', $dir . DIRECTORY_SEPARATOR . $value), $ignoreDirs, $ignoreFiles, $root);
                }
                else
                {
                    if(!in_array($value, $ignoreFiles)) {
                        $result[] = str_replace($root, '', $dir) . DIRECTORY_SEPARATOR . $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
    * Flattens a multidimensional array to a one-dimensional array, Credits
    * go to https://stackoverflow.com/a/1320156
    * @param  array  $array The array to be flattened
    * @return array        The flattened array
    */
    public function flatten(array $array)
    {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }

    public function diff(array $old, array $new)
    {
        $return = ['add' => [], 'change' => [], 'remove' => []];

        foreach($new as $value)
        {
            if(!in_array($value, $old)) {
                $return['add'][] = $value;
            }
            else {
                $return['change'][] = $value;
            }
        }

        foreach($old as $value)
        {
            if(!in_array($value, $new)) {
                $return['remove'][] = $value;
            }
        }

        return $return;
    }
}
