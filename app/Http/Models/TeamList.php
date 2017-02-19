<?php

namespace App\Http\Models;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class TeamList
{
    public
            $teams,
            $links;
    
    public function __construct($teams, Links $links)
    {
        $this->teams = $teams;
        $this->links = $links;
    }
}