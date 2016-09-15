<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;
use GrahamCampbell\Markdown\Facades\Markdown;

use Illuminate\Support\Facades\Cache;

class Page extends Model
{
    use SoftDeletes;

    /**
    * The attributes that should be mutated to dates.
    *
    * @var array
    */
    protected $dates = ['deleted_at'];

    /**
    * In which state is this Model? Can be raw|html|parsed
    * @var string
    */
    protected $state = 'raw';

    /**
    * An array with the related-pages-IDs
    *
    * @var array
    */
    protected $referencingPages = [];

    /**
    * Have the referencing pages already been retrieved?
    *
    * @var boolean
    */
    protected $referencesRetrieved = false;

    /**
     * A prefix for the cache.
     *
     * @var string
     */
    protected $cachePrefix = 'page_';

    /**
    * Define Many-to-many-relationship with other pages
    *
    * @return Page Return this instance
    */
    public function pages()
    {
        return $this->belongsToMany('App\Page', 'page_page', 'page1_id', 'page2_id')->withTimeStamps();
    }

    /**
    * Define the one-on-one-relationship with the page Indices
    *
    * @return Page Return this instance
    */
    public function page_index()
    {
        return $this->hasOne('App\PageIndex');
    }

    public function save(array $options = [])
    {
        // Flush the page's cached files so they get updated the next time
        Cache::pull($this->cachePrefix . $this->slug . '_html');
        Cache::pull($this->cachePrefix . $this->slug . '_parsed');

        return parent::save($options);
    }

    /**
    * Sets the state of this model. Can be 'raw'|'html'|'parsed'
    *
    * @param string $newState The new state
    */
    protected function setState($newState)
    {
        $this->state = $newState;

        return $this;
    }

    /**
    * Does not do anything, only for semantics
    *
    * @return Page This instance
    */
    public function raw()
    {
        return $this;
    }

    /**
    * Parses the page's content into html
    *
    * @return page The this-instance
    */
    public function html()
    {
        if(Cache::has($this->cachePrefix . $this->slug . '_html')) {
            $this->content = Cache::get($this->cachePrefix . $this->slug . '_html');
            return $this->setState('html');
        }

        $this->content = Markdown::convertToHtml($this->content);
        Cache::put($this->cachePrefix . $this->slug . '_html', $this->content, 22*60); // Store for 22 hours

        return $this->setState('html');
    }

    /**
    * Parses the page's content so that variables like pagecount etc. can be replaced
    *
    * @return Page This instance
    */
    public function parsed()
    {
        // First check if this page has been parsed before. Then we don't need to
        // bother here.
        if(Cache::has($this->cachePrefix . $this->slug . '_parsed')) {
            $this->content = Cache::get($this->cachePrefix . $this->slug . '_parsed');
            return $this->setState('parsed');
        }

        // Are we already in a parsed state? Then return.
        if($this->state == 'parsed') {
            return $this;
        }

        // We need parsed HTML to work with the content.
        if($this->state == 'raw') {
            $this->html();
        }

        // Parse WikiLinks
        $pattern = "/\[\[(.*?)\]\]/i";
        $this->content = preg_replace_callback($pattern, function($matches) {
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
                // UPDATE: Do not link to create page, as the show()-function will
                // automatically redirect there. In this case we can override a
                // problem with the cached pages not updating their links in about a day
                // meaning that newly created pages won't be updated in their links.
                return "<a class=\"broken\" href=\"" . url('/') . "/$matches[1]\" title=\"" . trans('ui.page.create') . "\">$text</a>";
            }
        }, $this->content);
        // END DISPLAY WIKILINKS

        // Labels
        $this->content = preg_replace_callback("(%label\|(.+?)%)", function($matches) {
            return '<span class="label-primary">' . $matches[1] . '</span>';
        }, $this->content);
        $this->content = preg_replace_callback("(%success\|(.+?)%)", function($matches) {
            return '<span class="label-success">' . $matches[1] . '</span>';
        }, $this->content);
        $this->content = preg_replace_callback("(%warning\|(.+?)%)", function($matches) {
            return '<span class="label-warning">' . $matches[1] . '</span>';
        }, $this->content);
        $this->content = preg_replace_callback("(%error\|(.+?)%)", function($matches) {
            return '<span class="label-error">' . $matches[1] . '</span>';
        }, $this->content);
        $this->content = preg_replace_callback("(%muted\|(.+?)%)", function($matches) {
            return '<span class="label-muted">' . $matches[1] . '</span>';
        }, $this->content);

        // Now save in cache.
        Cache::put($this->cachePrefix . $this->slug . '_parsed', $this->content, 22*60); // Store for 22 hours

        // For chainability
        return $this->setState('parsed');
    }

    /**
    * Highlights an array of terms to highlight
    *
    * @param  string|array $terms A string or an array with terms to highlight
    *
    * @return Page        Return this instance
    */
    public function highlight($terms)
    {
        if($terms == "") {
            // Nothing to highlight
            return $this;
        }

        if(!is_array($terms)) {
            $terms = explode(" ", $terms);
        }

        foreach($terms as $term) {
            $term = trim($term);
            $this->content = preg_replace("/(" . $term . ")/i", '<span class="highlight">$1</span>', $this->content);
        }

        return $this;
    }

    /**
    * Return the pages that link to $this
    *
    * @return array An array containing all IDs.
    */
    public function getReferencingPages()
    {
        if($this->referencedRetrieved) {
            return $this->referencingPages;
        }

        // Page1 is always "the" page and Page2 is always the referenced one.
        // So in order to get the pages that reference to this page, we need to
        // query the inverse of the relation.
        $ref = DB::select('SELECT page1_id FROM page_page WHERE page2_id = ' . $this->id . ';');

        // Enter into an array
        $tmp = [];
        for($i = 0; $i < count($ref); $i++)
        {
            $tmp[] = $ref[$i]->page1_id;
        }

        $this->referencesRetrieved = true;

        return $this->referencingPages = $tmp;
    }
}
