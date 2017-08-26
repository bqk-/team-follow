<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Database;

/**
 * Description of Friend
 *
 * @author thibault
 */
class Friend extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    
    public function user1()
    {
        return $this->hasOne('App\Database\User', 'id', 'user_id');
    }
    
    public function user2()
    {
        return $this->hasOne('App\Database\User', 'id', 'user_id_accept');
    }
}