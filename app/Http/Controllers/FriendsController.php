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

    public function get()
    {
        $user = \Auth::user();
        if($user == null)
        {
            return response()->json(new \App\Http\Models\FriendList(null, null,
                new \App\Http\Models\Links(
                        env('APP_URL') . "/friends/",
                        null,
                        null)));
        }
        
        $query = \App\Database\Friend::where('user_id', $user->id)
            ->orWhere('user_id_accept', $user->id)
                ->with('user1')
                ->with('user2')
            ->get();
        
        $active = [];
        $pending = [];
        foreach ($query as $f)
        {
            $friend = $f->user_id == $user->id ? $f->user2 : $f->user1;
            if($f->status == \App\Http\Models\FriendStatus::ACCEPTED)
            {
                $active[] = new \App\Http\Models\Friend(
                        $friend->id,
                        $friend->username,
                        $f->status);
            }
            else
            {
                $pending[] = new \App\Http\Models\Friend(
                        $friend->id,
                        $friend->username,
                        $f->status);
            }
        }
        
        return response()->json(new \App\Http\Models\FriendList($pending, $active,
                new \App\Http\Models\Links(
                        env('APP_URL') . "/friends/",
                        null,
                        null)));
    }
    
    public function add($id)
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
        
        $query->status = \App\Http\Models\FriendStatus::PENDING;
        
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
            leftJoin('friends as f1', 'f1.user_id', '=', 'users.id')
            ->leftJoin('friends as f2', 'f2.user_id_accept', '=', 'users.id')
             ->where('username', 'LIKE', '%' . $search . '%')
                ->whereNull('f1.id')
                ->whereNull('f2.id')
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
                        env('APP_URL') . "/friends/search/" + $search,
                        null,
                        null)));
    }
}
