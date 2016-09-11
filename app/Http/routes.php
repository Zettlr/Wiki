<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

/******************************************************************************
 * AJAX handlers for functionality not provided by Page and Search controller *
 ******************************************************************************/
// MediaLibrary functionality
Route::get ('/ajax/getMedia',                   'AjaxController@getMedia');
Route::post('/ajax/uploadMedia',                'AjaxController@uploadMedia');
// TODO: Move to page controller
Route::get ('/ajax/getPageContent/{id}/{raw?}', 'AjaxController@getPageContent');
// Route::get('/search/{term}',                 'PageController@search');

/******************************************************************************
 * Search Engine controller functionality                                     *
 ******************************************************************************/
Route::get ('/searchEngine/rebuild/full',       'Search@rebuildIndex');
// This route will get called only from within PageController-functions via dispatcher
Route::get ('/searchEngine/rebuild/{id}',       'Search@indexPage');
// Do a search
Route::get ('/search/{term}',                   'Search@search');

/******************************************************************************
 * Administration functionality                                               *
 ******************************************************************************/

Route::group(['prefix' => 'admin'], function() {
    Route::get ('/', 'Backend@index');
    Route::get ('/settings', 'Backend@settings');
    Route::post('/settings', 'Backend@saveSettings');
    Route::get ('/advancedSettings', 'Backend@advancedSettings');
    Route::post('/advancedSettings', 'Backend@saveSettings'); // As only changed settings are saved we can reuse the function
    Route::get ('/flushViews', 'Backend@flushViews');
    Route::get ('/backupDatabase', 'Backend@backupDatabase');
    Route::get ('/logs', 'Backend@logs');
});

/******************************************************************************
 * Exports                                                                    *
 ******************************************************************************/

Route::get('/export/{format?}/{pages?}', 'IO@export');

/******************************************************************************
 * Page functions (Creating, editing, deleting)                               *
 ******************************************************************************/
// Creation
Route::post('/create',                          'PageController@postCreate');
Route::get ('/create/{slug?}',                  'PageController@getCreate');
// Editing
Route::post('/edit/{flags?}',                   'PageController@postEdit');
Route::get ('/edit/{slug}',                     'PageController@getEdit');
// Display a list of all pages
Route::get ('/index',                           'PageController@listPages');
// Trash functions
Route::get ('/remove/{id}',                     'PageController@trash');
Route::get ('/trash/empty',                     'PageController@emptyTrash');
Route::get ('/trash',                           'PageController@showTrash');
Route::get ('/restore/{id}',                    'PageController@restoreFromTrash');
// Simple slug-proposing function
Route::get ('/sluggify/{title}', function($title) {
    return response()->json(['slug' => str_slug($title, '_')], 200);
});
// Now no reserved keywords left: Load a page
Route::get ('/{slug}/{term?}',                  'PageController@show');

// No page selected? Let's redirect to the main page
Route::get ('/', function() {
    return redirect('/Main_Page');
});
