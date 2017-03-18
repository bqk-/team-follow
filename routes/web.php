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
    $count = App\Database\Team::count();
    $teams = App\Database\Team::skip($page * PAGESIZE)->take(PAGESIZE)->get();
    $ret = array();
    foreach ($teams as $t)
    {
        $ret[] = new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo,
                env('APP_URL') . "/team/" . $t->id);
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
    
    $teams = App\Database\Team::where('name', 'LIKE', '%' . $search . '%')
            ->orderBy('name', 'desc')->get();
    $ret = array();
    foreach ($teams as $t)
    {
        $ret[] = new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo,
                env('APP_URL') . "/team/" . $t->id);
    }
  
    return response()->json(new \App\Http\Models\TeamList($ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/teams/search/" . $search,
                    "null",
                    "null"
                    )
            ));
});

$app->post('/manage/users/new/{id:[0-9]+}', function ($id) use ($app) {
    if($id == null)
    {
        return response()->json(false);
    }
    
    $user = App\Database\User::where('fb_id',$id)->first();
    if($user != null)
    {
        return response()->json(true);
    }
    
    $user = new App\Database\User;
    $user->fb_id = $id;
    $user->date = date('Y-m-d H:i:s', time());
    $user->save();
    
    return response()->json(true);
});

$app->post('/manage/monitors/new/{user:[0-9]+}/{id:[0-9]+}', function ($user, $id) use ($app) {
    if($id == null || $user == null) 
    {
        return response()->json(false);
    }
    
    $user = App\Database\User::where('fb_id', $user)->first();
    if($user == null)
    {
        return response()->json(false);
    }
    
    $team = App\Database\Team::find($id);
    if($team == null)
    {
        return response()->json(false);
    }
    
    $monitor = App\Database\Monitor::where('teamId', '=', $id)
        ->where('userId', $user->id)
        ->first();
    if($monitor != null)
    {
        return response()->json(false);
    }
    
    $monitor = new App\Database\Monitor;
    $monitor->teamId = $team->id;
    $monitor->userId = $user->id;
    $monitor->save();
    
    return response()->json(true);
});

$app->delete('manage/monitors/delete/{user:[0-9]+}/{id:[0-9]+}', function($user, $id) use ($app){
    if($id == null || $user == null) 
    {
        return response()->json(false);
    }
    
    $user = App\Database\User::where('fb_id', $user)->first();
    if($user == null)
    {
        return response()->json(false);
    }
    
    $team = App\Database\Team::find($id);
    if($team == null)
    {
        return response()->json(false);
    }
    
    $monitor = App\Database\Monitor::where('id', '=', $id)
        ->where('userId', $user->id)
        ->first();
    if($monitor == null)
    {
        return response()->json(false);
    }
    
    $monitor->delete();
    
    return response()->json(true);
});

$app->get('/monitors/{userId:[0-9]+}/fixtures/{page:[0-9]+}', function ($userId, $page) use ($app) {
    if($userId == null)
    {
        return response()->json(null);
    }
    
    if($page == null)
    {
        return response()->json(null);
    }
    
    $user = App\Database\User::where('fb_id', $userId)->first();
    if($user == null)
    {
        return response()->json(null);
    }
    
    $before = date('Y-m-d', strtotime('yesterday'));
    //$after = date('Y-m-d', strtotime('+1 week'));
    $monitors = \App\Database\Monitor::where('userId', $user->id)->select('teamId')->get();
    if($monitors->count() == 0)
    {
        return response()->json(new \App\Http\Models\UpcomingFixtures(
            array(),
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/" . $userId . '/fixtures/' . $page,
                    "null",
                    "null"
                    )
            ));
    }
    
    $query = App\Database\Fixture::where('date', '>', $before)
            //->where('date', '<=', $after)
            ->whereRaw('(homeTeamId in (' . $monitors->implode('teamId', ',') . ') or '
                    . 'awayTeamId in (' . $monitors->implode('teamId', ',') . '))')
            ->orderBy('date', 'asc');
 
    $count = $query->count();
    $fixtures = $query->skip($page * PAGESIZE)->take(PAGESIZE)->get();
    $ret = array();
    $cacheTeams = array();
    foreach ($fixtures as $f)
    {
        if(!isset($cacheTeams[$f->homeTeamId]))
        {
            $t = App\Database\Team::find($f->homeTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        if(!isset($cacheTeams[$f->awayTeamId]))
        {
            $t = App\Database\Team::find($f->awayTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        $home = $cacheTeams[$f->homeTeamId];
        $away = $cacheTeams[$f->awayTeamId];
        
        $fixture = new App\Http\Models\Game($f->id, $f->date,
                new \App\Http\Models\Team($home->id, $home->name, $home->code, $home->logo,
                        env('APP_URL') . "/team/" . $home->id),
                new \App\Http\Models\Team($away->id, $away->name, $away->code, $away->logo,
                        env('APP_URL') . "/team/" . $away->id),
                $f->status,
                $f->competitionId,
                $f->homeGoals,
                $f->awayGoals,
                $f->extraTimeHomeGoals,
                $f->extraTimeAwayGoals,
                $f->penaltiesHome,
                $f->penaltiesAway);
        
        
        $ret[] = $fixture;
    }
  
    return response()->json(new \App\Http\Models\UpcomingFixtures(
            $ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/" . $userId . '/fixtures/' . $page,
                    ($page + 1 * PAGESIZE < $count ? env('APP_URL') . "/monitors/" . $userId . '/fixtures/' . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/monitors/" . $userId . '/fixtures/' . ($page - 1) : "null") 
                    )
            ));
});

$app->get('/monitors/fixtures/current', function () use ($app) {
    date_default_timezone_set("UTC"); 
    $now = time();
    $fixtures = \App\Database\Fixture::where('status', '!=', 'FINISHED')
                    ->where('date', '<=', date('Y-m-d\TH:i', $now))
                    ->where('date', '>=', date('Y-m-d\TH:i', $now - 10800))
            ->orderBy('date', 'asc')
            ->get();
    
    $ret = array();
    $cacheTeams = array();
    foreach ($fixtures as $f)
    {
        if(!isset($cacheTeams[$f->homeTeamId]))
        {
            $t = App\Database\Team::find($f->homeTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        if(!isset($cacheTeams[$f->awayTeamId]))
        {
            $t = App\Database\Team::find($f->awayTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        $home = $cacheTeams[$f->homeTeamId];
        $away = $cacheTeams[$f->awayTeamId];
        
        $fixture = new App\Http\Models\Game($f->id, $f->date,
                new \App\Http\Models\Team($home->id, $home->name, $home->code, $home->logo,
                        env('APP_URL') . "/team/" . $home->id),
                new \App\Http\Models\Team($away->id, $away->name, $away->code, $away->logo,
                        env('APP_URL') . "/team/" . $away->id),
                $f->status,
                $f->competitionId,
                $f->homeGoals,
                $f->awayGoals,
                $f->extraTimeHomeGoals,
                $f->extraTimeAwayGoals,
                $f->penaltiesHome,
                $f->penaltiesAway);
        
        
        $ret[] = $fixture;
    }
  
    return response()->json(new \App\Http\Models\UpcomingFixtures(
            $ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/fixtures/current",
                    "null",
                    "null"
                    )
            ));
});


$app->get('/team/{id:[0-9]+}', function ($id) use ($app) {
    if($id == null)
    {
        return response()->json(null);
    }
    
    $team = App\Database\Team::find($id);
    if($team == null)
    {
        return response()->json(null);
    }
    
    $before = date('Y-m-d', time() - (30 * 3600 * 24));
    $after = date('Y-m-d', time() + (60 * 3600 * 24));
    
    $fixtures = App\Database\Fixture::where('date', '>', $before)
            ->where('date', '<', $after)
            ->where(function ($query) use ($id) {
                $query->where('homeTeamId', $id)
                    ->orWhere('awayTeamId', $id);
            })->orderBy('date', 'desc')
            ->get();
    $ret = array();
    $cacheTeams = array();
    $cacheTeams[$team->id] = $team;
    foreach ($fixtures as $f)
    {
        if(!isset($cacheTeams[$f->homeTeamId]))
        {
            $t = App\Database\Team::find($f->homeTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        if(!isset($cacheTeams[$f->awayTeamId]))
        {
            $t = App\Database\Team::find($f->awayTeamId);
            if($t == null)
            {
                continue;
            }
            
            $cacheTeams[$t->id] = $t;
        }
        
        $home = $cacheTeams[$f->homeTeamId];
        $away = $cacheTeams[$f->awayTeamId];
        
        $fixture = new App\Http\Models\Game($f->id, $f->date,
                new \App\Http\Models\Team($home->id, $home->name, $home->code, $home->logo,
                        env('APP_URL') . "/team/" . $home->id),
                new \App\Http\Models\Team($away->id, $away->name, $away->code, $away->logo,
                        env('APP_URL') . "/team/" . $away->id),
                $f->status,
                $f->competitionId,
                $f->homeGoals,
                $f->awayGoals,
                $f->extraTimeHomeGoals,
                $f->extraTimeAwayGoals,
                $f->penaltiesHome,
                $f->penaltiesAway);
        
        
        $ret[] = $fixture;
    }
  
    return response()->json(new \App\Http\Models\TeamDetails($team->id, $team->name, 
            $team->code, $team->logo,
            $ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/team/" . $id,
                    "null",
                    "null"
                    )
            ));
});


$app->get('/monitors/{userId:[0-9]+}', function($userId) use ($app){
    if($userId == 0) 
    {
        $monitors = \App\Database\Monitor::all();
    }
    else
    {
        $user = App\Database\User::where('fb_id', $userId)->first();
        if($user == null)
        {
            return response()->json(new \App\Http\Models\MonitorList(array(),
                    new App\Http\Models\Links(
                            env('APP_URL') . "/monitors/" . $userId,
                            "null",
                            "null"
                            )));
        }

        $monitors = App\Database\Monitor::where('userId', $user->id)->get();
    }
    
    $ret = array();
    foreach ($monitors as $m)
    {
        $t = App\Database\Team::find($m->teamId);
        $ret[] = new \App\Http\Models\Monitor($m->id, new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo,
                env('APP_URL') . "/team/" . $t->id));
    }
    
    return response()->json(new \App\Http\Models\MonitorList($ret,
                new App\Http\Models\Links(
                        env('APP_URL') . "/monitors/" . $userId,
                        "null",
                        "null"
                        )));
});