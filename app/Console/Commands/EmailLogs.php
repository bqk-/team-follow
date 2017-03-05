<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Console\Commands;
use Illuminate\Console\Command;

/**
 * Description of EmailLogs
 *
 * @author thibault
 */
class EmailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email log files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Illuminate\Support\Facades\Mail::to('clcsblack@gmail.com')
                ->send(new Log);
        
        @unlink(__DIR__.'/../Logs/Teams.txt');
        @unlink(__DIR__.'/../Logs/Monitors.txt');
        @unlink(__DIR__.'/../Logs/Fixtures.txt');
    }
}

class Log extends \Illuminate\Mail\Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
    
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email')
                    ->from('team-follow@bqk.ddns.net')
                    ->with([
                        'teams' => @file_get_contents(__DIR__.'/../Logs/Teams.txt'),
                        'monitors' => @file_get_contents(__DIR__.'/../Logs/Monitors.txt'),
                        'fixtures' => @file_get_contents(__DIR__.'/../Logs/Fixtures.txt'),
                    ]);
    }
}
