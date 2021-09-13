<?php
namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
class EofficeLogger 
{
    public function __invoke(array $config)
    {
        $logger = new Logger('custom');
        $lineFormatter = new LineFormatter(null, 'Y-m-d H:i:s', true, true);
        $stremHandler = new StreamHandler($config['path']);
        $stremHandler->setFormatter($lineFormatter);
        return $logger->pushHandler($stremHandler);
    }
}
