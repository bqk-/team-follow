<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of User
 *
 * @author thibault
 */
class User
{
    public $id,
            $name,
            $date,
            $friendStatus;
    
    public function __construct($id, $name, $date, $friendStatus)
    {
        $this->id = $id;
        $this->name = $name;
        $this->date = $date;
        $this->friendStatus = $friendStatus;
    }
}