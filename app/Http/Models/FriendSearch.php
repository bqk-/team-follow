<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of FriendSearch
 *
 * @author thibault
 */
class FriendSearch
{
    public
            $results,
            $_links;
    
    public function __construct($results, Links $links)
    {
        $this->results = $results;
        $this->_links = $links;
    }
}
