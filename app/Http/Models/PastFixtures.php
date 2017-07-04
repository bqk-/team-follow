<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of UpcomingFixtures
 *
 * @author thibault
 */
class PastFixtures
{
    public $games, $_links;
    public function __construct($games, $links)
    {
        $this->games = $games;
        $this->_links = $links;
    }
}
