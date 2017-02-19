<?php

namespace App\Http\Models;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Links
{
    public $_this,
            $_next,
            $_prev;
    
    public function __construct($current, $next, $prev)
    {
        $this->_this = $current;
        $this->_next = $next;
        $this->_prev = $prev;
    }
}