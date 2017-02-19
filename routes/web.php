<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return json_encode([
        "name" => "Team API",
        "version" => "0.1",
        "teams" => URL::to('/') + "teams"
    ]);
});

$app->get('/teams/{page?}', function ($page = 0) use ($app) {
    $teams = App\database\Team::skip(0 * $page)->take(20);
    $ret = array();
    foreach ($teams as $t)
    {
        $ret[] = new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo);
    }
  
    return json_encode($ret);
})
->where('page', '[0-9]+');
