<?php

namespace App\Providers;

use DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (envOverload("SQL_LOG") === 'true') {
            DB::listen(
                function ($sql) {
                    // $sql is an object with the properties:
                    //  sql: The query
                    //  bindings: the sql query variables
                    //  time: The execution time for the query
                    //  connectionName: The name of the connection

                    // To save the executed queries to file:
                    // Process the sql and the bindings:
                    foreach ($sql->bindings as $i => $binding) {
                        if ($binding instanceof \DateTime) {
                            $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                        } else {
                            if (is_string($binding)) {
                                $sql->bindings[$i] = "'$binding'";
                            }
                        }
                    }

                    // Insert bindings into query
                    $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);

                    $query = vsprintf($query, $sql->bindings);
                    // echo $query."<hr>";
                    // Save the query to file
                    $logFile = fopen(
                        storage_path('logs' . DIRECTORY_SEPARATOR . 'query.sql'),
                        'a+'
                    );
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "][" . $sql->time . "]" . $query . PHP_EOL);
                    fclose($logFile);
                }
            );
        }

    }
}
