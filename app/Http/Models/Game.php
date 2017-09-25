<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Models;

class Game
{
    public $id,
            $date,
            $homeTeam,
            $awayTeam,
            $status,
            $competitionId,
            $homeGoals,
            $awayGoals,
            $extraTimeHomeGoals,
            $extraTimeAwayGoals,
            $penaltiesHome,
            $penaltiesAway;
    
    public function __construct($id, $date, Team $homeTeam, Team $awayTeam, $status, $competitionId, 
            $homeGoals, $awayGoals, $extraTimeHomeGoals, $extraTimeAwayGoals,
            $penaltiesHome, $penaltiesAway)
    {
        $this->id = $id;
        $this->date = $date;
        $this->homeTeam = $homeTeam;
        $this->awayTeam = $awayTeam;
        $this->status = $status;
        $this->competitionId = $competitionId;
        $this->homeGoals = $homeGoals;
        $this->awayGoals = $awayGoals;
        $this->extraTimeHomeGoals = $extraTimeHomeGoals;
        $this->extraTimeAwayGoals = $extraTimeAwayGoals;
        $this->penaltiesHome = $penaltiesHome;
        $this->penaltiesAway = $penaltiesAway;
    }
    
}
