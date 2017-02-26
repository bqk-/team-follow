<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of Monitor
 *
 * @author thibault
 */
class Monitor
{
    public $id, $team;
    
    public function __construct($id, Team $team)
    {
        $this->id = $id;
        $this->team = $team;
    }
}
