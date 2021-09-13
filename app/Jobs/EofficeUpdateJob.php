<?php
namespace App\Jobs;

/**
 * Description of SceneSeederJob
 *
 * @author lizhijun
 */
class EofficeUpdateJob extends Job
{
    public function handle()
    {
        $versionJson = json_decode(file_get_contents('../version.json'), true);
        
        $versionJson['package'];
        
        $package = $this->getPackage();
        if ($versionJson['package'] > $package) {
            exec('..\..\..\php\php artisan eoffice:update ' . $versionJson['package']);
        }
    }
    public function getPackage()
    {
        $version = version();
        
        $verExplode = explode('_', $version);
        
        $package = isset($verExplode[1]) ? intval($verExplode[1]) : 10000000;
        
        return $package;
    }
}
