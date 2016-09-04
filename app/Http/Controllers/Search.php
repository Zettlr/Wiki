<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Requests;

use App\Page;
use App\PageIndex;

use GrahamCampbell\Markdown\Facades\Markdown;

class Search extends Controller
{
    // This controller's sole purpose is to index and search those indices
    //
    // It is associated with PageIndex, page_indices (table) and therefore Page


    public function rebuildIndex()
    {
        // Get all page IDs and rebuild their index
        $pages = Page::get(['id']);

        $errors = 0;
        foreach($pages as $page) {
            if(!$this->indexPage($page->id)) {
                $errors++;
            }
        }

        if($errors > 0) {
            dd("There were some errors while rebuilding the index");
        }

        dd("Rebuilding successful!");
    }

    /**
    *
    * This function indexes a page_id
    * @param  int $page_id The page to be (re)indexed
    * @return bool          Whether the indexing procedure was successful
    */
    public function indexPage($page_id)
    {
        // First get the page content
        try {
            $page = Page::findOrFail($page_id);
        }
        catch(ModelNotFoundException $e) {
            // Don't try to even index a nonexisting page
            return false;
        }

        // Convert Markdown -> HTML
        $page->content = Markdown::convertToHtml($page->content);

        // As we will definitely want the titles searchable, just patch it to the
        // content
        $page->content .= " " . $page->title;

        // Strip HTML tags
        $page->content = strip_tags($page->content);

        // Remove control chars (\n, \r, etc - also / as it is used as separator)
        $page->content = preg_replace('/[\x00-\x1F\x7F\/]/', ' ', $page->content);

        // Decode HTML entities (to have "natural" words)
        $page->content = html_entity_decode($page->content);

        // Remove any non-word-characters ( ) . , [ ] { } : * ? ! as well as " and ' and … and « » ;
        $page->content = preg_replace('/[\(\)\.,;\{\}\[\]\:\*\?\!\"\'…«»]/', '', $page->content);

        $wordsToIndex = explode(' ', $page->content);
        $indexed = [];

        foreach($wordsToIndex as $word) {
            // Let's first try by indexing ALL words.

            // Except < 2 chars.
            if(strlen($word) < 3) {
                continue;
            }

            if(array_key_exists($word, $indexed)) {
                // We already have it so just pump up the array
                $indexed[$word] += 1;
            } else {
                // We don't have the index yet, so initialize ...
                $indexed[$word] = 1;
            }
        }

        // Now we have a nice associative array containing all unique words and
        // their appearance. We assume all words to be more relevant, the more
        // often they appear in a page. Of course this doesn't hold true for non
        // nouns like "the", "a", "one" etc., but normally one wouldn't search
        // for those.

        // Sort by relevancy (descending)
        arsort($indexed);

        // Now run a UTF8-encoding over the keys, as there always can be some
        // UTF 8 characters in them.
        $indexed_utf = [];
        foreach($indexed as $key => $value) {
            $indexed_utf[utf8_encode($key)] = $value;
        }

        // Now encode to json and then pack into an existing (or a new) index
        $indexed = json_encode($indexed_utf);

        try {
            $page_index = PageIndex::where('page_id', '=', $page_id)->firstOrFail();

            $page_index->wordlist = $indexed;
            $page_index->save();
        }
        catch(ModelNotFoundException $e) {
            // There is no model, so create a new one
            $page_index = new PageIndex([ "wordlist" => $indexed ]);

            // Associate with respective page
            $page->page_index()->save($page_index);
        }

        // At this point we have a correct page index to be used! :)
        return true;
    }

    public function search($term)
    {
        // Let's begin by loading all page indices
        $indices = PageIndex::all();

        // Prepare the search term
        $terms = explode(" ", $term);
        for($i = 0; $i < sizeof($terms); $i++) {
            $terms[$i] = strtolower($terms[$i]);
        }

        $matchedPages = [];
        $maxRelevancy = 0;

        // For later we need to save all indexed words into an array.
        $levenshtein_input = [];

        // Begin the loop
        foreach($indices as $index) {
            // We need to recap the JSON to an associative array (second parameter)
            // Without the second parameter it will only give back an object
            $wordlist = json_decode($index->wordlist, true);

            // Re-decode the keys (we need to search for exact matches)
            //
            // Also strtolower the keys. Makes it easier to match the terms.
            $indexed_utf = [];
            foreach($wordlist as $key => $value) {
                $indexed_utf[strtolower(utf8_decode($key))] = $value;
                // Put the key into our input array
                $levenshtein_input[] = utf8_decode($key);
            }
            $wordlist = $indexed_utf;

            // Now loop through all search terms and count their existance
            // (but with cumulative relevancy)
            $relevancy = 0;
            for($i = 0; $i < sizeof($terms); $i++) {
                if(array_key_exists($terms[$i], $wordlist)) {
                    $relevancy += $wordlist[$terms[$i]];
                }
            }

            // Now, if we have a relevancy > 0, add it to the matched-array
            if($relevancy > 0) {
                $matchedPages[$index->page_id] = $relevancy;

                if($relevancy > $maxRelevancy) {
                    $maxRelevancy = $relevancy;
                }
            }
        }

        // After the loop the $matchedPages-array should contain page IDs and
        // according relevancy counts for all matched pages. Sort!
        arsort($matchedPages);

        // Now get all pages that we have matched
        $pages = Page::find(array_keys($matchedPages));

        foreach($pages as $page) {
            $page->relevancy = round($matchedPages[$page->id] / $maxRelevancy * 100);
        }

        $pages = $pages->sortByDesc('relevancy');

        // Now it can be that a user misspelled a word (either while searching
        // or while he was writing the articles). So we need to account for that
        // by checking the levenshtein-distance to see if a word has at least
        // one close (but not exact, of course) match.
        $levenshtein_distance = $this->similarWords($terms, array_unique($levenshtein_input));

        $searchLink = "";
        $searchSuggestion = "";

        for($i = 0; $i < sizeof($levenshtein_distance); $i++) {
            if($i > 0) {
                $searchLink .= "%20";
            }
            $searchLink .= $levenshtein_distance[$i]['word'];
            $searchSuggestion .= $levenshtein_distance[$i]['word'] . ' ';
        }
        $searchSuggestion = trim($searchSuggestion);

        return view('page.search', compact("pages", "term", "searchSuggestion", "searchLink"));
    }

    private function similarWords($needles, $haystack)
    {
        // This function iterates over the needles and haystack array and tries
        // to identify words that have no exact match (closest > 0) but a very
        // close call (closest < length of the word) using levenshtein distance

        $lev_dist = [];
        $i = 0;

        foreach($needles as $needle)
        {
            $closest = 1000;
            $closestWord = "";

            foreach($haystack as $word)
            {
                if(strtolower($word) === strtolower($needle)) {
                    continue;
                }

                $lev = levenshtein($needle, $word);
                if($lev > 0 && $lev < $closest) {
                    // We got a match (but not an exact one)
                    $closest = $lev;
                    $closestWord = $word;
                }
            }
            // Now our variables closest and closestWord should contain the best
            // match -> add to array (exclude 0 as this means a perfect match)
            if($closest > 0 && $closest < 1000) {
                // $lev_dist[$i]['distance'] = $closest;
                $lev_dist[$i]['word'] = $closestWord;
                $lev_dist[$i]['needle'] = $needle;
                ++$i;
            }
        }

        // At this point we have an array containing all needles that we found
        // a match for along with the closest word.
        return $lev_dist;
    }
}
