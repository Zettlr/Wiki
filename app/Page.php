<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function pages()
    {
        return $this->belongsToMany('App\Page', 'page_page', 'page1_id', 'page2_id')->withTimeStamps();
    }

    public function page_index()
    {
        return $this->hasOne('App\PageIndex');
    }
}
