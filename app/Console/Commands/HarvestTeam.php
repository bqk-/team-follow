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
    protected $signature = 'harvest_team';

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
        $client = new \GuzzleHttp\Client();
        $request = $client->get('http://api.football-data.org/v1/fixtures?timeFrame=n1',
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
            if(!$this->existsTeam($f->_links->homeTeam->href))
            {
                $this->registerTeam($f->_links->homeTeam->href);
            }            
        }
    }
    
    private function existsTeam($href)
    {
        $id = substr($href, strrpos($href, '/'), strlen($href) - 1);
        $t = \App\database\Team::find($id);
        return $t != null;
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
        $t = new \App\database\Team();
        echo 'Registered ' . $team->name . '\n';
        $t->id = $team->id;
        $t->name = $team->name;
        $t->code = $team->code;
        $t->logo = $team->crestUrl;
        $t->save();
    }
}
