<?php

namespace App\Http\Models;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Team
{
    public $id,
            $name,
            $code,
            $logo,
            $link;
    
    public function __construct($id, $name, $code, $logo, $link)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->logo = $logo;
        $this->link = $link;
    }
}