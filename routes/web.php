<?php
use Illuminate\Http\Request;
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
        "teams" => env('APP_URL') . "/teams/0",
        "live" => env('APP_URL'). "/monitors/fixtures/current"
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
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/teams/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/teams/" . ($page - 1) : "null") 
                    )
            ));
});

$app->post('/user/login', function (Request $request) {
    $token = $request->header("token");
    if(empty($token))
    {
        return response()->json(false);
    }
    
    list($u, $p) = explode(":", base64_decode($token));

    $user = App\Database\User::where("username", $u)->first();
    if($user == null)
    {
        return response()->json(false);
    }

    if(password_verify($p, $user->password))
    {
        $user->date = date("Y-m-d H:i:s", time());
        return response()->json(true);
    }

    return response()->json(false);
});

$app->post('/user/register', function (Request $request) {
    $token = $request->header("token");
    if(empty($token))
    {
        return response()->json("No token provided", 400);
    }
    
    list($u, $p) = explode(":", base64_decode($token));
    
    if(strlen($u) < 4)
    {
        return response()->json("Username is too short.", 400);
    }
    
    if(strlen($u) > 30)
    {
        return response()->json("Username is too long.", 400);
    }
    
    $uppercase = preg_match('@[A-Z]@', $p);
    $lowercase = preg_match('@[a-z]@', $p);
    $number    = preg_match('@[0-9]@', $p);

    if(!$uppercase || !$lowercase || !$number || strlen($p) < 8) 
    {
        return response()->json("Password is too weak.", 400);
    }
    
    $user = App\Database\User::where("username", $u)->first();
    
    if($user == null)
    {
        $newUser = new App\Database\User;
        $newUser->username = $u;
        $newUser->password = password_hash($p, PASSWORD_BCRYPT);
        $newUser->date = date("Y-m-d H:i:s", time());
        $newUser->save();
        
        return response()->json("Success");
    }
    
    return response()->json("Username already exists.", 400);
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
    $user = Auth::user();
    
    if($user != null)
    {
        $teams = App\Database\Team::where('name', 'LIKE', '%' . $search . '%')
                ->leftJoin('monitors', function ($join) use ($user) {
                    $join->on('teams.id', '=', 'monitors.teamId')
                         ->where('monitors.userId', '=', $user->id);
                })
            ->orderBy('name', 'desc')
            ->select('teams.*', 'monitors.id as mid')
            ->get();
    }
    else
    {
        $teams = App\Database\Team::where('name', 'LIKE', '%' . $search . '%')
            ->orderBy('name', 'desc')->get();
    }
    
    $ret = array();
    foreach ($teams as $t)
    {
        $ret[] = new App\Http\Models\TeamSearch($t->id, 
                $t->name, 
                $t->code, 
                $t->logo,
                $user != null ? $t->mid > 0 : false,
                env('APP_URL') . "/team/" . $t->id);
    }
  
    return response()->json(new \App\Http\Models\TeamSearchResult($ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/teams/search/" . $search,
                    "null",
                    "null"
                    )
            ));
});

$app->post('/monitors/new/{id:[0-9]+}', ['middleware' => 'auth', function ($id) use ($app) {
    $user = Auth::user();
    if($id == null || $user == null) 
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
}]);

$app->delete('/monitors/delete/{id:[0-9]+}', ['middleware' => 'auth', function($id) use ($app){
    $user = Auth::user();
    if($id == null || $user == null)
    {
        return response()->json(false);
    }
    
    $monitor = App\Database\Monitor::where('teamId', '=', $id)
        ->where('userId', $user->id)
        ->first();
    if($monitor == null)
    {
        return response()->json(false);
    }
    
    $monitor->delete();
    
    return response()->json(true);
}]);

$app->get('/monitors/fixtures/past/{page:[0-9]+}', 
        ['middleware' => 'auth', function ($page) use ($app) {
    if($page == null)
    {
        return response()->json(null);
    }
    
    $user = Auth::user();
    if($user == null)
    {
        return response()->json(null);
    }
    
    $before = date('Y-m-d', strtotime('-3 hours'));
    //$after = date('Y-m-d', strtotime('+1 week'));
    $monitors = \App\Database\Monitor::where('userId', $user->id)->select('teamId')->get();
    if($monitors->count() == 0)
    {
        return response()->json(new \App\Http\Models\PastFixtures(
            array(),
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/fixtures/past/" . $page,
                    "null",
                    "null"
                    )
            ));
    }
    
    $query = App\Database\Fixture::where('status', '=', 'FINISHED')
            //->where('date', '<=', $after)
            ->whereRaw('(homeTeamId in (' . $monitors->implode('teamId', ',') . ') or '
                    . 'awayTeamId in (' . $monitors->implode('teamId', ',') . '))')
            ->orderBy('date', 'desc');
 
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
  
    return response()->json(new \App\Http\Models\PastFixtures(
            $ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/fixtures/past/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/monitors/fixtures/past/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/monitors/fixtures/past/" . ($page - 1) : "null") 
                    )
            ));
}]);

$app->get('/monitors/fixtures/coming/{page:[0-9]+}', 
        ['middleware' => 'auth', function ($page) use ($app) {
    if($page == null)
    {
        return response()->json(null);
    }
    
    $user = Auth::user();
    if($user == null)
    {
        return response()->json(null);
    }
    
    $before = date('Y-m-d', strtotime('-3 hours'));
    //$after = date('Y-m-d', strtotime('+1 week'));
    $monitors = \App\Database\Monitor::where('userId', $user->id)->select('teamId')->get();
    if($monitors->count() == 0)
    {
        return response()->json(new \App\Http\Models\ComingFixtures(
            array(),
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/fixtures/coming/" . $page,
                    "null",
                    "null"
                    )
            ));
    }
    
    $query = App\Database\Fixture::
            where('status', '!=', 'FINISHED')
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
  
    return response()->json(new \App\Http\Models\ComingFixtures(
            $ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/fixtures/coming/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/monitors/fixtures/coming/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/monitors/fixtures/coming/" . ($page - 1) : "null") 
                    )
            ));
}]);

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
  
    return response()->json(new \App\Http\Models\ComingFixtures(
            $ret,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/monitors/fixtures/current",
                    "null",
                    "null"
                    )
            ));
});

$app->get('/team/{id:[0-9]+}/coming/{page:[0-9]+}', function ($id,
        $page) use ($app) {
    if($page == null)
    {
        return response()->json(null);
    }

    if($id == null)
    {
        return response()->json(null);
    }

    $team = App\Database\Team::find($id);
    if($team == null)
    {
        return response()->json(null);
    }
    
    $coming = App\Database\Fixture::
            where('status', '!=', 'FINISHED')
            //->where('date', '<=', $after)
            ->whereRaw('(homeTeamId =' . $id . ' or '
                    . 'awayTeamId =' . $id . ')')
            ->orderBy('date', 'asc');
    
    $cacheTeams = array();
    $count = $coming->count();
    $fixtures2 = $coming->skip($page * PAGESIZE)->take(PAGESIZE)->get();
    $retComing = array();
    foreach ($fixtures2 as $f)
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
        
        
        $retComing[] = $fixture;
    }
    
    return response()->json(new \App\Http\Models\ComingFixtures(
            $retComing,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/team/".$id."/coming/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/team/".$id."/coming/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/team/".$id."/coming/" . ($page - 1) : "null") 
                    )
            ));
});

$app->get('/team/{id:[0-9]+}/past/{page:[0-9]+}', function ($id,
        $page) use ($app) {
    if($page == null)
    {
        return response()->json(null);
    }

    if($id == null)
    {
        return response()->json(null);
    }

    $team = App\Database\Team::find($id);
    if($team == null)
    {
        return response()->json(null);
    }
    
    $past = App\Database\Fixture::
            where('status', '=', 'FINISHED')
            //->where('date', '<=', $after)
            ->whereRaw('(homeTeamId =' . $id . ' or '
                    . 'awayTeamId =' . $id . ')')
            ->orderBy('date', 'desc');
    
    $cacheTeams = array();
    $count = $past->count();
    $fixtures2 = $past->skip($page * PAGESIZE)->take(PAGESIZE)->get();
    $retPast = array();
    foreach ($fixtures2 as $f)
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
        
        
        $retPast[] = $fixture;
    }
    
    return response()->json(new \App\Http\Models\ComingFixtures(
            $retPast,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/team/".$id."/past/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/team/".$id."/past/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/team/".$id."/past/" . ($page - 1) : "null") 
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
    
    return response()->json(new \App\Http\Models\TeamDetails($team->id, $team->name, 
            $team->code, $team->logo,
            new \App\Http\Models\Links(
                    env('APP_URL') . "/team/" . $id,
                    "null",
                    "null"
                    )
            ));
});
 
$app->get('/monitors/{page:[0-9]+}', 
        ['middleware' => 'auth', function($page) use ($app){
    $user = Auth::user();
    
    $query = App\Database\Monitor::where('userId', $user->id);
            
    $count = $query->count();
    $monitors = $query->skip($page * PAGESIZE)->take(PAGESIZE)
                ->get();
    
    $ret = array();
    foreach ($monitors as $m)
    {
        $t = App\Database\Team::find($m->teamId);
        $ret[] = new App\Http\Models\Team($t->id, $t->name, $t->code, $t->logo,
                env('APP_URL') . "/team/" . $t->id);
    }
    
    return response()->json(new \App\Http\Models\TeamList($ret,
                new App\Http\Models\Links(
                        env('APP_URL') . "/monitors/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/monitors/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/monitors/" . ($page - 1) : "null") 
                    )
                ));
}]);

$app->get('/friends/pending/{page:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@getPending'
]);

$app->get('/friends/waiting/{page:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@getWaiting'
]);

$app->get('/friends/active/{page:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@getActive'
]);

$app->post('/friends/add/{id:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@add'
]);

$app->delete('/friends/remove/{id:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@remove'
]);

$app->post('/friends/accept/{id:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@accept'
]);

$app->post('/friends/refuse/{id:[0-9]+}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@refuse'
]);

$app->get('/friends/search/{search}', 
        ['middleware' => 'auth', 
            'uses' => 'FriendsController@search'
]);