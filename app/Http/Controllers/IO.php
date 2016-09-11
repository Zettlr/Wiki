<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Page;

use Comodojo\Zip\Zip;
use stdClass;

use GrahamCampbell\Markdown\Facades\Markdown;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\View;

class IO extends Controller
{
    // This controller basically is responsible for migrating from and to
    // ZettlrWiki.
    public function export($format = "html", $pages = "all")
    {
        // Possible values for format (right now):
        // "html"

        $filesystem = new Filesystem();

        // Prepare the output folder
        $basedir = storage_path() . '/app/public/export';

        if(!$filesystem->exists($basedir)) {
            $filesystem->makeDirectory($basedir);
        }

        // Clean up previous exportings
        $filesystem->deleteDirectory($basedir, true);

        if($pages === "all") {
            $p = Page::all();
        }
        else {
            $pages = explode(",", $pages);
            $p = Page::find($pages);
        }

        // First: Assemble TOC
        $toc = [];
        $i = 0;
        foreach($p as $page) {
            $toc[$i] = new stdClass();
            $toc[$i]->slug = $page->slug;
            $toc[$i]->title = $page->title;
            $i++;
        }

        // We need all pages in HTML format. So reformat them!
        foreach($p as $page) {
            $page->content = Markdown::convertToHtml($page->content);

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

                    // When linking here, we need to only get up one level and append HTML.
                    return "<a href=\"./$matches[1].html\">" . $text . "</a>";
                }
                else {
                    // No page with this name exists -> only wrap in a red link.
                    return "<a class=\"broken\">$text</a>";
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

            // Last but not least: wrap them in the exportable HTML
            $view = View::make('app.export-html', compact('page', 'toc'));
            $filesystem->put($basedir . '/' . $page->slug . '.html', $view->render());
        } // END FOREACH

        // Now copy stuff like CSS and javascript.
        if(!$filesystem->exists($basedir . '/css')) {
            $filesystem->makeDirectory($basedir . '/css');
        }
        if(!$filesystem->exists($basedir . '/js')) {
            $filesystem->makeDirectory($basedir . '/js');
        }

        $filesystem->copy(public_path() . '/css/app.min.css', $basedir . '/app.min.css');
        $filesystem->copy(public_path() . '/js/jquery.min.js', $basedir . '/jquery.min.js');
        $filesystem->copy(public_path() . '/js/jquery-ui.min.js', $basedir . '/jquery-ui.min.js');

        // Now zip the base dir and output it.
        $zip = Zip::create(storage_path() . '/app/public/export.zip');
        foreach($filesystem->allFiles($basedir) as $path) {
            if($filesystem->isDirectory($path)) {
                continue;
            }
            $zip->add($path->getPathname(), true);
        }

        $zip->close();

        // Finally: Provide file for download:
        $database = $filesystem->get(storage_path() . '/app/public/export.zip');

        return response($database, 200)
        ->header('Content-Type', 'application/octet-stream')
        ->header('Content-Disposition', 'attachment; filename="export.zip"');
    }

    // ToDo: Remove and use in different class
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
