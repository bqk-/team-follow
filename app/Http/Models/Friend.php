<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

/**
 * Description of Friend
 *
 * @author thibault
 */
class Friend
{
    public $id,
            $name,
            $status;
    
    public function __construct($id, $name, $status)
    {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
    }
}
