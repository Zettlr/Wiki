<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PageIndex extends Model
{
    // We have a different table name (not page_indexs, but I don't know if
    // laravel is smart enough to figure the index->indices out...)
    protected $table = "page_indices";

    protected $fillable = [ "wordlist", "page_id"];

    public function page()
    {
        return $this->belongsTo('App\Page');
    }
}
