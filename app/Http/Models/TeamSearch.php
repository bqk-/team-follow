<?php

namespace App\Http\Models;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class TeamSearch
{
    public $id,
            $name,
            $code,
            $logo,
            $isFollowed,
            $link;
    
    public function __construct($id, $name, $code, $logo, $isFollowed, $link)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->logo = $logo;
        $this->isFollowed = $isFollowed;
        $this->link = $link;
    }
}