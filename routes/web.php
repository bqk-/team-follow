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

define("PAGESIZE", 20);

$app->get('/', function () use ($app) {
    return response()->json([
        "name" => "Team API",
        "version" => "0.1",
        "teams" => env('APP_URL') . "/teams/0"
    ]);
});

$app->get('/teams/{page:[0-9]+}', function ($page = 0) use ($app) {
    $count = App\database\Team::count();
    $teams = App\database\Team::skip($page * PAGESIZE)->take(PAGESIZE)->get();
    $ret = array();
    foreach ($teams as $t)
    {
        $ret[] = new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo);
    }
  
    return response()->json(new \App\Http\Models\TeamList($ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/teams/" . $page,
                    ($page + 1 * PAGESIZE < $count ? env('APP_URL') . "/teams/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/teams/" . ($page - 1) : "null") 
                    )
            ));
});

$app->get('/teams/search/{search:[a-zA-Z0-9]+}', function ($search = "") use ($app) {
    if($search == null)
    {
        return response()->json(new \App\Http\Models\TeamList(array(),
            new \App\Http\Models\Links(
                    env('APP_URL') . "/teams/search/" . $search,
                    "null",
                    "null"
                    )
            ));
    }
    
    $teams = App\database\Team::where('name', 'LIKE', '%' . $search . '%')
            ->orderBy('name', 'desc')->get();
    $ret = array();
    foreach ($teams as $t)
    {
        $ret[] = new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo);
    }
  
    return response()->json(new \App\Http\Models\TeamList($ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/teams/search/" . $search,
                    "null",
                    "null"
                    )
            ));
});

$app->post('/manage/account/new/{id:[0-9]+}', function ($id) use ($app) {
    if($id == null)
    {
        return response()->json(false);
    }
    
    $user = App\database\User::where('fb_id',$id)->first();
    if($user != null)
    {
        return response()->json(true);
    }
    
    $user = new App\database\User;
    $user->fb_id = $id;
    $user->date = new DateTime(time());
    $user->save();
    
    return response()->json(true);
});

$app->post('/manage/monitor/new/{user:[0-9]+}/{id:[0-9]+}', function ($user, $id) use ($app) {
    if($id == null || $user == null) 
    {
        return response()->json(false);
    }
    
    $user = App\database\User::where('fb_id',$id)->first();
    if($user == null)
    {
        return response()->json(false);
    }
    
    $team = App\database\Team::find($id);
    if($team == null)
    {
        return response()->json(false);
    }
    
    $monitor = App\database\Monitor::where('teamId', '=', $id)
        ->where('userId', $user->id)
        ->get();
    if($monitor != null)
    {
        return response()->json(false);
    }
    
    $monitor = new App\database\Monitor;
    $monitor->teamId = $team->id;
    $monitor->userId = $user->id;
    $monitor->save();
    
    return response()->json(true);
});

$app->get('/team/{id:[0-9]+}', function ($id) use ($app) {
    if($id == null)
    {
        return response()->json(null);
    }
    
    $team = App\database\Team::find($id);
    if($team == null)
    {
        return response()->json(null);
    }
    
    $before = date('Y-m-d', time() - 7 * 3600);
    $after = date('Y-m-d', time() + 14 * 3600);
    
    $fixtures = App\database\Fixture::whereDate('date', '<', $after)
            ->whereDate('date', '>', $before)
            ->where(function ($query) {
                $query->where('homeTeamId', $id)
                    ->orWhere('awayTeamId', $id);
            })->get();
    $ret = array();
    $past = array();
    $cacheTeams = array();
    $cacheTeams[$team->id] = $team;
    foreach ($fixtures as $f)
    {
        if(!isset($cacheTeams[$f->homeTeamId]))
        {
            $t = App\database\Team::find($f->homeTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        if(!isset($cacheTeams[$f->awayTeamId]))
        {
            $t = App\database\Team::find($f->awayTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        $home = $cacheTeams[$f->homeTeamId];
        $away = $cacheTeams[$f->awayTeamId];
        
        $fixture = new App\Http\Models\Game($f->id, $f->date,
                new \App\Http\Models\Team($home->id, $home->name, $home->code, $home->logo),
                new \App\Http\Models\Team($away->id, $away->name, $away->code, $away->logo),
                $f->status,
                $f->competitionId,
                $f->homeGoals,
                $f->awayGoals,
                $f->extraTimeHomeGoals,
                $f->extraTimeAwayGoals,
                $f->penaltiesHome,
                $f->penaltiesAway);
        
        if(strtotime($fixture->date) >= time())
        {
            $ret[] = $fixture;
        }
        else
        {
            $past[] = $fixture;
        }
    }
  
    return response()->json(new \App\Http\Models\TeamDetails($team->id, $team->name, 
            $team->code, $team->logo, $ret, $past,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/teams/" . $id,
                    "null",
                    "null"
                    )
            ));
});
