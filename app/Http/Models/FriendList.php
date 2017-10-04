<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Models;

class FriendList
{
    public
            $results,
            $_links;
    
    public function __construct($users, Links $links)
    {
        $this->results = $users;
        $this->_links = $links;
    }
}