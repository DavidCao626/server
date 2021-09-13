<?php


namespace App\EofficeApp\Elastic\Commands\Suggestion;


use App\EofficeApp\Elastic\Services\Suggestion\DiscoverService;
use Illuminate\Console\Command;

class DiscoverCommand extends Command
{
    protected $signature = 'es:suggestion:discover';

    public function handle()
    {
        /** @var DiscoverService $discover */
        $discover = app('App\EofficeApp\Elastic\Services\Suggestion\DiscoverService');
        $builder = $discover->discover();
    }
}