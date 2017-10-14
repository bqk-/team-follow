<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HarvestTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:team';

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
            $client = new \GuzzleHttp\Client();
            $request = $client->get('http://api.football-data.org/v1/fixtures?timeFrame=n60',
                    [
                    'headers' => [
                        'User-Agent'  => 'testing/1.0',
                        'Accept'      => 'application/json',
                        'X-Auth-Token'=> env('APIKEY')
                    ]
                ]);
            $response = json_decode($request->getBody());
            $done = [];
            foreach ($response->fixtures as $f)
            {
                if(($t = $this->getTeam($f->_links->homeTeam->href)) == null)
                {
                    $this->registerTeam($f->_links->homeTeam->href);
                }
                else
                {
                    $this->updateTeam($t);
                }

                if(($t = $this->getTeam($f->_links->awayTeam->href)) == null)
                {
                    $this->registerTeam($f->_links->awayTeam->href);
                }
                else
                {
                    $this->updateTeam($t);
                }      
                
                $done[] = $f->_links->homeTeam->href;
                $done[] = $f->_links->awayTeam->href;
            }
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            sleep(65);
            $this->handle();
        }
    }
    
    private function getTeam($href)
    {
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        $t = \App\Database\Team::find($id);
        return $t;
    }
    
    private function registerTeam($href)
    {
        $client = new \GuzzleHttp\Client();
        $request = $client->get($href,
                [
                'headers' => [
                    'User-Agent'  => 'testing/1.0',
                    'Accept'      => 'application/json',
                    'X-Auth-Token'=> env('APIKEY')
                ]
            ]);
        
        $team = json_decode($request->getBody());
        $t = new \App\Database\Team();
        echo 'Registered ' . $team->name .  PHP_EOL;
        
        $id = substr($href, strrpos($href, '/') + 1, strlen($href) - 1);
        
        $t->id = $id;
        $t->name = $team->name;
        $t->code = $team->code;
        $t->logo = $team->crestUrl;
        $t->save();
    }
    
    private function updateTeam($href)
    {
        //
    }
}
