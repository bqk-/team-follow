<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of MonitorList
 *
 * @author thibault
 */
class MonitorList
{
    public
            $teams,
            $_links;
    
    public function __construct($teams, Links $links)
    {
        $this->teams = $teams;
        $this->_links = $links;
    }
}
