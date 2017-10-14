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
        $start = time() - (7 * 24 * 60 * 60);
        $end = time() + (7 * 24 * 60 * 60 * 8);
        $current = $start;
        
        while($current < $end)
        {
            $current = $this->getPage($current);
        }
    }
    
    private function getPage($unixStamp)
    {
        $start = date('Y-m-d', $unixStamp);
        $end = date('Y-m-d', $unixStamp + (7 * 24 * 60 * 60));
        try 
        {
            $client = new \GuzzleHttp\Client();
            $request = $client->get('http://api.football-data.org/v1/fixtures'
                    . '?timeFrameStart=' . $start
                    . '&timeFrameEnd=' . $end,
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
                if(($ff = $this->getFixture($f->_links->self->href)) == null)
                {
                    $this->registerFixture($f);
                }   
                else
                {
                    $this->updateFixture($f, $ff);
                }
            }
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            echo 'Exception, retrying in 65 secondes..' . PHP_EOL;
            sleep(65);
            return $unixStamp;
        }
        
        return $unixStamp + (7 * 24 * 60 * 60);
    }
    
    private function getFixture($href)
    {
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $f = \App\Database\Fixture::find($id);
        return $f;
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
    
    private function updateFixture($f, \App\Database\Fixture $t)
    {
        $href = $f->_links->homeTeam->href;
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t->homeTeamId = $id;
        $href = $f->_links->awayTeam->href;
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t->awayTeamId = $id;
        $t->date = $f->date;
        $t->status = $f->status;
        if(empty($t->status))
        {
            $t->status = "SCHEDULED";
        }
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
            else
            {
                $t->extraTimeHomeGoals = null;
                $t->extraTimeAwayGoals = null;
            }
            
            if(isset($f->result->penaltyShootout))
            {
                $t->penaltiesHome = $f->result->penaltyShootout->goalsHomeTeam;
                $t->penaltiesAway = $f->result->penaltyShootout->goalsAwayTeam;
            }
            else
            {
                $t->penaltiesHome = null;
                $t->penaltiesAway = null;
            }
        }
        echo 'Updated ' . $f->homeTeamName .
                ' - ' . $f->awayTeamName . PHP_EOL;
        $t->save();
    }
}
