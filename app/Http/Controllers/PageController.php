<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Requests;

use Illuminate\Support\Collection;

use App\Page;

use Validator;
use DB;

use GrahamCampbell\Markdown\Facades\Markdown;
use League\HTMLToMarkdown\HtmlConverter;

class PageController extends Controller
{
    // This handles all requests to specific pages

    public function listPages()
    {
        // This function returns a view displaying the following three
        // sections:
        // 1. All pages as a list
        // 2. All unreferenced pages (i.e. pages that can't be accessed through
        //    other pages)
        // 3. All missing links (i.e. pages that are referenced from in pages
        //    but are not yet represented in the database)

        // 1. For the page list get all pages with all contents.
        $pages = Page::all();

        // 2. Unreferenced pages
        $unreferencedPages = new Collection();
        $missingPages = [];
        $pattern = "/\[\[(.*?)\]\]/i";
        foreach($pages as $page)
        {
            $referencingPages = DB::select('SELECT page1_id FROM page_page WHERE page2_id = ' . $page->id . ';');
            if(count($referencingPages) == 0) {
                $unreferencedPages->push($page);
            }

            // 3. Missing links
            $text = $page->content;
            while(preg_match($pattern, $text, $matches))
            {
                if(strpos($matches[1], '|') > 0)
                {
                    // The first part is what we need
                    $tmp = explode('|', $matches[1]);
                    $matches[1] = $tmp[0];
                }

                // Now that we for sure have only the slug in $matches[1], get the page
                $page = Page::where('slug', $matches[1])->get()->first();

                if($page == null) {
                    // We found a missing link -> push it into the array
                    $missingPages[] = $matches[1];
                }

                // Now cut the string to search
                $text = substr($text, strpos($text, $matches[0]) + strlen($matches[0]));
            }
        }

        // The app-view always requires $page->title variable to be set
        $page = new Page();
        $page->title = "Page index";

        return view('page.main', compact('pages', 'unreferencedPages', 'missingPages', 'page'));
    }

    public function show($slug, $term = "")
    {
        // The page name is a unique slug that is stored in the database
        // We can have two scenarios.
        // First: The page exists -> show it
        // Second: The page does not exist -> show form to enter it

        $page = Page::where('slug', $slug)->get()->first();
        // Count the results
        if(count($page) != 1) {
            // Redirect to creation page
            return redirect('/create/'.$slug);
        }

        // Before converting to html we need to assign a little bit nicer quotes to the content
        // Currently, only german quotes will be set, but in the future, depending on the language
        // we could employ all quotes necessary
        // Deactivated (Reason: Some nasty errors in display)
        // $page->content = preg_replace("/[\W+][\"](.+?)[\"][\W+]/is", " &bdquo;$1&ldquo; ", $page->content);

        $page->content = Markdown::convertToHtml($page->content);

        if($term !== "") {
            // We have a term to highlight, so do it!
            // We have to do this BEFORE we replace such stuff like wiki links
            // as otherwise we can corrupt our links

            $term = explode(" ", $term);

            for($i = 0; $i < sizeof($term); $i++) {
                //$page->content = preg_replace("/[^\[\>](" . $term[$i] . ")/i", ' <span class="highlight">$1</span>', $page->content);
                $page->content = preg_replace("/(" . $term[$i] . ")/i", ' <span class="highlight">$1</span>', $page->content);
            }
        }

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

        // Now get the referencing pages for this
        // In our database, always page2_id is the page
        // that is being referenced by page1_id, so we
        // need to get page1_id where page2_id is this id
        $referencingPages = DB::select('SELECT page1_id FROM page_page WHERE page2_id = ' . $page->id . ';');

        // Now we have an array with all IDs from the referencing pages, but we
        // need title and slug from these pages
        $tmp = [];
        for($i = 0; $i < count($referencingPages); $i++)
        {
            $tmp[] = $referencingPages[$i]->page1_id;
        }
        // whereIn takes an array and selects all (in this case) IDs from the array
        $referencingPages = Page::whereIn('id', $tmp)->get();

        return view('page.show', compact('page', 'referencingPages'));
    }

    /**
    * Displays a form for inserting a new page
    * @param  string $slug Either a pre-set slug (when clicking on a missing link) or NULL
    * @return View       Pass on the view function to display the form
    */
    public function getCreate($slug = NULL)
    {
        if(isset($slug)) {
            return view('page.create', compact('slug'));
        }

        return view('page.create');
    }

    /**
    * Inserts a post into the database
    * @param  Request $request The request object
    * @return Redirect           Returns a redirect either to the created page or, on errors, to the create page
    */
    public function postCreate(Request $request)
    {
        // Create a validator
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required|min:10',
            'slug' => 'unique:pages|max:255'
        ]);

        if ($validator->fails()) {
            return redirect('/create')
            ->withErrors($validator)
            ->withInput();
        }

        // Everything good? Create new record!
        $page = new Page();
        $page->slug = $request->slug;
        $page->title = $request->title;
        $page->content = $request->content;
        $page->save();

        // Now we have to update the page links. But we have the problem,
        // that we don't know if there was any other page, linking to this
        // page, i.e. distorting our "Referenced by" field on top of each page.
        // Therefore we will need to update ALL links to be sure.
        $this->updateLinks();

        // One last thing to do: Create the search index. Otherwise the page will
        // be invisible to our search. To trigger creation, we will manually
        // "visit" the rebuilder to trigger the creation of an index.
        $indexingRequest = Request::create('/searchEngine/rebuild/' . $page->id, 'GET');
        $response = \Route::dispatch($indexingRequest);

        return redirect('/'.$page->slug);
    }

    /**
    * Displays a page for editing purposes
    * @param  string $slug The slug where the page is to be found under
    * @return view       Just the view.
    */
    public function getEdit($slug)
    {
        $page = Page::where('slug', $slug)->get()->first();

        return view('page.edit', compact('page'));
    }

    /**
    * Inserts edited contents of pages into the database
    * @param  Request $request The request object
    * @param  Mixed   $flags    Potential flags (like "json") or null
    * @return Redirect           Either to the page or on errors to edit page
    */
    public function postEdit(Request $request, $flags = null)
    {
        // Create a validator
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required|min:10'
        ]);

        if ($validator->fails()) {
            if($flags === "json") {
                return response()->json([$validator, 400]); // Bad request
            }
            else {
                return redirect('/edit/'.$request->slug)
                ->withErrors($validator)
                ->withInput();
            }
        }

        $page = Page::where('slug', $request->slug)->get()->first();

        if($page === null) {
            if($flags === "json") {
                return response()->json(['Page was not found', 404]);
            }
            else {
                return redirect('/');
            }
        }

        // Everything good? Edit!
        $page->title = $request->title;
        $page->content = $request->content;
        // If the page has been updated via ContentTools we have to convert to
        // markdown (first to remove the editing classes and second to Simply
        // save space on the server).
        if($flags === "json") {
            $converter = new HtmlConverter();
            $page->content = $converter->convert($page->content);
        }
        $page->save();

        // Now update all links this page may link to
        $pageCollection = new Collection();
        $pageCollection->push($page);
        $this->updateLinks($pageCollection);

        // One last thing to do: Update the search index. Therefore we will "call"
        // the rebuilder from within this function via a route we will process
        // internally (i.e. as if we would've visit this route)
        $indexingRequest = Request::create('/searchEngine/rebuild/' . $page->id, 'GET');
        $response = \Route::dispatch($indexingRequest);

        if($flags === "json") {
            return response()->json([$page->content, 200]);
        }
        else {
            return redirect('/'.$request->slug);
        }
    }

    /**
    * Displays all pages, that have been soft-deleted
    * @return View Displays the Trash
    */
    public function showTrash()
    {
        $pages = Page::onlyTrashed()->get();

        // The app-view always requires $page->title variable to be set
        $page = new Page();
        $page->title = "Trash";


        return view('page.trash', compact('pages', 'page'));
    }

    /**
    * Empties the trash by hard-deleting all soft-deleted pages
    * @return redirect Back to the (then empty) trash
    */
    public function emptyTrash()
    {
        $pages = Page::onlyTrashed()->get();

        foreach($pages as $page) {
            $page->forceDelete();
        }

        return redirect('/trash');
    }

    /**
    * Soft-Deletes a page (moves them to trash)
    * @param  Int $id The page ID
    * @return redirect     Either to the trash (if someone wanted to delete the Main_Page) or back to home
    */
    public function trash($id)
    {
        try {
            $page = Page::findOrFail($id);
        }
        catch(ModelNotFoundException $e) {
            return redirect('/trash')->withErrors(['Page not found']);
        }

        // Check if it's the main page. If yes, do not delete it!
        if($page->slug === 'Main_Page') {
            return redirect('/trash')->withErrors(['You may not delete the main page.']);
        }

        $page->delete();

        return redirect('/');
    }

    /**
    * This function removes the "has been deleted" flag on the page
    * @param  Int $id The page ID
    * @return redirect     To trash (either with or without errors)
    */
    public function restoreFromTrash($id)
    {
        $page = Page::withTrashed()->where('id', $id)->get()->first();

        if($page === null) {
            return redirect('/trash')->withErrors(['Page not found']);
        }

        $page->restore();

        return redirect('/trash');
    }

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

    /**
    * Updates either all pages or only a selection of pages depending on param
    * @param  Illuminate\Support\Collection $pages An array holding one or more pages
    * @return void
    */
    public function updateLinks($pages = null)
    {
        // In $pages only should be one page (when called from within postEdit,
        // which happens on every edit)
        // But basically it can also hold a distinctive array of pages (to not
        // update EVERY SINGLE page)
        if($pages === null) {
            $allPages = Page::all();
        }
        else {
            $allPages = $pages;
        }

        foreach($allPages as $page) {
            // Now retrieve all linked pages from within the page
            $pattern = "/\[\[(.*?)\]\]/i";
            $text = $page->content;
            $matches = '';

            // First sync with an empty array to exclude possible duplicates
            $page->pages()->sync([]);

            while(preg_match($pattern, $text, $matches))
            {
                // We got a match
                $slug = $matches[1];
                $linkedPage = Page::where('slug', $slug)->get()->first();
                if($linkedPage !== null) {
                    $page->pages()->attach($linkedPage->id);
                }

                // Cut the text up to this match.
                $text = substr($text, strpos($text, $matches[0]) + strlen($matches[0]));
            }
        }
    }
}
