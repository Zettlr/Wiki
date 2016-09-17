<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use App\Http\Requests;

use GrahamCampbell\Markdown\Facades\Markdown;

use DateTime;
use ZipArchive;

use Dotenv\Dotenv;

class UpdateController extends Controller
{
    public function index()
    {
        $page = new \stdClass();
        $page->title = trans('ui.backend.updates.update');

        // This function only checks for updates and displays info
        $url = "https://api.github.com/repos/Zettlr/wiki/releases";

        $response = $this->queryGitHub($url);
        $tags = [];

        foreach($response as $r) {
            $tags[] = ['tag_name' => $r['tag_name'], 'published_at' => $r['published_at']];
        }

        // Now we have all tags. Let's check if we can update
        $currentVersion = env('APP_VERSION', 'v0.0.0');
        $newVersion = '';
        $update = 'non'; // Values: maj, min, pat, non
        $published = ''; // When the newest update has been published

        foreach($tags as $tag) {
            $t = explode('.', substr($tag['tag_name'], 1));
            $c = explode('.', substr($currentVersion, 1));

            if(intval($t[0]) > intval($c[0])) { // We have a major new version
                $update = 'maj';
                $newVersion = $tag['tag_name'];
                $published = $tag['published_at'];
                // Break because we've found a newer version
                break;
            } elseif(intval($t[1]) > intval($c[1])) { // We have a minor new
                $update = 'min';
                $newVersion = $tag['tag_name'];
                $published = $tag['published_at'];
                break;
            } elseif(intval($t[2]) > intval($c[2])) { // We have a patch
                $update = 'pat';
                $newVersion = $tag['tag_name'];
                $published = $tag['published_at'];
                break;
            } else { // No new update
                $update = 'non';
            }
        }

        if($update != 'non') {
            // Pull the new changelog from the server
            $url = 'https://api.github.com/repos/Zettlr/wiki/contents/changelog.md';

            $response = $this->queryGitHub($url);

            // Why ever they base64-encode the contents of simple text files ...
            $changelog = base64_decode($response['content']);

            $changelog = Markdown::convertToHtml($changelog);

            $published = new DateTime($published);
            $published = $published->format('d.m.Y');
        }

        return view('admin.updates', compact('page', 'tags', 'newVersion', 'update', 'published', 'changelog', 'dirMap'));
    }

    public function runUpdate($version = null)
    {
        if(null === $version) {
            return redirect('/admin/updates');
        }

        $page = new \stdClass();
        $page->title = "Updating &hellip;";

        // 1. Download the ZIP
        $folder = $this->downloadZIP($version);
        if(!$folder) { // folder will be "false" on error
            dd("Couldn't download or extract ZIP.");
        }

        // Rename the directory to a unified name
        rename(base_path() . '/storage/app/' . $folder, base_path() . '/storage/app/update_files');

        // ZipArchive outputs the root dir with a trailing slash -> remove to unify
        $folder = substr($folder, 0, strlen($folder) - 1);

        // 3. Create Diff for patching

        // We have to ignore directories with user-generated data and files
        // the user may have edited (such as SQLite, .env or .htaccess) that
        // do not reside in any of the ignored directories.
        $ignoreDirectories = array('storage', '.git', 'node_modules', 'vendor');
        $ignoreFiles = array(env('DB_DATABASE', 'database.sqlite'), '.env', '.htaccess');

        // Map old directory
        $oldDir = $this->flatten($this->dirToArray(base_path(),
        $ignoreDirectories,
        $ignoreFiles, base_path()));

        // Map new directory
        $newDir = $this->flatten($this->dirToArray(base_path() . '/storage/app/' . $folder,
        [],
        $ignoreFiles, base_path() . '/storage/app/' . $folder));

        $diff = $this->diff($oldDir, $newDir);

        // So, we now can get the ZIP, get the diff and all we have to do is:
        // 1. Overwrite/remove/add files
        // 2. Let composer install run to update vendor packages
        // 3. Let artisan migrate run to update database
        // 4. Delete the $folder
        // 5. Write the new version to .env

        // Do something

        // Begin adding new files
        foreach($diff['add'] as $file) {
            File::put(base_path() . $file);
        }

        // Change files
        foreach($diff['change'] as $file) {
            File::put(base_path() . $file);
        }

        // Remove old files
        foreach($diff['remove'] as $file) {
            File::delete(base_path() . $file);
        }

        // Now let artisan update the database (force to overwrite any security question)
        Artisan::call('migrate --force');

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

        // Now we should be ready to rumble again -> update the env file
        $editor = new EnvEdit();
        $editor->read();
        $editor->setVars(['APP_VERSION' => $version]);
        $editor->write();

        // Cleanup
        File::deleteDirectory(base_path() . '/storage/app/' . $folder);

        // If everything went smoothely, redirect to the updates page
        return redirect('/admin/updates');

    }

    public function queryGitHub($url = "")
    {
        if($url === "") {
            return -1;
        }

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, "Updater");

        $return = curl_exec($curl);
        curl_close($curl);

        return json_decode($return, true);
    }
}
