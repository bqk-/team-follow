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
            $pending,
            $active,
            $_links;
    
    public function __construct($pending, $active, Links $links)
    {
        $this->active = $active;
        $this->pending = $pending;
        $this->_links = $links;
    }
}