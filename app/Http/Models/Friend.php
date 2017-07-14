<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of Friend
 *
 * @author thibault
 */
class Friend
{
    public $user1,
            $user2,
            $status;
    
    public function __construct($u1, $u2, $status)
    {
        $this->user1 = $u1;
        $this->user2 = $u2;
        $this->status = $status;
    }
}
