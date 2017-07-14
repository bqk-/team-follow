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
    
    public function friend()
    {
        return $this->hasOne('App\Database\Friend');
    } 
}