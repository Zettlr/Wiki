<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Media;
use App\Page;

use GrahamCampbell\Markdown\Facades\Markdown;

use Storage;

class AjaxController extends Controller
{
    public function getMedia()
    {
        // This function just returns all media stored in the database
        $media = Media::all();

        if(count($media) <= 0) {
            return response()->json(['message' => 'There are no media files stored in the database.'], 404);
        }
        else {
            // JSON is so damned easy <3
            return $media;
        }
    }

    // TODO
    public function uploadMedia()
    {
        // This function lets us upload images
        if(!$request->hasFile('import_tmp'))
        return response()->json(['The file hasn\'t been uploaded!'], 400);

        if(!$request->file('import_tmp')->isValid())
        return response()->json(['The file has been corrupted on upload.'], 500);

        // First initialize our local storage
        $store = Storage::disk('local');

        $fcontents = File::get($request->file('import_tmp')->getRealPath());

        // We're using time() to have a unique identifier for retaining the notes' upload order
        $success = $store->put(
        $dirname . '/tmp_'.time().'.'.$request->file('import_tmp')->getClientOriginalExtension(),
        $fcontents);

        if($success)
        return response()->json(['Upload successful'], 200);
        else {
            return response()->json(['Failed to move file to directory'], 500);
        }
    }

    public function getPageContent($id, $raw = "no")
    {
        if($raw !== "no") { // Raw HTML without variable parsing
            // ATTENTION! Until ContentTools works, it will try to convert from MD
            // to HTML!
            try {
                $page = Page::findOrFail($id);

                $page->content = Markdown::convertToHtml($page->content);

                return response()->json([$page->content, 200]);
            } catch (ModelNotFoundException $e) {
                return response()->json(["Couldn't find page!", 404]);
            }
        }
        else { // "Nice" HTML with variables parsed
            try {
                $page = Page::findOrFail($id);

                $page->content = Markdown::convertToHtml($page->content);

            } catch (ModelNotFoundException $e) {
                return response()->json(["Couldn't find page!", 404]);
            }

            // TODO: Export this whole bunch of code

            // BEGIN DISPLAY WIKILINKS: Preg-replace the Wikilinks
            $pattern = "/\[\[(.*?)\]\]/i";
            $page->content = preg_replace_callback($pattern, function($matches) {
                // Replace the Link text with the title of each found wikilink

                if(strpos($matches[1], '|') > 0)
                {
                    // The second part is what we need
                    $text = explode('|', $matches[1]);
                    $matches[1] = $text[0];
                    if(strlen($text[1]) == 0) {
                        $text = $matches[1];
                    }
                    else {
                        $text = $text[1];
                    }
                }
                else {
                    // If no linktext was given, just display the match.
                    $text = $matches[1];
                }

                // Now that we for sure have only the slug in $matches[1], get the page
                $page = Page::where('slug', $matches[1])->get()->first();

                if($page !== null) {
                    // We got a match -> replace the link

                    if($text === $matches[1]) {
                        $text = $page->title;
                    }

                    return "<a href=\"" . url('/') . "/$matches[1]\">" . $text . "</a>";
                }
                else {
                    // No page with this name exists -> link to create page
                    return "<a class=\"broken\" href=\"" . url('/') . "/create/$matches[1]\" title=\"Create this page\">$text</a>";
                }
            }, $page->content);
            // END DISPLAY WIKILINKS

            // Prepare possible variables and replace them
            // Available variables: %wordcount%, %pagecount%
            $page->content = preg_replace_callback("(%wordcount%)", function($matches) {
                // Compute the wordcount and replace
                return number_format($this->wordcount());
            }, $page->content);

            $page->content = preg_replace_callback("(%pagecount%)", function($matches) {
                // Compute the pagecount and replace
                return number_format($this->pagecount());
            }, $page->content);

            // Labels
            $page->content = preg_replace_callback("(%label\|(.+?)%)", function($matches) {
                return '<span class="label-primary">' . $matches[1] . '</span>';
            }, $page->content);
            $page->content = preg_replace_callback("(%success\|(.+?)%)", function($matches) {
                return '<span class="label-success">' . $matches[1] . '</span>';
            }, $page->content);
            $page->content = preg_replace_callback("(%warning\|(.+?)%)", function($matches) {
                return '<span class="label-warning">' . $matches[1] . '</span>';
            }, $page->content);
            $page->content = preg_replace_callback("(%error\|(.+?)%)", function($matches) {
                return '<span class="label-error">' . $matches[1] . '</span>';
            }, $page->content);
            $page->content = preg_replace_callback("(%muted\|(.+?)%)", function($matches) {
                return '<span class="label-muted">' . $matches[1] . '</span>';
            }, $page->content);

            return response()->json([$page->content, 200]);
        }
    }

    // TODO: Those two functions are declared two times, which should not be

    /**
     * Simple word counting function by counting an array of space-exploded contents
     * @return Int The calculated word count
     */
    public function wordcount()
    {
        // Calculates a wordcount for the whole wiki
        // Could do it nicer, but seriously, nobody
        // needs such exact amounts
        $count = 0;

        $pages = Page::all();

        foreach($pages as $page)
        {
            $cntwords = explode(' ', $page->content);
            $count += count($cntwords);
        }

        return $count;
    }

    /**
     * Simply returns an aggregate count of pages currently in database
     * @return Int The number of pages
     */
    public function pagecount()
    {
        // Pretty easy: Return number of pages in this wiki
        return Page::all()->count();
    }
}
