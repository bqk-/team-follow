<?php

namespace App\Http\Controllers;

class FriendsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getPending($page)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(new \App\Http\Models\FriendList(null, null,
                new \App\Http\Models\Links(
                        env('APP_URL') . "/friends/pending/0",
                        "null",
                        "null")));
        }

        $query = \App\Database\Friend::where('user_id', $user->id)
            ->where('status', \App\Http\Models\FriendStatus::PENDING)
                ->with('user2');
        
        $count = $query->count();
        $friends = $query->skip($page * PAGESIZE)->take(PAGESIZE)
                ->get();
        
        $users = [];
        foreach ($friends as $f)
        {
            $friend = $f->user2;
            $users[] = new \App\Http\Models\Friend(
                        $friend->id,
                        $friend->username,
                        $f->status);
        }
        
        return response()->json(new \App\Http\Models\FriendList($users,
                 new \App\Http\Models\Links(
                    env('APP_URL') . "/friends/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/friends/pending/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/friends/pending/" . ($page - 1) : "null") 
                    )
                ));
    }
    
    public function getWaiting($page)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(new \App\Http\Models\FriendList(null, null,
                new \App\Http\Models\Links(
                        env('APP_URL') . "/friends/",
                        "null",
                        "null")));
        }
        $query = \App\Database\Friend::where('user_id_accept', $user->id)
            ->where('status', \App\Http\Models\FriendStatus::PENDING)
                ->with('user1');
        
        $count = $query->count();
        $friends = $query->skip($page * PAGESIZE)->take(PAGESIZE)
                ->get();
        
        $users = [];
        foreach ($friends as $f)
        {
            $friend = $f->user1;
            $users[] = new \App\Http\Models\Friend(
                        $friend->id,
                        $friend->username,
                        $f->status);
        }
        
        return response()->json(new \App\Http\Models\FriendList($users,
                 new \App\Http\Models\Links(
                    env('APP_URL') . "/friends/waiting/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/friends/waiting/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/friends/waiting/" . ($page - 1) : "null") 
                    )
                ));
    }
    
    public function getActive($page)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(new \App\Http\Models\FriendList(null, null,
                new \App\Http\Models\Links(
                        env('APP_URL') . "/friends/active/0",
                        "null",
                        "null")));
        }
        
        $query = \App\Database\Friend::
                where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('user_id_accept', $user->id);
                })
                ->where('status', \App\Http\Models\FriendStatus::ACCEPTED)
                ->with('user1')
                ->with('user2');
        
        $count = $query->count();
        $friends = $query->skip($page * PAGESIZE)->take(PAGESIZE)
                ->get();
        
        $users = [];
        foreach ($friends as $f)
        {
            $friend = $f->user_id == $user->id ? $f->user2 : $f->user1;
            $users[] = new \App\Http\Models\Friend(
                        $friend->id,
                        $friend->username,
                        $f->status);
        }
        
        return response()->json(new \App\Http\Models\FriendList($users,
                 new \App\Http\Models\Links(
                    env('APP_URL') . "/friends/active/" . $page,
                    (($page + 1) * PAGESIZE < $count ? env('APP_URL') . "/friends/active/" . ($page + 1) : "null"),
                    ($page > 0 ? env('APP_URL') . "/friends/active/" . ($page - 1) : "null") 
                    )
                ));
    }
    
    public function add($id)
    {
        $user = \Auth::user();
        if($user == null || $user->id == $id)
        {
            return response()->json(false);
        }
        
        $query = \App\Database\Friend::
            where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('user_id_accept', $user->id);
            })
            ->where(function ($query) use ($id) {
                $query->where('user_id', $id)
                    ->orWhere('user_id_accept', $id);
            })
            ->first();
            
        if($query != null)
        {
            return response()->json(false);
        }
        
        $friend = new \App\Database\Friend;
        $friend->user_id = $user->id;
        $friend->user_id_accept = $id;
        $friend->status = \App\Http\Models\FriendStatus::PENDING;
        $friend->save();
        
        return response()->json(true);
    }
    
    public function remove($id)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(false);
        }
        
        $query = \App\Database\Friend::
            where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('user_id_accept', $user->id);
            })
            ->where(function ($query) use ($id) {
                $query->where('user_id', $id)
                    ->orWhere('user_id_accept', $id);
            })
            ->first();
            
        if($query == null)
        {
            return response()->json(false);
        }
        
        $query->delete();
        
        return response()->json(true);
    }
    
    public function accept($id)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(false);
        }
        
        $query = \App\Database\Friend::
            where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('user_id_accept', $user->id);
            })
            ->where(function ($query) use ($id) {
                $query->where('user_id', $id)
                    ->orWhere('user_id_accept', $id);
            })
            ->first();
            
        if($query == null)
        {
            return response()->json(false);
        }
        
        if($query->status == \App\Http\Models\FriendStatus::ACCEPTED)
        {
            return response()->json(false);
        }
        
        $query->status = \App\Http\Models\FriendStatus::ACCEPTED;
        $query->save();
        
        return response()->json(true);
    }
    
    public function refuse($id)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(false);
        }
        
        $query = \App\Database\Friend::
            where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('user_id_accept', $user->id);
            })
            ->where(function ($query) use ($id) {
                $query->where('user_id', $id)
                    ->orWhere('user_id_accept', $id);
            })
            ->first();
            
        if($query == null)
        {
            return response()->json(false);
        }
        
        if($query->status == \App\Http\Models\FriendStatus::ACCEPTED)
        {
            return response()->json(false);
        }
        
        $query->delete();
        
        return response()->json(true);
    }
    
    public function search($search)
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(false);
        }
        
        $query = \App\Database\User::
            where('username', 'LIKE', '%' . $search . '%')
            ->where('id', '!=', $user->id)
            ->orderBy('username', 'desc')
            ->select('users.id', 'users.username', 'users.date')    
            ->get();
            
        $ret = [];
        foreach ($query as $u)
        {
            $ret[] = new \App\Http\Models\User($u->id, $u->username, $u->date);
        }
        
        return response()->json(new \App\Http\Models\FriendSearch($ret,
                new \App\Http\Models\Links(
                        env('APP_URL') . "/friends/search/" . $search,
                        "null",
                        "null")));
    }
}
