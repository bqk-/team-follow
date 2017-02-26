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
        //todo
    }
}
