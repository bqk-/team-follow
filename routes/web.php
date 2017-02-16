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
        "version" => "0.1"
    ]);
});

$app->get('/teams/', function () use ($app) {
    $teams = App\database\Team::all();
    return json_encode($teams);
});
