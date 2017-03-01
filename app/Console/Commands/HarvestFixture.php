<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HarvestFixture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:fixture';

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
            $count = \App\Database\Monitor::count();
            for($page = 0; $page < $count / 20; $page++)
            {
                $teams = \App\Database\Monitor::
                        select('teamId')->distinct()
                        ->skip($page * 20)->take(20)->get();
                foreach ($teams as $t)  
                {
                    $client = new \GuzzleHttp\Client();
                    $request = $client->get('http://api.football-data.org/v1/teams/' . 
                            $t->teamId . '/fixtures',
                            [
                            'headers' => [
                                'User-Agent'  => 'testing/1.0',
                                'Accept'      => 'application/json',
                                'X-Auth-Token'=> env('APIKEY')
                            ]
                        ]);
                    $response = json_decode($request->getBody());
                    foreach ($response->fixtures as $f)
                    {
                        if(!$this->existsFixture($f->_links->self->href))
                        {
                            $this->registerFixture($f);
                        }   
                        else
                        {
                            $this->updateFixture($f, $t);
                        }
                    }
                }
            }
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            sleep(65);
            $this->handle();
        }
    }
    
    private function existsFixture($href)
    {
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t = \App\Database\Fixture::find($id);
        return $t != null;
    }
    
    private function registerFixture($f)
    {
        echo 'Registered ' . $f->homeTeamName .
                ' - ' . $f->awayTeamName . PHP_EOL;
        $href = $f->_links->self->href;
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        
        $t = new \App\Database\Fixture();
        $t->id = $id;
        $this->updateFixture($f, $t);
    }
    
    private function updateFixture($f, $t)
    {
        $href = $f->_links->homeTeam->href;
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t->homeTeamId = $id;
        $href = $f->_links->awayTeam->href;
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t->awayTeamId = $id;
        $t->date = $f->date;
        $t->status = $f->status;
        $href = $f->_links->competition->href;
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t->competitionId = $id;
        if(isset($f->result))
        {
            $t->homeGoals = $f->result->goalsHomeTeam;
            $t->awayGoals = $f->result->goalsAwayTeam;
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
        }
        
        $t->save();
    }
}
