<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Monitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harvest foot data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try
        {
            date_default_timezone_set("UTC"); 
            $now = time();
                $fixtures = \App\Database\Fixture::where('status', '!=', 'FINISHED')
                    ->where('date', '<=', date('Y-m-d\TH:i', $now))
                    ->where('date', '>=', date('Y-m-d\TH:i', $now - 10800))
                    ->get();
            foreach ($fixtures as $t)  
            {
                $client = new \GuzzleHttp\Client();
                $request = $client->get('http://api.football-data.org/v1/fixtures/' . 
                        $t->id,
                        [
                        'headers' => [
                            'User-Agent'  => 'testing/1.0',
                            'Accept'      => 'application/json',
                            'X-Auth-Token'=> env('APIKEY')
                        ]
                    ]);
                $response = json_decode($request->getBody());
                $f = $response->fixture;
                
                if(isset($f->result) && $this->hasChanged($t, $f))
                {
                    $t->homeGoals = $f->result->goalsHomeTeam;
                    $t->awayGoals = $f->result->goalsAwayTeam;
                    $t->status = $f->status;
                    if(isset($f->result->extraTime))
                    {
                        $t->extraTimeHomeGoals = $f->result->extraTime->goalsHomeTeam;
                        $t->extraTimeAwayGoals = $f->result->extraTime->goalsAwayTeam;
                    }

                    if(isset($f->result->penaltyShootout))
                    {
                        $t->penaltiesHome = $f->result->penaltyShootout->goalsHomeTeam;
                        $t->penaltiesAway = $f->result->penaltyShootout->goalsAwayTeam;
                    }
                    
                    echo 'Game update: ' . $f->homeTeamName .' ' . $f->result->goalsHomeTeam .
                            ' vs ' . $f->result->goalsAwayTeam . ' ' . $f->awayTeamName . ' / ' . $t->status .  PHP_EOL;
                    $t->save();
                }
            }
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            sleep(60);
            $this->handle();
        }
    }
    
    private function hasChanged(\App\Database\Fixture $t, $json)
    {
        $equals = $t->status === $json->status;
        $equals &= $t->homeGoals === $json->result->goalsHomeTeam
        && $t->awayGoals === $json->result->goalsAwayTeam;
        if(isset($json->result->extraTime))
        {
            $equals &= $t->extraTimeHomeGoals === $json->result->extraTime->goalsHomeTeam
            && $t->extraTimeAwayGoals === $json->result->extraTime->goalsAwayTeam;
        }

        if(isset($json->result->penaltyShootout))
        {
            $equals &= $t->penaltiesHome === $json->result->penaltyShootout->goalsHomeTeam
            && $t->penaltiesAway === $json->result->penaltyShootout->goalsAwayTeam;
        }
        
        return !$equals;
    }
}
