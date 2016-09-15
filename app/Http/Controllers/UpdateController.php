<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use GrahamCampbell\Markdown\Facades\Markdown;

use DateTime;

class UpdateController extends Controller
{
    public function index()
    {
        $page = new \stdClass();
        $page->title = trans('ui.backend.updates.update');

        // This function only checks for updates and displays info
        // $url = "https://api.github.com/repos/Zettlr/wiki/git/refs/tags";
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

        return view('admin.updates', compact('page', 'tags', 'newVersion', 'update', 'published', 'changelog'));
    }

    public function runUpdate()
    {
        // This function actually runs the update.
        // It consists of these steps:
        //
        // 1. Download the ZIP file
        // 2. Extract the ZIP file
        // 3. Move all files to their new location (+ overwriting)
        // 4. Run composer update to sync the installed packages
        // 5. Run artisan migrate to migrate any changes to the database
        // 6. Cleanup: Delete ZIP and extracted directory
        // 7. Add new .env-variable to APP_VERSION.

        // First try out differ
        // https://github.com/sebastianbergmann/diff/blob/master/src/Line.php
        //
        // Following should happen: You create a complete tree, ignoring
        // storage, config and .env-file. And database. You know, everything
        // that lies inside the gitignore-files should NOT be touched.
        // Important to do it RELATIVE (because the base path will be NOT the
        // same).
        // THEN you should do THE SAME with the extracted files.
        // Then get a diff.
        //
        // According to Line.php ADDED -> 1, REMOVED -> 2 and UNCHANGED -> 3
        // That will be retrievable from the created parser-object (actually, not
        // the differ itself). And then do as it commands:
        // REMOVE every file with a 2, ADD every file (from extract to base)
        // that has 1, ignore the 3.
        //
        // Then all new files
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
