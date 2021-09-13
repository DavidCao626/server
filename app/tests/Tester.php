<?php
namespace App\Tests;
/**
 * Description of Test
 *
 * @author lizhijun
 */
class Tester 
{
    public function run($module, $tester)
    {
        $class = 'App\EofficeApp\\' . ucfirst($module) . '\Tests\\' . ucfirst($tester);
        if (!class_exists($class)) {
            throw new \Exception('Class [' . $class . '] not exists');
        }
        $classObject = app($class);
        if (!method_exists($classObject, 'run')) {
            throw new \Exception('Method [run] missing from ' . $class);
        }

        return $classObject->run();
    }
}
