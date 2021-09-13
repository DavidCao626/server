<?php
namespace App\Jobs;

/**
 * Description of SceneSeederJob
 *
 * @author lizhijun
 */
class SceneSeederJob extends Job
{
    private $sceneUrl;
    private $fileSize;
    public function __construct($sceneUrl, $fileSize)
    {
        $this->sceneUrl = $sceneUrl;
        $this->fileSize = $fileSize;
    }
    public function handle()
    {
        app("App\EofficeApp\Home\Services\HomeService")->handleSceneSeeder($this->sceneUrl, $this->fileSize);
    }
}
