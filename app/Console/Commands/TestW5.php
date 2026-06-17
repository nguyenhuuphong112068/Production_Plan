<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestW5 extends Command
{
    protected $signature = 'test:w5';
    protected $description = 'Command description';

    public function handle()
    {
        $e = DB::table('stage_plan')->where('id', 43078)->first();
        dd($e);
    }
}
